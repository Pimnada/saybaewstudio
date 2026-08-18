<?php
/**
 * Nudge for a customer who has not downloaded their album yet.
 * Vars: name, album_title, album_url, photo_count, days_left
 */
require_once __DIR__ . '/_parts.php';

$subject   = 'ยังไม่ได้ดาวน์โหลดอัลบั้ม "' . ($album_title ?? '') . '" นะคะ';
$preheader = 'เหลืออีก ' . (int) ($days_left ?? 30) . ' วันก่อนไฟล์จะถูกย้ายไปที่เก็บสำรอง';

echo em_eyebrow('แจ้งเตือนจากสตูดิโอ');
echo em_h1('อย่าลืมเก็บรูปไว้ในเครื่องด้วยนะคะ');
echo em_p('สวัสดีค่ะคุณ <strong>' . e($name ?? 'ลูกค้า') . '</strong><br>'
        . 'ระบบพบว่าอัลบั้ม <strong>' . e($album_title ?? '') . '</strong> ('
        . fmt_num((int) ($photo_count ?? 0)) . ' รูป) ยังไม่ได้ถูกดาวน์โหลดเลยค่ะ');

echo em_note('ไฟล์ขนาดเต็มจะเปิดให้ดาวน์โหลดอีก <strong>'
    . (int) ($days_left ?? 30) . ' วัน</strong> หลังจากนั้นจะย้ายไปที่เก็บสำรอง '
    . 'และต้องแจ้งทีมงานให้ดึงกลับมาซึ่งใช้เวลา 1–2 วัน');

echo em_button('ดาวน์โหลดรูปทั้งหมด', $album_url ?? url('albums.php'));
echo em_p('<span style="font-size:13px;color:#7C7267;">ถ้าดาวน์โหลดเรียบร้อยแล้วหรือไม่ต้องการรับการแจ้งเตือนนี้ '
    . 'ตอบกลับอีเมลฉบับนี้ได้เลยค่ะ</span>');
