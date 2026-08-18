<?php
/**
 * One album: the gallery a customer is sent a link to.
 *
 * Handles the three access modes — public, code-protected and hidden — plus
 * per-photo and bulk download, folder filtering and the share link.
 */

require_once __DIR__ . '/../auth.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$st = db()->prepare(
    "SELECT a.*, c.name AS category_name
       FROM albums a LEFT JOIN categories c ON c.id = a.category_id
      WHERE a.slug = ? LIMIT 1"
);
$st->execute([$slug]);
$album = $st->fetch();

if (!$album || $album['status'] !== 'published') {
    http_response_code(404);
    $page_title = 'ไม่พบอัลบั้ม';
    include __DIR__ . '/../inc/header.php';
    echo '<section class="section"><div class="wrap empty-state">' . icon('images', '', 56)
       . '<h2>ไม่พบอัลบั้มนี้</h2><p>อัลบั้มอาจถูกปิดหรือย้ายไปแล้ว</p>'
       . '<a class="btn btn--primary mt-16" href="' . e(url('albums.php')) . '">ดูอัลบั้มทั้งหมด</a></div></section>';
    include __DIR__ . '/../inc/footer.php';
    exit;
}

$albumId = (int) $album['id'];

// --- access code -------------------------------------------------------------
boot_session();
$needsCode = $album['access'] === 'code' && $album['access_code'] !== '';
$unlocked  = !$needsCode || (($_SESSION['album_ok'][$albumId] ?? false) === true);

if ($needsCode && is_post() && isset($_POST['access_code'])) {
    csrf_check();
    if (hash_equals((string) $album['access_code'], trim((string) $_POST['access_code']))) {
        $_SESSION['album_ok'][$albumId] = true;
        redirect('album.php?slug=' . urlencode($slug));
    }
    $codeError = 'รหัสเข้าชมไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
}

if (!$unlocked) {
    $page_title = $album['title'];
    include __DIR__ . '/../inc/header.php';
    ?>
    <section class="section">
      <div class="wrap wrap--narrow">
        <div class="card card--pad-lg text-center" style="max-width:440px;margin:0 auto;">
          <div class="why-card__icon" style="margin:0 auto 16px;"><?= icon('lock', '', 28) ?></div>
          <h2><?= e($album['title']) ?></h2>
          <p class="text-muted">อัลบั้มนี้เป็นแบบส่วนตัว กรุณาใส่รหัสเข้าชมที่ได้รับจากทีมงาน</p>
          <?php if (!empty($codeError)): ?>
            <div class="alert alert--error"><?= icon('help', '', 20) ?><span><?= e($codeError) ?></span></div>
          <?php endif; ?>
          <form method="post" class="mt-16">
            <?= csrf_field() ?>
            <div class="field">
              <input type="text" name="access_code" placeholder="รหัสเข้าชม" required autofocus
                     style="text-align:center;letter-spacing:2px;">
            </div>
            <button class="btn btn--primary btn--block" type="submit">เข้าชมอัลบั้ม</button>
          </form>
        </div>
      </div>
    </section>
    <?php
    include __DIR__ . '/../inc/footer.php';
    exit;
}

// --- content -----------------------------------------------------------------
track_visit($albumId);
db()->prepare('UPDATE albums SET views = views + 1 WHERE id = ?')->execute([$albumId]);

$folders = db()->prepare('SELECT * FROM folders WHERE album_id = ? ORDER BY sort_order, id');
$folders->execute([$albumId]);
$folders = $folders->fetchAll();

$folderId = isset($_GET['folder']) ? (int) $_GET['folder'] : 0;

$sql    = 'SELECT * FROM photos WHERE album_id = ?';
$params = [$albumId];
if ($folderId > 0) {
    $sql     .= ' AND folder_id = ?';
    $params[] = $folderId;
}
$sql .= ' ORDER BY sort_order, id';

$ps = db()->prepare($sql);
$ps->execute($params);
$photos = $ps->fetchAll();

$videos = db()->prepare("SELECT * FROM videos WHERE album_id = ? AND status = 'published' ORDER BY sort_order");
$videos->execute([$albumId]);
$videos = $videos->fetchAll();

$totalPhotos = album_photo_count($albumId);
$shareUrl    = url('album.php?slug=' . urlencode($album['slug']));
$canDownload = $album['allow_download'] && setting('download_enabled', '1') === '1';

$page_title = $album['title'];
$page_desc  = excerpt($album['description'], 160);
$og_image   = album_cover($album, 'preview');
include __DIR__ . '/../inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs">
      <a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp;
      <a href="<?= e(url('albums.php')) ?>">ผลงาน</a> &nbsp;›&nbsp; <?= e($album['title']) ?>
    </div>
    <h1><?= e($album['title']) ?></h1>
    <p><?= e($album['description']) ?></p>

    <div class="row row--wrap" style="justify-content:center;gap:18px;margin-top:16px;font-size:13.5px;color:var(--on-dark-mute);">
      <span class="row" style="gap:6px;"><?= icon('calendar', '', 16) ?><?= e(thai_date($album['event_date'])) ?></span>
      <span class="row" style="gap:6px;"><?= icon('image', '', 16) ?><?= fmt_num($totalPhotos) ?> รูป</span>
      <span class="row" style="gap:6px;"><?= icon('video', '', 16) ?><?= fmt_num(count($videos)) ?> วิดีโอ</span>
      <span class="row" style="gap:6px;"><?= icon('eye', '', 16) ?><?= fmt_num((int) $album['views']) ?> เข้าชม</span>
    </div>

    <div class="row row--wrap" style="justify-content:center;margin-top:20px;">
      <?php if ($canDownload && $photos): ?>
        <a class="btn btn--primary" href="<?= e(url('dl.php?album=' . $albumId . ($folderId ? '&folder=' . $folderId : ''))) ?>">
          <?= icon('download', '', 18) ?> ดาวน์โหลดทั้งอัลบั้ม (ZIP)
        </a>
      <?php endif; ?>
      <button class="btn btn--light" type="button" data-copy="<?= e($shareUrl) ?>">
        <?= icon('link', '', 18) ?> คัดลอกลิงก์แชร์
      </button>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">

    <?php if ($folders): ?>
      <div class="filter-bar">
        <a class="filter-pill <?= $folderId === 0 ? 'is-active' : '' ?>"
           href="<?= e(url('album.php?slug=' . urlencode($album['slug']))) ?>">
          ทั้งหมด (<?= fmt_num($totalPhotos) ?>)
        </a>
        <?php foreach ($folders as $f):
            $fc = db()->prepare('SELECT COUNT(*) FROM photos WHERE folder_id = ?');
            $fc->execute([$f['id']]);
            $fc = (int) $fc->fetchColumn();
        ?>
          <a class="filter-pill <?= $folderId === (int) $f['id'] ? 'is-active' : '' ?>"
             href="<?= e(url('album.php?slug=' . urlencode($album['slug']) . '&folder=' . $f['id'])) ?>">
            <?= e($f['name']) ?> (<?= fmt_num($fc) ?>)
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$photos): ?>
      <div class="empty-state"><?= icon('image', '', 56) ?><p>ยังไม่มีรูปในอัลบั้มนี้</p></div>
    <?php else: ?>
      <div class="photo-grid">
        <?php foreach ($photos as $p): ?>
          <div class="photo-cell"
               data-lb-src="<?= e(photo_url($p, 'preview')) ?>"
               data-lb-caption="<?= e($p['caption'] ?: $album['title']) ?>">
            <img src="<?= e(photo_url($p, 'thumb')) ?>" alt="<?= e($p['caption'] ?: $album['title']) ?>"
                 loading="lazy" width="360" height="240">
            <?php if ($canDownload): ?>
              <div class="photo-cell__actions">
                <a class="photo-cell__btn" href="<?= e(url('dl.php?photo=' . $p['id'])) ?>"
                   title="ดาวน์โหลดไฟล์ขนาดเต็ม" download><?= icon('download', '', 16) ?></a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($videos): ?>
      <h2 class="mt-32 mb-16">วิดีโอในอัลบั้มนี้</h2>
      <div class="grid grid-3">
        <?php foreach ($videos as $v): ?>
          <div class="card" style="padding:0;overflow:hidden;">
            <div class="ratio ratio--16x9">
              <?php if ($v['provider'] === 'youtube' && $v['url']): ?>
                <?php
                preg_match('/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_\-]{6,})/', $v['url'], $m);
                $ytId = $m[1] ?? '';
                ?>
                <iframe src="https://www.youtube-nocookie.com/embed/<?= e($ytId) ?>"
                        title="<?= e($v['title']) ?>" loading="lazy" allowfullscreen
                        style="border:0;"></iframe>
              <?php elseif ($v['filename']): ?>
                <video controls preload="metadata" src="<?= e(upload_url($v['filename'])) ?>"></video>
              <?php endif; ?>
            </div>
            <div style="padding:14px 16px;">
              <div class="fw-700"><?= e($v['title']) ?></div>
              <div class="text-xs text-muted"><?= e($v['duration']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
