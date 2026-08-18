<?php
/**
 * Sent when the owner adds a teammate on admin-users.php.
 * Vars: name, email, temp_password, role_label, login_url
 */
require_once __DIR__ . '/_parts.php';

$subject   = 'บัญชีเข้าใช้ระบบหลังบ้าน สายแบ้วสตูดิโอ';
$preheader = 'ตั้งรหัสผ่านใหม่ทันทีหลังเข้าสู่ระบบครั้งแรก';

echo em_eyebrow('บัญชีทีมงาน');
echo em_h1('ยินดีต้อนรับเข้าทีมค่ะ');
echo em_p('สวัสดีค่ะคุณ <strong>' . e($name ?? '') . '</strong><br>'
        . 'บัญชีสำหรับเข้าใช้ระบบจัดการเว็บไซต์ของสายแบ้วสตูดิโอถูกสร้างให้แล้ว '
        . 'สิทธิ์การใช้งานของคุณคือ <strong>' . e($role_label ?? 'ทีมงาน') . '</strong>');

echo em_panel(
      em_row('อีเมลเข้าระบบ', $email ?? '')
    . em_row('รหัสผ่านชั่วคราว', $temp_password ?? '')
);

echo em_note('กรุณา<strong>เปลี่ยนรหัสผ่านทันที</strong>หลังเข้าสู่ระบบครั้งแรก '
    . 'และอย่าส่งต่ออีเมลฉบับนี้ให้ผู้อื่น');

echo em_button('เข้าสู่ระบบหลังบ้าน', $login_url ?? url('admin-login.php'));
