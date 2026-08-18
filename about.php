<?php
/** เกี่ยวกับเรา — driven by the "about" row in pages. */

require_once __DIR__ . '/lib.php';

$active = 'about.php';
track_visit();

$st = db()->prepare("SELECT * FROM pages WHERE slug = 'about' AND status = 'published'");
$st->execute();
$page = $st->fetch();

$stats = [
    'ปีที่ให้บริการ'   => setting('stat_years', '8') . '+',
    'งานที่ถ่ายแล้ว'   => setting('stat_jobs', '1,200') . '+',
    'โรงเรียนที่ไว้ใจ' => setting('stat_schools', '45') . '+',
    'รูปที่ส่งมอบ'     => setting('stat_photos', '380,000') . '+',
];

$photos = db()->query(
    "SELECT p.* FROM photos p JOIN albums a ON a.id = p.album_id
      WHERE a.status = 'published' ORDER BY p.id LIMIT 6"
)->fetchAll();

$page_title = $page['title'] ?? 'เกี่ยวกับเรา';
$page_desc  = $page['meta_description'] ?? '';
include __DIR__ . '/inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; เกี่ยวกับเรา</div>
    <h1><?= e($page['title'] ?? 'เกี่ยวกับเรา') ?></h1>
    <p><?= e(setting('site_tagline')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="grid grid-4">
      <?php foreach ($stats as $label => $value): ?>
        <div class="card text-center" data-reveal>
          <div style="font-size:30px;font-weight:700;color:var(--gold);line-height:1.2;"><?= e($value) ?></div>
          <div class="text-sm text-muted"><?= e($label) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--cream section--tight">
  <div class="wrap wrap--narrow">
    <div class="prose"><?= $page['body'] ?? '<p>ยังไม่มีเนื้อหา</p>' ?></div>
  </div>
</section>

<?php if ($photos): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head"><h2>บรรยากาศการทำงานของเรา</h2></div>
    <div class="photo-grid">
      <?php foreach ($photos as $p): ?>
        <div class="photo-cell" data-lb-src="<?= e(photo_url($p, 'preview')) ?>"
             data-lb-caption="<?= e($p['caption'] ?: setting('site_name')) ?>">
          <img src="<?= e(photo_url($p, 'thumb')) ?>" alt="" loading="lazy" width="360" height="240">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/inc/contact-band.php'; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
