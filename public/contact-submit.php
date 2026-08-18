<?php
/**
 * Receives the enquiry form from inc/contact-band.php.
 *
 * Stores the message, notifies the team, sends the customer an acknowledgement,
 * then bounces back to whichever page the form was on.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../mailer.php';

if (!is_post()) {
    redirect('contact.php');
}
csrf_check();

$returnTo = basename((string) ($_POST['return_to'] ?? 'contact.php'));
if (!preg_match('/^[a-z0-9\-]+\.php$/', $returnTo)) {
    $returnTo = 'contact.php';
}
$back = $returnTo . '#contact';

// Honeypot: a real visitor never sees this field.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    flash('ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับโดยเร็วที่สุด');
    redirect($back);
}

$name      = trim((string) ($_POST['name'] ?? ''));
$phone     = trim((string) ($_POST['phone'] ?? ''));
$email     = trim((string) ($_POST['email'] ?? ''));
$jobType   = trim((string) ($_POST['job_type'] ?? ''));
$eventDate = trim((string) ($_POST['event_date'] ?? ''));
$detail    = trim((string) ($_POST['detail'] ?? ''));

$errors = [];
if ($name === '')  { $errors[] = 'กรุณากรอกชื่อ'; }
if ($phone === '') { $errors[] = 'กรุณากรอกเบอร์โทร'; }
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
}
if ($eventDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    $eventDate = '';
}

if ($errors) {
    boot_session();
    $_SESSION['contact_old'] = compact('name', 'phone', 'email', 'jobType', 'eventDate', 'detail')
        + ['job_type' => $jobType, 'event_date' => $eventDate];
    flash(implode(' · ', $errors), 'error');
    redirect($back);
}

// Simple rate limit: three enquiries per IP per hour is plenty for a studio.
$st = db()->prepare(
    "SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > ?"
);
$st->execute([client_ip(), date('Y-m-d H:i:s', time() - 3600)]);
if ((int) $st->fetchColumn() >= 3) {
    flash('คุณส่งข้อความบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่ หรือทักไลน์เข้ามาได้เลย', 'error');
    redirect($back);
}

db()->prepare(
    'INSERT INTO messages (name, phone, email, job_type, event_date, detail, source, status, ip)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([
    $name, $phone, $email ?: null, $jobType ?: null, $eventDate ?: null,
    $detail ?: null, 'web', 'new', client_ip(),
]);
$messageId = (int) db()->lastInsertId();

$vars = [
    'name'       => $name,
    'phone'      => $phone,
    'email'      => $email,
    'job_type'   => $jobType ?: 'ไม่ระบุ',
    'event_date' => $eventDate,
    'detail'     => $detail,
    'message_id' => $messageId,
];

// Notify the studio, and acknowledge to the customer when they left an address.
send_email(setting('notify_email', MAIL_ADMIN), 'new-message', $vars);
if ($email !== '') {
    send_email($email, 'message-received', $vars);
}

flash('ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับภายใน 1 วันทำการค่ะ');
redirect($back);
