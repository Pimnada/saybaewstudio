<?php
/**
 * The dark "ติดต่อ" band with the enquiry form. Shared by the homepage and
 * contact.php so there is only one copy of this form to keep in step.
 *
 * The form posts to contact-submit.php, which stores the message, mails the
 * team and bounces back here with a flash.
 */

require_once __DIR__ . '/../auth.php';

$jobTypes = db()->query(
    "SELECT title FROM services WHERE status = 'published' ORDER BY sort_order"
)->fetchAll(PDO::FETCH_COLUMN);

$flashes = take_flash();
$old     = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_old']);
?>
<section class="contact-band" id="contact">
  <div class="wrap contact-grid">

    <div class="contact-panel">
      <h2><?= e(setting('cta_title')) ?></h2>
      <p><?= e(setting('cta_text')) ?></p>

      <div class="contact-list">
        <div class="contact-list__item">
          <span class="contact-list__icon"><?= icon('line', '', 18) ?></span>
          <span>
            <span class="contact-list__label">LINE</span>
            <span class="contact-list__value"><?= e(setting('contact_line')) ?></span>
          </span>
        </div>
        <div class="contact-list__item">
          <span class="contact-list__icon"><?= icon('facebook', '', 18) ?></span>
          <span>
            <span class="contact-list__label">Facebook</span>
            <span class="contact-list__value"><?= e(setting('contact_facebook')) ?></span>
          </span>
        </div>
        <div class="contact-list__item">
          <span class="contact-list__icon"><?= icon('phone', '', 18) ?></span>
          <span>
            <span class="contact-list__label">โทรศัพท์</span>
            <span class="contact-list__value"><?= e(setting('contact_phone')) ?></span>
          </span>
        </div>
        <div class="contact-list__item">
          <span class="contact-list__icon"><?= icon('clock', '', 18) ?></span>
          <span>
            <span class="contact-list__label">เวลาเปิดทำการ</span>
            <span class="contact-list__value"><?= e(setting('contact_hours')) ?></span>
          </span>
        </div>
      </div>
    </div>

    <div class="contact-form">
      <?php foreach ($flashes as $f): ?>
        <div class="alert alert--<?= e($f['type'] === 'success' ? 'success' : 'error') ?>">
          <?= icon($f['type'] === 'success' ? 'check-circle' : 'help', '', 20) ?>
          <span><?= e($f['message']) ?></span>
        </div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('contact-submit.php')) ?>" data-guard>
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="<?= e(current_path()) ?>">

        <!-- Bots fill this in; people never see it. -->
        <div class="sr-only" aria-hidden="true">
          <label>อย่ากรอกช่องนี้ <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="grid grid-2">
          <div class="field">
            <label for="cf-name">ชื่อ <span class="req">*</span></label>
            <input id="cf-name" type="text" name="name" placeholder="คุณกรอกชื่อ" required
                   maxlength="160" value="<?= e($old['name'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="cf-phone">เบอร์โทร <span class="req">*</span></label>
            <input id="cf-phone" type="tel" name="phone" placeholder="กรุณากรอกเบอร์โทร" required
                   maxlength="40" value="<?= e($old['phone'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="cf-job">ประเภทงาน</label>
            <select id="cf-job" name="job_type">
              <option value="">เลือกประเภทงาน</option>
              <?php foreach ($jobTypes as $t): ?>
                <option value="<?= e($t) ?>" <?= ($old['job_type'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="cf-date">วันที่จัดงาน</label>
            <input id="cf-date" type="date" name="event_date" value="<?= e($old['event_date'] ?? '') ?>">
          </div>
        </div>

        <div class="field">
          <label for="cf-detail">รายละเอียดเพิ่มเติม</label>
          <textarea id="cf-detail" name="detail" rows="3" maxlength="2000"
                    placeholder="เล่ารายละเอียดเกี่ยวกับงานของคุณ"><?= e($old['detail'] ?? '') ?></textarea>
        </div>

        <button class="btn btn--primary btn--block" type="submit">ส่งข้อความถึงทีมงาน</button>
      </form>
    </div>

  </div>
</section>
