<?php
/** บทความ — the studio's articles. */

require_once __DIR__ . '/../lib.php';

$active = 'blog.php';
track_visit();

$perPage = 9;
$page    = max(1, (int) ($_GET['p'] ?? 1));

$total = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$articles = db()->query(
    "SELECT * FROM articles WHERE status = 'published'
      ORDER BY published_at DESC, id DESC
      LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
)->fetchAll();

$page_title = 'บทความ';
$page_desc  = 'เคล็ดลับการถ่ายภาพเด็กและการเตรียมงานจากสายแบ้วสตูดิโอ';
include __DIR__ . '/../inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; บทความ</div>
    <h1>บทความจากสตูดิโอ</h1>
    <p>เคล็ดลับเตรียมงาน เตรียมลูก และเรื่องเบื้องหลังการถ่ายภาพเด็ก</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$articles): ?>
      <div class="empty-state"><?= icon('article', '', 56) ?><p>ยังไม่มีบทความ</p></div>
    <?php else: ?>
      <div class="grid grid-3">
        <?php foreach ($articles as $a): ?>
          <a class="card card--hover" href="<?= e(url('article.php?slug=' . urlencode($a['slug']))) ?>"
             style="padding:0;overflow:hidden;" data-reveal>
            <div class="ratio ratio--3x2">
              <img src="<?= e($a['cover'] ? upload_url($a['cover']) : url('assets/img/placeholder.svg')) ?>"
                   alt="<?= e($a['title']) ?>" loading="lazy">
            </div>
            <div style="padding:18px 20px 22px;">
              <div class="text-xs text-faint mb-8"><?= e(thai_date($a['published_at'])) ?></div>
              <h3 class="card-title" style="color:var(--ink);"><?= e($a['title']) ?></h3>
              <p class="card-text"><?= e(excerpt($a['excerpt'] ?: $a['body'], 110)) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pager">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="is-current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e(url('blog.php?p=' . $i)) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
