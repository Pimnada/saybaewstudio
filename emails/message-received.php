<?php
/**
 * Auto-acknowledgement to the customer right after they submit the form.
 * Vars: name, job_type, event_date, detail
 */
require_once __DIR__ . '/_parts.php';

$subject   = 'ได้รับข้อความของคุณแล้ว — สายแบ้วสตูดิโอ';
$preheader = 'ทีมงานจะติดต่อกลับภายใน 1 วันทำการ';

echo em_eyebrow('ยืนยันการรับข้อความ');
echo em_h1('ขอบคุณที่ติดต่อสายแบ้วสตูดิโอ');
echo em_p('สวัสดีค่ะคุณ <strong>' . e($name ?? 'ลูกค้า') . '</strong><br>'
        . 'เราได้รับข้อความของคุณเรียบร้อยแล้ว ทีมงานจะตรวจสอบคิวว่างและติดต่อกลับ '
        . '<strong>ภายใน 1 วันทำการ</strong> ทางเบอร์โทรหรือ LINE ที่คุณแจ้งไว้');

echo em_panel(
      em_row('ประเภทงาน', $job_type ?? '')
    . em_row('วันที่จัดงาน', !empty($event_date) ? thai_date($event_date) : 'ยังไม่ระบุ')
    . em_row('ข้อความของคุณ', $detail ?? '')
);

echo em_note('หากต้องการคุยเร็วกว่านี้ ทักไลน์ <strong>'
    . e(setting('contact_line', '@saybaewstudio'))
    . '</strong> ได้เลย ทีมงานตอบไวที่สุดในช่องทางนี้');

echo em_button('ดูตัวอย่างผลงานทั้งหมด', url('albums.php'));
echo em_button_ghost('อ่านคำถามที่พบบ่อย', url('index.php#faq'));
