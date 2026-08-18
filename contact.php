<?php
/** ติดต่อ — the enquiry form plus every other way to reach the studio. */

require_once __DIR__ . '/lib.php';

$active = 'contact.php';
track_visit();

// Arriving from a service card pre-selects that job type.
if (!empty($_GET['job'])) {
    boot_session();
    $_SESSION['contact_old']['job_type'] = (string) $_GET['job'];
}

$faqs = db()->query(
    "SELECT * FROM faqs WHERE status = 'published' ORDER BY sort_order LIMIT 4"
)->fetchAll();

$page_title = 'ติดต่อเรา';
$page_desc  = 'สอบถามคิวงานและราคาถ่ายภาพเด็กกับสายแบ้วสตูดิโอ';
include __DIR__ . '/inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; ติดต่อ</div>
    <h1>สอบถามคิวงาน</h1>
    <p>แจ้งวันที่ สถานที่ และประเภทงาน ทีมงานตอบกลับภายใน 1 วันทำการ</p>
  </div>
</section>

<?php include __DIR__ . '/inc/contact-band.php'; ?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-4">
      <a class="card card--hover text-center" href="<?= e(setting('contact_line_url', '#')) ?>" target="_blank" rel="noopener">
        <div class="why-card__icon" style="margin:0 auto 12px;background:rgba(6,199,85,.12);color:var(--line-green);">
          <?= icon('line', '', 26) ?>
        </div>
        <h3 class="card-title">LINE</h3>
        <p class="card-text"><?= e(setting('contact_line')) ?></p>
      </a>

      <a class="card card--hover text-center" href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone'))) ?>">
        <div class="why-card__icon" style="margin:0 auto 12px;"><?= icon('phone', '', 26) ?></div>
        <h3 class="card-title">โทรศัพท์</h3>
        <p class="card-text"><?= e(setting('contact_phone')) ?></p>
      </a>

      <a class="card card--hover text-center" href="mailto:<?= e(setting('contact_email')) ?>">
        <div class="why-card__icon" style="margin:0 auto 12px;"><?= icon('mail', '', 26) ?></div>
        <h3 class="card-title">อีเมล</h3>
        <p class="card-text"><?= e(setting('contact_email')) ?></p>
      </a>

      <div class="card text-center">
        <div class="why-card__icon" style="margin:0 auto 12px;"><?= icon('clock', '', 26) ?></div>
        <h3 class="card-title">เวลาทำการ</h3>
        <p class="card-text"><?= e(setting('contact_hours')) ?></p>
      </div>
    </div>
  </div>
</section>

<?php if ($faqs): ?>
<section class="section section--cream">
  <div class="wrap">
    <div class="section-head"><h2>คำถามที่ถามบ่อยก่อนจอง</h2></div>
    <div class="faq-grid">
      <?php foreach ($faqs as $f): ?>
        <div class="faq-item">
          <button class="faq-q" type="button" aria-expanded="false">
            <span><?= e($f['question']) ?></span><span class="faq-q__plus" aria-hidden="true"></span>
          </button>
          <div class="faq-a"><div><p><?= nl2br(e($f['answer'])) ?></p></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer.php'; ?>
