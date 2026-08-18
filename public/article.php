<?php
/** One article. */

require_once __DIR__ . '/../lib.php';

$active = 'blog.php';

$st = db()->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published'");
$st->execute([trim((string) ($_GET['slug'] ?? ''))]);
$article = $st->fetch();

if (!$article) {
    http_response_code(404);
    $page_title = 'ไม่พบบทความ';
    include __DIR__ . '/../inc/header.php';
    echo '<section class="section"><div class="wrap empty-state">' . icon('article', '', 56)
       . '<h2>ไม่พบบทความนี้</h2>'
       . '<a class="btn btn--primary mt-16" href="' . e(url('blog.php')) . '">ดูบทความทั้งหมด</a></div></section>';
    include __DIR__ . '/../inc/footer.php';
    exit;
}

track_visit();
db()->prepare('UPDATE articles SET views = views + 1 WHERE id = ?')->execute([$article['id']]);

$more = db()->prepare(
    "SELECT * FROM articles WHERE status = 'published' AND id <> ? ORDER BY published_at DESC LIMIT 3"
);
$more->execute([$article['id']]);
$more = $more->fetchAll();

$page_title = $article['title'];
$page_desc  = excerpt($article['excerpt'] ?: $article['body'], 160);
$og_image   = $article['cover'] ? upload_url($article['cover']) : null;
include __DIR__ . '/../inc/header.php';
?>

<section class="page-head">
  <div class="wrap wrap--narrow">
    <div class="crumbs">
      <a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp;
      <a href="<?= e(url('blog.php')) ?>">บทความ</a>
    </div>
    <h1><?= e($article['title']) ?></h1>
    <p><?= e(thai_date($article['published_at'])) ?> · อ่านแล้ว <?= fmt_num((int) $article['views']) ?> ครั้ง</p>
  </div>
</section>

<section class="section">
  <div class="wrap wrap--narrow">
    <?php if ($article['cover']): ?>
      <img src="<?= e(upload_url($article['cover'])) ?>" alt="<?= e($article['title']) ?>"
           style="border-radius:var(--r);margin-bottom:28px;" width="860" height="520">
    <?php endif; ?>

    <div class="article-body"><?= $article['body'] ?></div>

    <div class="row mt-32" style="gap:10px;">
      <a class="btn btn--ghost btn--sm" href="<?= e(url('blog.php')) ?>"><?= icon('arrow-left', '', 16) ?> บทความทั้งหมด</a>
      <button class="btn btn--light btn--sm" type="button"
              data-copy="<?= e(url('article.php?slug=' . urlencode($article['slug']))) ?>">
        <?= icon('link', '', 16) ?> คัดลอกลิงก์
      </button>
    </div>
  </div>
</section>

<?php if ($more): ?>
<section class="section section--cream">
  <div class="wrap">
    <div class="section-head"><h2>บทความอื่นที่น่าสนใจ</h2></div>
    <div class="grid grid-3">
      <?php foreach ($more as $m): ?>
        <a class="card card--hover" href="<?= e(url('article.php?slug=' . urlencode($m['slug']))) ?>">
          <div class="text-xs text-faint mb-8"><?= e(thai_date($m['published_at'])) ?></div>
          <h3 class="card-title" style="color:var(--ink);"><?= e($m['title']) ?></h3>
          <p class="card-text"><?= e(excerpt($m['excerpt'] ?: $m['body'], 90)) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../inc/footer.php'; ?>
