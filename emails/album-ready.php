<?php
/**
 * Sent from the album page when the studio hands an album over to the customer.
 * Vars: name, album_title, album_url, photo_count, video_count, event_date,
 *       access_code (optional), note (optional)
 */
require_once __DIR__ . '/_parts.php';

$subject   = 'อัลบั้ม "' . ($album_title ?? '') . '" พร้อมให้ดาวน์โหลดแล้ว';
$preheader = 'รูปทั้งหมด ' . (int) ($photo_count ?? 0) . ' รูป ดาวน์โหลดไฟล์ขนาดเต็มได้เลย';

echo em_eyebrow('อัลบั้มพร้อมแล้ว');
echo em_h1('รูปงานของคุณจัดเสร็จเรียบร้อย');
echo em_p('สวัสดีค่ะคุณ <strong>' . e($name ?? 'ลูกค้า') . '</strong><br>'
        . 'อัลบั้ม <strong>' . e($album_title ?? '') . '</strong> คัดและแต่งเสร็จแล้ว '
        . 'เปิดดูและดาวน์โหลดไฟล์ขนาดเต็มได้จากลิงก์ด้านล่างค่ะ');

echo em_stats([
    'รูปภาพ'        => fmt_num((int) ($photo_count ?? 0)),
    'วิดีโอ'         => fmt_num((int) ($video_count ?? 0)),
]);

echo em_button('เปิดอัลบั้มของฉัน', $album_url ?? url('albums.php'));

if (!empty($access_code)) {
    echo em_note('อัลบั้มนี้ตั้งเป็นแบบส่วนตัว ใส่รหัสเข้าชม <strong style="letter-spacing:1px;">'
        . e($access_code) . '</strong> เมื่อเปิดลิงก์');
}

echo em_divider();

echo em_p('<strong>สิ่งที่ทำได้ในหน้าอัลบั้ม</strong>');
echo em_p(
    '· ดาวน์โหลดทีละรูป หรือเลือกหลายรูปแล้วดาวน์โหลดพร้อมกันเป็นไฟล์ ZIP<br>'
  . '· ไฟล์ที่ได้เป็นไฟล์ขนาดเต็มจากกล้อง ไม่ถูกบีบอัด สั่งอัดขยายได้<br>'
  . '· กดแชร์ลิงก์ให้ญาติหรือผู้ปกครองท่านอื่นเปิดดูได้ทันที ไม่ต้องสมัครสมาชิก'
);

if (!empty($note)) {
    echo em_panel('<div style="font-size:14.5px;color:#2A241C;line-height:1.85;">'
        . nl2br(e($note)) . '</div>');
}

echo em_p('<span style="font-size:13px;color:#7C7267;">ไฟล์จะเก็บไว้บนระบบอย่างน้อย 12 เดือน '
    . 'แนะนำให้ดาวน์โหลดเก็บไว้ในเครื่องด้วยอีกชุดหนึ่งนะคะ</span>');
