<?php
/**
 * Email: template rendering plus three ways to deliver.
 *
 *   MAIL_LOG_ONLY = true  → nothing leaves the server, every letter lands in
 *                           email_log so it can be read at admin-emails.php
 *   SMTP_HOST set         → sent over SMTP (STARTTLS, AUTH LOGIN)
 *   otherwise             → PHP mail()
 *
 * Every letter is written to email_log either way, so the studio always has a
 * record of what was sent to a customer.
 */

require_once __DIR__ . '/lib.php';

/** Templates the admin panel can send, in the order they appear in the UI. */
function email_templates(): array
{
    return [
        'new-message'      => 'แจ้งทีมงาน: มีข้อความใหม่จากลูกค้า',
        'message-received' => 'ตอบกลับลูกค้า: ได้รับข้อความแล้ว',
        'album-ready'      => 'ส่งลิงก์อัลบั้มให้ลูกค้า',
        'album-reminder'   => 'เตือนลูกค้าให้ดาวน์โหลดไฟล์',
        'reply'            => 'ตอบกลับลูกค้าด้วยข้อความอิสระ',
        'user-invite'      => 'เชิญทีมงานเข้าใช้ระบบหลังบ้าน',
    ];
}

/**
 * Render one template into a full HTML letter.
 * Returns ['subject' => string, 'html' => string].
 */
function render_email(string $template, array $vars = []): array
{
    $file = __DIR__ . '/emails/' . basename($template) . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('ไม่พบเทมเพลตอีเมล: ' . $template);
    }

    $subject = '';
    $preheader = '';
    extract($vars, EXTR_SKIP);

    ob_start();
    require $file;                 // sets $subject, $preheader; echoes the body
    $content = (string) ob_get_clean();

    ob_start();
    require __DIR__ . '/emails/layout.php';
    $html = (string) ob_get_clean();

    return ['subject' => $subject, 'html' => $html];
}

function send_email(string $to, string $template, array $vars = []): bool
{
    try {
        $mail = render_email($template, $vars);
    } catch (Throwable $e) {
        log_email($to, '(render failed)', $template, '', 'failed', $e->getMessage());
        return false;
    }

    $subject = $vars['subject_override'] ?? $mail['subject'];

    if (MAIL_LOG_ONLY) {
        log_email($to, $subject, $template, $mail['html'], 'logged', 'MAIL_LOG_ONLY เปิดอยู่ ไม่ได้ส่งออกจริง');
        return true;
    }

    try {
        $ok = SMTP_HOST !== ''
            ? smtp_send($to, $subject, $mail['html'])
            : php_mail_send($to, $subject, $mail['html']);
        log_email($to, $subject, $template, $mail['html'], $ok ? 'sent' : 'failed', $ok ? null : 'ผู้ให้บริการอีเมลปฏิเสธ');
        return $ok;
    } catch (Throwable $e) {
        log_email($to, $subject, $template, $mail['html'], 'failed', $e->getMessage());
        return false;
    }
}

function log_email(string $to, string $subject, string $template, string $body, string $status, ?string $error = null): void
{
    try {
        db()->prepare(
            'INSERT INTO email_log (to_email, subject, template, body, status, error)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$to, mb_substr($subject, 0, 300), $template, $body, $status, $error ? mb_substr($error, 0, 400) : null]);
    } catch (Throwable $e) {
        // Never let logging break a send.
    }
}

function php_mail_send(string $to, string $subject, string $html): bool
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . mail_encode_name(MAIL_FROM_NAME) . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_ADMIN,
        'X-Mailer: saybaewstudio',
    ];
    return mail($to, mail_encode_name($subject), $html, implode("\r\n", $headers));
}

function mail_encode_name(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

/**
 * Minimal SMTP client: EHLO, STARTTLS, AUTH LOGIN, one recipient.
 * Enough for the transactional mail this site sends, with no dependency to add.
 */
function smtp_send(string $to, string $subject, string $html): bool
{
    $host    = SMTP_HOST;
    $port    = (int) SMTP_PORT;
    $timeout = 20;

    $transport = $port === 465 ? 'ssl://' : '';
    $fp = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, $timeout);
    if (!$fp) {
        throw new RuntimeException('เชื่อมต่อ SMTP ไม่ได้: ' . $errstr);
    }
    stream_set_timeout($fp, $timeout);

    $expect = function (array $codes) use ($fp): string {
        $line = '';
        do {
            $line = (string) fgets($fp, 1024);
            if ($line === '') {
                throw new RuntimeException('SMTP ปิดการเชื่อมต่อกลางคัน');
            }
        } while (isset($line[3]) && $line[3] === '-');
        if (!in_array((int) substr($line, 0, 3), $codes, true)) {
            throw new RuntimeException('SMTP ตอบกลับผิดคาด: ' . trim($line));
        }
        return $line;
    };
    $say = function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };

    $expect([220]);
    $say('EHLO ' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost'));
    $expect([250]);

    if ($port !== 465) {
        $say('STARTTLS');
        $expect([220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('เปิด TLS กับ SMTP ไม่สำเร็จ');
        }
        $say('EHLO ' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost'));
        $expect([250]);
    }

    if (SMTP_USER !== '') {
        $say('AUTH LOGIN');
        $expect([334]);
        $say(base64_encode(SMTP_USER));
        $expect([334]);
        $say(base64_encode(SMTP_PASS));
        $expect([235]);
    }

    $say('MAIL FROM:<' . MAIL_FROM . '>');
    $expect([250]);
    $say('RCPT TO:<' . $to . '>');
    $expect([250, 251]);
    $say('DATA');
    $expect([354]);

    $headers = implode("\r\n", [
        'From: ' . mail_encode_name(MAIL_FROM_NAME) . ' <' . MAIL_FROM . '>',
        'To: <' . $to . '>',
        'Subject: ' . mail_encode_name($subject),
        'Date: ' . date('r'),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ]);

    // Dot-stuffing is not needed for base64 output, which never starts a line with a dot.
    $body = chunk_split(base64_encode($html), 76, "\r\n");
    fwrite($fp, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
    $expect([250]);

    $say('QUIT');
    fclose($fp);
    return true;
}
