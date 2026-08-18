<?php
/**
 * Session, CSRF and the admin gate. Include this at the top of every admin-*.php.
 */

require_once __DIR__ . '/lib.php';

function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on')
                      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    ]);
    session_name('sbs_session');
    session_start();
}

// ------------------------------------------------------------------- CSRF ----

function csrf_token(): string
{
    boot_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    boot_session();
    $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $sent)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่'], 419);
        }
        http_response_code(419);
        exit('เซสชันหมดอายุ กรุณากลับไปหน้าเดิมแล้วลองใหม่อีกครั้ง');
    }
}

// ------------------------------------------------------------------ users ----

function current_user(): ?array
{
    boot_session();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    if (isset($GLOBALS['__user']) && $GLOBALS['__user']['id'] == $_SESSION['uid']) {
        return $GLOBALS['__user'];
    }
    $st = db()->prepare('SELECT * FROM users WHERE id = ? AND status = ?');
    $st->execute([$_SESSION['uid'], 'active']);
    $user = $st->fetch() ?: null;
    $GLOBALS['__user'] = $user;
    return $user;
}

function attempt_login(string $email, string $password): bool
{
    $st = db()->prepare('SELECT * FROM users WHERE email = ? AND status = ?');
    $st->execute([mb_strtolower(trim($email)), 'active']);
    $user = $st->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    boot_session();
    session_regenerate_id(true);
    $_SESSION['uid']  = $user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
        ->execute([date('Y-m-d H:i:s'), $user['id']]);

    $GLOBALS['__user'] = $user;
    log_activity('login', $user['email']);
    return true;
}

function logout(): void
{
    boot_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Every admin page starts with this. */
function require_admin(): array
{
    $user = current_user();
    if (!$user) {
        $to = urlencode($_SERVER['REQUEST_URI'] ?? '/admin.php');
        redirect('admin-login.php?next=' . $to);
    }
    return $user;
}

/** Owner-only areas: users, system settings. */
function require_owner(): array
{
    $user = require_admin();
    if ($user['role'] !== 'owner') {
        http_response_code(403);
        exit('หน้านี้เปิดให้เฉพาะผู้ดูแลระบบเท่านั้น');
    }
    return $user;
}

function is_owner(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'owner';
}

function role_label(string $role): string
{
    return match ($role) {
        'owner'  => 'ผู้ดูแลระบบ',
        'editor' => 'ผู้จัดการเนื้อหา',
        'staff'  => 'ทีมงาน',
        default  => $role,
    };
}
