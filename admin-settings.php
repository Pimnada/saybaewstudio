<?php
/** ตั้งค่าเว็บไซต์ — identity, hero copy, homepage sections and SEO. */

require_once __DIR__ . '/auth.php';

$keys = [
    'site_name', 'site_name_en', 'site_tagline', 'site_description',
    'hero_title', 'hero_text', 'hero_cta1_label', 'hero_cta1_url', 'hero_cta2_label', 'hero_cta2_url',
    'about_title', 'about_points',
    'cta_title', 'cta_text',
    'meta_keywords', 'ga_id',
    'albums_per_page', 'stat_years', 'stat_jobs', 'stat_schools', 'stat_photos',
];

if (is_post()) {
    csrf_check();
    foreach ($keys as $k) {
        if (array_key_exists($k, $_POST)) {
            set_setting($k, trim((string) $_POST[$k]));
        }
    }
    set_setting('download_enabled', isset($_POST['download_enabled']) ? '1' : '0');
    set_setting('floating_line',    isset($_POST['floating_line']) ? '1' : '0');

    log_activity('settings.update', 'ตั้งค่าเว็บไซต์');
    flash('บันทึกการตั้งค่าเว็บไซต์แล้ว');
    redirect('admin-settings.php');
}

$admin_title  = 'ตั้งค่าเว็บไซต์';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ตั้งค่าเว็บไซต์', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ตั้งค่าเว็บไซต์</h1>
    <p class="text-sm text-muted mb-0">ข้อความและตัวเลขที่แสดงบนหน้าเว็บสาธารณะ</p>
  </div>
  <a class="btn btn--light" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">
    <?= icon('external', '', 16) ?> ดูหน้าเว็บจริง
  </a>
</div>

<form method="post">
  <?= csrf_field() ?>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">ชื่อและคำอธิบายเว็บไซต์</h2></div>
    <div class="panel__body">
      <div class="grid grid-2" style="gap:0 16px;">
        <div class="field">
          <label for="st-name">ชื่อสตูดิโอ (ภาษาไทย)</label>
          <input id="st-name" type="text" name="site_name" value="<?= e(setting('site_name')) ?>">
        </div>
        <div class="field">
          <label for="st-name-en">ชื่อภาษาอังกฤษ</label>
          <input id="st-name-en" type="text" name="site_name_en" value="<?= e(setting('site_name_en')) ?>">
        </div>
      </div>
      <div class="field">
        <label for="st-tag">คำโปรยสั้น</label>
        <input id="st-tag" type="text" name="site_tagline" value="<?= e(setting('site_tagline')) ?>">
      </div>
      <div class="field mb-0">
        <label for="st-desc">คำอธิบายเว็บไซต์ (ใช้กับ Google และตอนแชร์ลิงก์)</label>
        <textarea id="st-desc" name="site_description" rows="3"><?= e(setting('site_description')) ?></textarea>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">แบนเนอร์หน้าแรก</h2></div>
    <div class="panel__body">
      <div class="field">
        <label for="st-hero-title">หัวข้อใหญ่</label>
        <input id="st-hero-title" type="text" name="hero_title" value="<?= e(setting('hero_title')) ?>">
      </div>
      <div class="field">
        <label for="st-hero-text">ข้อความรอง</label>
        <textarea id="st-hero-text" name="hero_text" rows="3"><?= e(setting('hero_text')) ?></textarea>
      </div>
      <div class="grid grid-4" style="gap:0 16px;">
        <div class="field">
          <label for="st-c1l">ปุ่มหลัก — ข้อความ</label>
          <input id="st-c1l" type="text" name="hero_cta1_label" value="<?= e(setting('hero_cta1_label')) ?>">
        </div>
        <div class="field">
          <label for="st-c1u">ปุ่มหลัก — ลิงก์</label>
          <input id="st-c1u" type="text" name="hero_cta1_url" value="<?= e(setting('hero_cta1_url')) ?>">
        </div>
        <div class="field">
          <label for="st-c2l">ปุ่มรอง — ข้อความ</label>
          <input id="st-c2l" type="text" name="hero_cta2_label" value="<?= e(setting('hero_cta2_label')) ?>">
        </div>
        <div class="field">
          <label for="st-c2u">ปุ่มรอง — ลิงก์</label>
          <input id="st-c2u" type="text" name="hero_cta2_url" value="<?= e(setting('hero_cta2_url')) ?>">
        </div>
      </div>
      <p class="hint mb-0">
        รูปในโมเสกหน้าแรกดึงมาจากอัลบั้มที่เผยแพร่โดยอัตโนมัติ —
        กำหนดเองได้ที่หน้า <a href="<?= e(url('admin-banners.php')) ?>">แบนเนอร์</a>
      </p>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">แถบ "ระบบอัลบั้ม" และแถบชวนติดต่อ</h2></div>
    <div class="panel__body">
      <div class="field">
        <label for="st-about-title">หัวข้อแถบระบบอัลบั้ม</label>
        <input id="st-about-title" type="text" name="about_title" value="<?= e(setting('about_title')) ?>">
      </div>
      <div class="field">
        <label for="st-about-points">รายการติ๊กถูก (บรรทัดละ 1 ข้อ)</label>
        <textarea id="st-about-points" name="about_points" rows="7"><?= e(setting('about_points')) ?></textarea>
      </div>
      <div class="grid grid-2" style="gap:0 16px;">
        <div class="field">
          <label for="st-cta-title">หัวข้อแถบชวนติดต่อ</label>
          <input id="st-cta-title" type="text" name="cta_title" value="<?= e(setting('cta_title')) ?>">
        </div>
        <div class="field">
          <label for="st-cta-text">ข้อความรอง</label>
          <input id="st-cta-text" type="text" name="cta_text" value="<?= e(setting('cta_text')) ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">ตัวเลขบนหน้าเกี่ยวกับเรา</h2></div>
    <div class="panel__body">
      <div class="grid grid-4" style="gap:0 16px;">
        <div class="field">
          <label for="st-y">ปีที่ให้บริการ</label>
          <input id="st-y" type="text" name="stat_years" value="<?= e(setting('stat_years', '8')) ?>">
        </div>
        <div class="field">
          <label for="st-j">งานที่ถ่ายแล้ว</label>
          <input id="st-j" type="text" name="stat_jobs" value="<?= e(setting('stat_jobs', '1,200')) ?>">
        </div>
        <div class="field">
          <label for="st-s">โรงเรียนที่ไว้ใจ</label>
          <input id="st-s" type="text" name="stat_schools" value="<?= e(setting('stat_schools', '45')) ?>">
        </div>
        <div class="field">
          <label for="st-p">รูปที่ส่งมอบ</label>
          <input id="st-p" type="text" name="stat_photos" value="<?= e(setting('stat_photos', '380,000')) ?>">
        </div>
      </div>
      <p class="hint mb-0">ใส่ตัวเลขล้วนได้เลย ระบบจะเติมเครื่องหมาย + ให้เอง</p>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">พฤติกรรมของเว็บไซต์</h2></div>
    <div class="panel__body">
      <label class="check mb-16">
        <input type="checkbox" name="download_enabled" <?= setting('download_enabled', '1') === '1' ? 'checked' : '' ?>>
        <span><strong>เปิดให้ดาวน์โหลดไฟล์ขนาดเต็ม</strong><br>
          <span class="text-xs text-muted">ปิดสวิตช์นี้จะปิดการดาวน์โหลดทุกอัลบั้มพร้อมกัน</span></span>
      </label>
      <label class="check mb-16">
        <input type="checkbox" name="floating_line" <?= setting('floating_line', '1') === '1' ? 'checked' : '' ?>>
        <span><strong>แสดงปุ่ม LINE ลอยมุมจอ</strong></span>
      </label>
      <div class="grid grid-2" style="gap:0 16px;">
        <div class="field">
          <label for="st-per">จำนวนอัลบั้มต่อหน้า</label>
          <input id="st-per" type="number" name="albums_per_page" min="6" max="48"
                 value="<?= e(setting('albums_per_page', '12')) ?>">
        </div>
        <div class="field">
          <label for="st-ga">Google Analytics ID</label>
          <input id="st-ga" type="text" name="ga_id" value="<?= e(setting('ga_id')) ?>" placeholder="G-XXXXXXXXXX">
          <p class="hint">เว้นว่างไว้ถ้ายังไม่ใช้ — ระบบมีสถิติของตัวเองอยู่แล้ว</p>
        </div>
      </div>
      <div class="field mb-0">
        <label for="st-kw">คำค้นหา (keywords)</label>
        <input id="st-kw" type="text" name="meta_keywords" value="<?= e(setting('meta_keywords')) ?>">
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel__foot" style="border-top:0;">
      <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกการตั้งค่า</button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
