<?php
/** รีวิว — every published review. */

require_once __DIR__ . '/lib.php';

$active = 'reviews.php';
track_visit();

$reviews = db()->query(
    "SELECT * FROM reviews WHERE status = 'published' ORDER BY sort_order, id DESC"
)->fetchAll();

$avg = 0.0;
if ($reviews) {
    $avg = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
}

$page_title = 'รีวิวจากลูกค้า';
$page_desc  = 'เสียงจากผู้ปกครองและโรงเรียนที่ใช้บริการสายแบ้วสตูดิโอ';
include __DIR__ . '/inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; รีวิว</div>
    <h1>เสียงจากลูกค้า</h1>
    <p>คะแนนเฉลี่ย <?= e((string) $avg) ?> จาก 5 · <?= fmt_num(count($reviews)) ?> รีวิว</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$reviews): ?>
      <div class="empty-state"><?= icon('star', '', 56) ?><p>ยังไม่มีรีวิว</p></div>
    <?php else: ?>
      <div class="grid grid-3">
        <?php foreach ($reviews as $r): ?>
          <article class="card card--hover" data-reveal>
            <div class="row mb-16">
              <img src="<?= e($r['avatar'] ? upload_url($r['avatar']) : url('assets/img/avatar.svg')) ?>"
                   alt="<?= e($r['name']) ?>" width="46" height="46" loading="lazy"
                   style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:1.5px solid var(--gold);">
              <div>
                <div class="fw-700"><?= e($r['name']) ?></div>
                <div class="text-xs text-muted"><?= e($r['role']) ?></div>
              </div>
            </div>
            <div class="mb-8"><?= star_row((int) $r['rating']) ?></div>
            <p class="card-text"><?= nl2br(e($r['body'])) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/inc/contact-band.php'; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
