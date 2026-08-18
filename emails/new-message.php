<?php
/**
 * To the studio team, the moment a contact form is submitted.
 * Vars: name, phone, email, job_type, event_date, detail, message_id
 */
require_once __DIR__ . '/_parts.php';

$subject   = 'ข้อความใหม่จาก ' . ($name ?? 'ลูกค้า') . ' — สอบถามคิวงาน';
$preheader = ($job_type ?? 'สอบถามงาน') . ' · ' . excerpt($detail ?? '', 80);

echo em_eyebrow('ข้อความใหม่จากเว็บไซต์');
echo em_h1('มีลูกค้าสอบถามคิวงานเข้ามา');
echo em_p('ลูกค้ากรอกแบบฟอร์มติดต่อบนหน้าเว็บเมื่อ <strong>' . e(thai_datetime(date('Y-m-d H:i:s'))) . '</strong> รายละเอียดตามด้านล่าง');

echo em_panel(
      em_row('ชื่อผู้ติดต่อ', $name ?? '')
    . em_row('เบอร์โทร', $phone ?? '')
    . em_row('อีเมล', $email ?? '')
    . em_row('ประเภทงาน', $job_type ?? '')
    . em_row('วันที่จัดงาน', !empty($event_date) ? thai_date($event_date) : '')
    . em_row('รายละเอียด', $detail ?? '')
);

echo em_button('เปิดในหน้าแอดมิน', url('admin-messages.php?id=' . (int) ($message_id ?? 0)));
echo em_p('<span style="font-size:13px;color:#7C7267;">ตอบกลับภายใน 1 ชั่วโมงในเวลาทำการช่วยให้ลูกค้าตัดสินใจจองคิวได้เร็วขึ้นมาก</span>');
