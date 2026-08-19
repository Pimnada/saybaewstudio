<?php
/** Dashboard. */

require_once __DIR__ . '/../auth.php';

$admin_title  = 'แดชบอร์ด';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['แดชบอร์ด', null]];
include __DIR__ . '/../inc/admin-head.php';

$pdo = db();

$totalVisits = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE day = ?');
$st->execute([date('Y-m-d')]);
$todayVisits = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE day >= ?');
$st->execute([date('Y-m-d', strtotime('-30 days'))]);
$last30 = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE day >= ? AND day < ?');
$st->execute([date('Y-m-d', strtotime('-60 days')), date('Y-m-d', strtotime('-30 days'))]);
$prev30 = (int) $st->fetchColumn();
$delta  = $prev30 > 0 ? round(($last30 - $prev30) / $prev30 * 100, 1) : null;

$totalDownloads = (int) $pdo->query('SELECT COALESCE(SUM(downloads), 0) FROM albums')->fetchColumn();
$totalAlbums    = (int) $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn();
$totalPhotos    = (int) $pdo->query('SELECT COUNT(*) FROM photos')->fetchColumn();

// Thirty-day visit chart.
$st = $pdo->prepare('SELECT day, COUNT(*) AS n FROM visits WHERE day >= ? GROUP BY day');
$st->execute([date('Y-m-d', strtotime('-29 days'))]);
$byDay = [];
foreach ($st->fetchAll() as $row) {
    $byDay[$row['day']] = (int) $row['n'];
}
$series = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $series[$d] = $byDay[$d] ?? 0;
}
$peak = max(1, max($series));

$recentAlbums = $pdo->query(
    "SELECT a.*, (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS photo_count
       FROM albums a ORDER BY a.updated_at DESC, a.id DESC LIMIT 5"
)->fetchAll();

$recentPhotos = $pdo->query(
    'SELECT * FROM photos ORDER BY id DESC LIMIT 12'
)->fetchAll();

$newMessages    = (int) $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'new'")->fetchColumn();

$recentMessages = $pdo->query(
    'SELECT * FROM messages ORDER BY id DESC LIMIT 5'
)->fetchAll();

$topAlbums = $pdo->query(
    "SELECT title, slug, views FROM albums WHERE status = 'published' ORDER BY views DESC LIMIT 5"
)->fetchAll();
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">สวัสดีค่ะ คุณ<?= e($user['name']) ?></h1>
    <p class="text-sm text-muted mb-0">ภาพรวมของเว็บไซต์ ณ <?= e(thai_datetime(date('Y-m-d H:i:s'))) ?></p>
  </div>
  <div class="row">
    <a class="btn btn--light btn--sm" href="<?= e(url('admin-albums.php')) ?>"><?= icon('images', '', 16) ?> อัลบั้มทั้งหมด</a>
    <a class="btn btn--primary btn--sm" href="<?= e(url('admin-album.php?new=1')) ?>"><?= icon('plus', '', 16) ?> สร้างอัลบั้มใหม่</a>
  </div>
</div>

<div class="stats">
  <div class="stat">
    <div class="stat__label"><?= icon('eye') ?> ผู้เข้าชมทั้งหมด</div>
    <div class="stat__value"><?= fmt_num($totalVisits) ?></div>
    <?php if ($delta !== null): ?>
      <div class="stat__delta stat__delta--<?= $delta >= 0 ? 'up' : 'down' ?>">
        <?= $delta >= 0 ? '+' : '' ?><?= e((string) $delta) ?>% เทียบ 30 วันก่อนหน้า
      </div>
    <?php else: ?>
      <div class="stat__delta text-faint">30 วันล่าสุด <?= fmt_num($last30) ?></div>
    <?php endif; ?>
  </div>

  <div class="stat">
    <div class="stat__label"><?= icon('trend-up') ?> ผู้ชมวันนี้</div>
    <div class="stat__value"><?= fmt_num($todayVisits) ?></div>
    <div class="stat__delta text-faint">30 วันล่าสุด <?= fmt_num($last30) ?> ครั้ง</div>
  </div>

  <div class="stat">
    <div class="stat__label"><?= icon('download') ?> ยอดดาวน์โหลด</div>
    <div class="stat__value"><?= fmt_num($totalDownloads) ?></div>
    <div class="stat__delta text-faint">รวมทุกอัลบั้ม</div>
  </div>

  <div class="stat">
    <div class="stat__label"><?= icon('images') ?> อัลบั้มทั้งหมด</div>
    <div class="stat__value"><?= fmt_num($totalAlbums) ?></div>
    <div class="stat__delta text-faint"><?= fmt_num($totalPhotos) ?> รูปในระบบ</div>
  </div>
</div>

<div class="grid" style="grid-template-columns: minmax(0,1.5fr) minmax(0,1fr); gap:20px; align-items:start;">

  <div>
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">ผู้เข้าชม 30 วันล่าสุด</h2>
        <span class="spacer"></span>
        <a class="btn btn--light btn--sm" href="<?= e(url('admin-stats.php')) ?>">ดูสถิติเต็ม</a>
      </div>
      <div class="panel__body">
        <div class="chart">
          <?php foreach ($series as $day => $n): ?>
            <div class="chart__bar" style="height: <?= max(3, round($n / $peak * 100)) ?>%">
              <span><?= e(thai_date($day)) ?> · <?= fmt_num($n) ?> ครั้ง</span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="row row--between text-xs text-faint mt-8">
          <span><?= e(thai_date(array_key_first($series))) ?></span>
          <span><?= e(thai_date(array_key_last($series))) ?></span>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">รูปที่อัปโหลดล่าสุด</h2>
        <span class="spacer"></span>
        <span class="text-xs text-faint"><?= fmt_num($totalPhotos) ?> รูปทั้งหมด</span>
      </div>
      <?php if (!$recentPhotos): ?>
        <div class="panel__body">
          <div class="empty-state" style="padding:30px 10px;">
            <?= icon('image', '', 48) ?>
            <p>ยังไม่มีรูปในระบบ — สร้างอัลบั้มแล้วอัปโหลดรูปได้เลย</p>
            <a class="btn btn--primary btn--sm" href="<?= e(url('admin-album.php?new=1')) ?>">สร้างอัลบั้มแรก</a>
          </div>
        </div>
      <?php else: ?>
        <div class="ph-grid" data-size="sm">
          <?php foreach ($recentPhotos as $p): ?>
            <a class="ph" href="<?= e(url('admin-album.php?id=' . $p['album_id'])) ?>">
              <div class="ph__media"><img src="<?= e(photo_url($p, 'thumb')) ?>" alt="" loading="lazy"></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">อัลบั้มล่าสุด</h2>
        <span class="spacer"></span>
        <a class="btn btn--light btn--sm" href="<?= e(url('admin-albums.php')) ?>">ทั้งหมด</a>
      </div>
      <div class="panel__body panel__body--flush">
        <?php if (!$recentAlbums): ?>
          <p class="text-sm text-muted" style="padding:20px;">ยังไม่มีอัลบั้ม</p>
        <?php else: ?>
          <table class="tbl">
            <tbody>
            <?php foreach ($recentAlbums as $a): ?>
              <tr>
                <td style="width:70px;">
                  <img class="thumb" src="<?= e(album_cover($a)) ?>" alt="" loading="lazy">
                </td>
                <td>
                  <a class="fw-700" href="<?= e(url('admin-album.php?id=' . $a['id'])) ?>"><?= e($a['title']) ?></a>
                  <div class="text-xs text-faint">
                    <?= e(thai_date($a['event_date'])) ?> · <?= fmt_num((int) $a['photo_count']) ?> รูป
                  </div>
                </td>
                <td style="width:80px;" class="text-right">
                  <span class="badge badge--<?= $a['status'] === 'published' ? 'ok' : 'muted' ?>">
                    <?= $a['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">ข้อความล่าสุด</h2>
        <?php if ($newMessages): ?><span class="badge badge--danger"><?= $newMessages ?> ใหม่</span><?php endif; ?>
        <span class="spacer"></span>
        <a class="btn btn--light btn--sm" href="<?= e(url('admin-messages.php')) ?>">ทั้งหมด</a>
      </div>
      <div class="panel__body panel__body--flush">
        <?php if (!$recentMessages): ?>
          <p class="text-sm text-muted" style="padding:20px;">ยังไม่มีข้อความจากลูกค้า</p>
        <?php else: ?>
          <table class="tbl">
            <tbody>
            <?php foreach ($recentMessages as $m): ?>
              <tr>
                <td>
                  <a class="fw-700" href="<?= e(url('admin-messages.php?id=' . $m['id'])) ?>"><?= e($m['name']) ?></a>
                  <div class="text-xs text-faint"><?= e(excerpt($m['detail'] ?: $m['job_type'], 44)) ?></div>
                </td>
                <td class="text-right text-xs text-faint" style="width:96px;"><?= e(time_ago($m['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head"><h2 class="panel__title">อัลบั้มที่มีคนดูมากที่สุด</h2></div>
      <div class="panel__body panel__body--flush">
        <?php if (!$topAlbums): ?>
          <p class="text-sm text-muted" style="padding:20px;">ยังไม่มีข้อมูล</p>
        <?php else: ?>
          <table class="tbl">
            <tbody>
            <?php foreach ($topAlbums as $a): ?>
              <tr>
                <td><a href="<?= e(url('album.php?slug=' . urlencode($a['slug']))) ?>" target="_blank"><?= e($a['title']) ?></a></td>
                <td class="text-right fw-700" style="width:80px;"><?= fmt_num((int) $a['views']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
