<?php
/** ข้อมูลการติดต่อ — phone, LINE, socials, opening hours. */

require_once __DIR__ . '/../auth.php';

$keys = [
    'contact_phone', 'contact_email', 'contact_line', 'contact_line_url',
    'contact_facebook', 'contact_facebook_url', 'contact_instagram_url',
    'contact_youtube_url', 'contact_tiktok_url',
    'contact_address', 'contact_hours', 'contact_map', 'notify_email',
];

if (is_post()) {
    csrf_check();
    foreach ($keys as $k) {
        if (array_key_exists($k, $_POST)) {
            set_setting($k, trim((string) $_POST[$k]));
        }
    }
    log_activity('settings.contact', 'ข้อมูลติดต่อ');
    flash('บันทึกข้อมูลการติดต่อแล้ว');
    redirect('admin-contact.php');
}

$admin_title  = 'ข้อมูลการติดต่อ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ข้อมูลการติดต่อ', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ข้อมูลการติดต่อ</h1>
    <p class="text-sm text-muted mb-0">แสดงในฟุตเตอร์ แถบติดต่อ หน้าติดต่อ และท้ายอีเมลทุกฉบับ</p>
  </div>
</div>

<form method="post">
  <?= csrf_field() ?>

  <div class="grid grid-2" style="align-items:start;">

    <div class="panel">
      <div class="panel__head"><h2 class="panel__title">ช่องทางหลัก</h2></div>
      <div class="panel__body">
        <div class="field">
          <label for="ct-phone"><?= icon('phone', '', 15) ?> เบอร์โทรศัพท์</label>
          <input id="ct-phone" type="text" name="contact_phone" value="<?= e(setting('contact_phone')) ?>">
        </div>
        <div class="field">
          <label for="ct-email"><?= icon('mail', '', 15) ?> อีเมล</label>
          <input id="ct-email" type="email" name="contact_email" value="<?= e(setting('contact_email')) ?>">
        </div>
        <div class="field">
          <label for="ct-line"><?= icon('line', '', 15) ?> LINE ID</label>
          <input id="ct-line" type="text" name="contact_line" value="<?= e(setting('contact_line')) ?>">
        </div>
        <div class="field">
          <label for="ct-line-url">ลิงก์เพิ่มเพื่อน LINE</label>
          <input id="ct-line-url" type="url" name="contact_line_url" value="<?= e(setting('contact_line_url')) ?>"
                 placeholder="https://line.me/R/ti/p/@saybaewstudio">
          <p class="hint">ปุ่ม LINE ทุกปุ่มบนเว็บชี้ไปที่ลิงก์นี้</p>
        </div>
        <div class="field mb-0">
          <label for="ct-hours"><?= icon('clock', '', 15) ?> เวลาเปิดทำการ</label>
          <input id="ct-hours" type="text" name="contact_hours" value="<?= e(setting('contact_hours')) ?>">
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head"><h2 class="panel__title">โซเชียลมีเดีย</h2></div>
      <div class="panel__body">
        <div class="field">
          <label for="ct-fb"><?= icon('facebook', '', 15) ?> ชื่อเพจ Facebook</label>
          <input id="ct-fb" type="text" name="contact_facebook" value="<?= e(setting('contact_facebook')) ?>">
        </div>
        <div class="field">
          <label for="ct-fb-url">ลิงก์เพจ Facebook</label>
          <input id="ct-fb-url" type="url" name="contact_facebook_url" value="<?= e(setting('contact_facebook_url')) ?>">
        </div>
        <div class="field">
          <label for="ct-ig"><?= icon('instagram', '', 15) ?> ลิงก์ Instagram</label>
          <input id="ct-ig" type="url" name="contact_instagram_url" value="<?= e(setting('contact_instagram_url')) ?>">
        </div>
        <div class="field">
          <label for="ct-yt"><?= icon('youtube', '', 15) ?> ลิงก์ YouTube</label>
          <input id="ct-yt" type="url" name="contact_youtube_url" value="<?= e(setting('contact_youtube_url')) ?>">
        </div>
        <div class="field mb-0">
          <label for="ct-tt"><?= icon('tiktok', '', 15) ?> ลิงก์ TikTok</label>
          <input id="ct-tt" type="url" name="contact_tiktok_url" value="<?= e(setting('contact_tiktok_url')) ?>">
        </div>
        <p class="hint">เว้นว่างช่องไหนไว้ ไอคอนของช่องทางนั้นจะไม่แสดงในฟุตเตอร์</p>
      </div>
    </div>

  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">ที่อยู่และการแจ้งเตือน</h2></div>
    <div class="panel__body">
      <div class="field">
        <label for="ct-addr"><?= icon('map-pin', '', 15) ?> ที่อยู่</label>
        <input id="ct-addr" type="text" name="contact_address" value="<?= e(setting('contact_address')) ?>">
      </div>
      <div class="field">
        <label for="ct-map">Google Maps — โค้ดฝัง (embed)</label>
        <textarea id="ct-map" name="contact_map" rows="3"
                  placeholder="<iframe src=&quot;https://www.google.com/maps/embed?...&quot;></iframe>"><?= e(setting('contact_map')) ?></textarea>
      </div>
      <div class="field mb-0" style="max-width:420px;">
        <label for="ct-notify">อีเมลที่รับแจ้งเตือนข้อความใหม่</label>
        <input id="ct-notify" type="email" name="notify_email" value="<?= e(setting('notify_email')) ?>">
        <p class="hint">ทุกครั้งที่มีลูกค้ากรอกแบบฟอร์มติดต่อ ระบบจะส่งแจ้งเตือนมาที่อีเมลนี้</p>
      </div>
    </div>
    <div class="panel__foot">
      <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกข้อมูลติดต่อ</button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
