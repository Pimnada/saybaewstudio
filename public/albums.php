<?php
/** ผลงาน — every published album, filterable by category. */

require_once __DIR__ . '/../lib.php';

$active = 'albums.php';
track_visit();

$perPage = max(6, (int) setting('albums_per_page', '12'));
$page    = max(1, (int) ($_GET['p'] ?? 1));
$cat     = trim((string) ($_GET['cat'] ?? ''));

$categories = db()->query(
    "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order"
)->fetchAll();

$where  = ["a.status = 'published'", "a.type = 'photo'"];
$params = [];
if ($cat !== '') {
    $where[]  = 'c.slug = ?';
    $params[] = $cat;
}
$whereSql = implode(' AND ', $where);

$st = db()->prepare(
    "SELECT COUNT(*) FROM albums a LEFT JOIN categories c ON c.id = a.category_id WHERE $whereSql"
);
$st->execute($params);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$st = db()->prepare(
    "SELECT a.*, c.name AS category_name, c.slug AS category_slug
       FROM albums a LEFT JOIN categories c ON c.id = a.category_id
      WHERE $whereSql
      ORDER BY a.is_featured DESC, a.event_date DESC, a.id DESC
      LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$st->execute($params);
$albums = $st->fetchAll();

$page_title = 'ผลงาน';
$page_desc  = 'อัลบั้มผลงานถ่ายภาพเด็กและงานกิจกรรมของสายแบ้วสตูดิโอ';
include __DIR__ . '/../inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; ผลงาน</div>
    <h1>อัลบั้มผลงานทั้งหมด</h1>
    <p>ทั้งหมด <?= fmt_num($total) ?> อัลบั้ม — กดเข้าไปดูรูปเต็มและดาวน์โหลดไฟล์ได้</p>
  </div>
</section>

<section class="section">
  <div class="wrap">

    <div class="filter-bar">
      <a class="filter-pill <?= $cat === '' ? 'is-active' : '' ?>" href="<?= e(url('albums.php')) ?>">ทั้งหมด</a>
      <?php foreach ($categories as $c): ?>
        <a class="filter-pill <?= $cat === $c['slug'] ? 'is-active' : '' ?>"
           href="<?= e(url('albums.php?cat=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$albums): ?>
      <div class="empty-state">
        <?= icon('images', '', 56) ?>
        <p>ยังไม่มีอัลบั้มในหมวดนี้</p>
        <a class="btn btn--ghost mt-16" href="<?= e(url('albums.php')) ?>">ดูอัลบั้มทั้งหมด</a>
      </div>
    <?php else: ?>
      <div class="album-list">
        <?php foreach ($albums as $a): ?>
          <a class="album-card" href="<?= e(url('album.php?slug=' . urlencode($a['slug']))) ?>" data-reveal>
            <div class="album-card__media">
              <img src="<?= e(album_cover($a)) ?>" alt="<?= e($a['title']) ?>" loading="lazy" width="420" height="262">
              <?php if ($a['category_name']): ?>
                <span class="album-card__badge"><?= e($a['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="album-card__body">
              <h3 class="album-card__title"><?= e($a['title']) ?></h3>
              <div class="album-card__date"><?= e(thai_date($a['event_date'])) ?></div>
              <div class="album-card__meta">
                <span><?= icon('image', '', 14) ?><?= fmt_num(album_photo_count((int) $a['id'])) ?> รูป</span>
                <span><?= icon('video', '', 14) ?><?= fmt_num(album_video_count((int) $a['id'])) ?> วิดีโอ</span>
                <span><?= icon('eye', '', 14) ?><?= fmt_num((int) $a['views']) ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pager">
          <?php
          $q = $cat !== '' ? 'cat=' . urlencode($cat) . '&' : '';
          for ($i = 1; $i <= $pages; $i++):
              if ($i === $page): ?>
                <span class="is-current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url('albums.php?' . $q . 'p=' . $i)) ?>"><?= $i ?></a>
              <?php endif;
          endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
