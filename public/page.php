<?php
/** Static pages managed from admin-pages.php (privacy, terms, and any others). */

require_once __DIR__ . '/../lib.php';

track_visit();

$st = db()->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$st->execute([trim((string) ($_GET['slug'] ?? ''))]);
$page = $st->fetch();

if (!$page) {
    http_response_code(404);
    $page_title = 'ไม่พบหน้านี้';
    include __DIR__ . '/../inc/header.php';
    echo '<section class="section"><div class="wrap empty-state">' . icon('pages', '', 56)
       . '<h2>ไม่พบหน้านี้</h2>'
       . '<a class="btn btn--primary mt-16" href="' . e(url('index.php')) . '">กลับหน้าแรก</a></div></section>';
    include __DIR__ . '/../inc/footer.php';
    exit;
}

$page_title = $page['title'];
$page_desc  = $page['meta_description'];
include __DIR__ . '/../inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; <?= e($page['title']) ?></div>
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="wrap wrap--narrow">
    <div class="prose"><?= $page['body'] ?></div>
    <p class="text-xs text-faint mt-32">ปรับปรุงล่าสุด <?= e(thai_date($page['updated_at'])) ?></p>
  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
