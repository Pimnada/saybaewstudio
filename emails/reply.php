<?php
/**
 * Free-text reply written by staff on admin-messages.php.
 * Vars: name, body, original (their message), staff_name, subject_override
 */
require_once __DIR__ . '/_parts.php';

$subject   = $subject_override ?? 'ตอบกลับจากสายแบ้วสตูดิโอ';
$preheader = excerpt($body ?? '', 110);

echo em_eyebrow('ตอบกลับจากทีมงาน');
echo em_h1('สวัสดีค่ะคุณ ' . ($name ?? 'ลูกค้า'));
echo '<div style="font-size:15px;line-height:1.9;color:#4A4238;">' . nl2br(e($body ?? '')) . '</div>';

if (!empty($original)) {
    echo em_divider();
    echo '<div style="font-size:12.5px;color:#9A9186;margin-bottom:8px;">ข้อความเดิมของคุณ</div>';
    echo em_panel('<div style="font-size:14px;color:#7C7267;line-height:1.8;font-style:italic;">'
        . nl2br(e($original)) . '</div>');
}

echo em_button_ghost('ดูผลงานทั้งหมด', url('albums.php'));

echo em_p('<span style="font-size:13.5px;color:#7C7267;">ด้วยความยินดี<br><strong style="color:#2A241C;">'
    . e($staff_name ?? 'ทีมงานสายแบ้วสตูดิโอ') . '</strong></span>');
