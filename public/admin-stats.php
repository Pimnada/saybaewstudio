<?php
/** สถิติการเข้าชม. */

require_once __DIR__ . '/../auth.php';

$pdo   = db();
$range = max(7, min(365, (int) ($_GET['days'] ?? 30)));
$from  = date('Y-m-d', strtotime('-' . ($range - 1) . ' days'));

$st = $pdo->prepare('SELECT day, COUNT(*) AS n FROM visits WHERE day >= ? GROUP BY day');
$st->execute([$from]);
$byDay = [];
foreach ($st->fetchAll() as $row) {
    $byDay[$row['day']] = (int) $row['n'];
}

$series = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $series[$d] = $byDay[$d] ?? 0;
}
$peak  = max(1, max($series));
$total = array_sum($series);
$avg   = (int) round($total / $range);

// Unique-ish visitors: the ip hash is salted per day, so a repeat visit on the
// same day counts once and the raw address is never stored.
$st = $pdo->prepare('SELECT COUNT(DISTINCT ip_hash) FROM visits WHERE day >= ?');
$st->execute([$from]);
$uniques = (int) $st->fetchColumn();

$st = $pdo->prepare(
    'SELECT path, COUNT(*) AS n FROM visits WHERE day >= ? GROUP BY path ORDER BY n DESC LIMIT 12'
);
$st->execute([$from]);
$topPaths = $st->fetchAll();

$st = $pdo->prepare(
    "SELECT a.title, a.slug, COUNT(v.id) AS n
       FROM visits v JOIN albums a ON a.id = v.album_id
      WHERE v.day >= ? GROUP BY a.id, a.title, a.slug ORDER BY n DESC LIMIT 10"
);
$st->execute([$from]);
$topAlbums = $st->fetchAll();

$st = $pdo->prepare(
    "SELECT referer, COUNT(*) AS n FROM visits
      WHERE day >= ? AND referer <> '' AND referer IS NOT NULL
      GROUP BY referer ORDER BY n DESC LIMIT 10"
);
$st->execute([$from]);
$referers = $st->fetchAll();

$totalDownloads = (int) $pdo->query('SELECT COALESCE(SUM(downloads),0) FROM albums')->fetchColumn();
$totalMessages  = (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();

/** Rough device split from the user-agent string. Good enough to answer
    "do parents open this on a phone?" — and the answer is almost always yes. */
$st = $pdo->prepare('SELECT ua FROM visits WHERE day >= ? LIMIT 5000');
$st->execute([$from]);
$devices = ['มือถือ' => 0, 'แท็บเล็ต' => 0, 'คอมพิวเตอร์' => 0];
foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $ua) {
    if (preg_match('/iPad|Tablet/i', (string) $ua)) {
        $devices['แท็บเล็ต']++;
    } elseif (preg_match('/Mobile|Android|iPhone/i', (string) $ua)) {
        $devices['มือถือ']++;
    } else {
        $devices['คอมพิวเตอร์']++;
    }
}
$deviceTotal = max(1, array_sum($devices));

$admin_title  = 'สถิติการเข้าชม';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['สถิติการเข้าชม', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">สถิติการเข้าชม</h1>
    <p class="text-sm text-muted mb-0">
      <?= e(thai_date($from)) ?> – <?= e(thai_date(date('Y-m-d'))) ?>
    </p>
  </div>
  <div class="row" style="gap:6px;">
    <?php foreach ([7 => '7 วัน', 30 => '30 วัน', 90 => '90 วัน', 365 => '1 ปี'] as $d => $label): ?>
      <a class="btn btn--<?= $range === $d ? 'primary' : 'light' ?> btn--sm"
         href="<?= e(url('admin-stats.php?days=' . $d)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="stats">
  <div class="stat">
    <div class="stat__label"><?= icon('eye') ?> เปิดหน้าเว็บ</div>
    <div class="stat__value"><?= fmt_num($total) ?></div>
    <div class="stat__delta text-faint">เฉลี่ยวันละ <?= fmt_num($avg) ?> ครั้ง</div>
  </div>
  <div class="stat">
    <div class="stat__label"><?= icon('users') ?> ผู้ชมไม่ซ้ำ</div>
    <div class="stat__value"><?= fmt_num($uniques) ?></div>
    <div class="stat__delta text-faint">นับแบบไม่เก็บเลข IP จริง</div>
  </div>
  <div class="stat">
    <div class="stat__label"><?= icon('download') ?> ดาวน์โหลดสะสม</div>
    <div class="stat__value"><?= fmt_num($totalDownloads) ?></div>
    <div class="stat__delta text-faint">ทุกอัลบั้มรวมกัน</div>
  </div>
  <div class="stat">
    <div class="stat__label"><?= icon('inbox') ?> ข้อความที่ได้รับ</div>
    <div class="stat__value"><?= fmt_num($totalMessages) ?></div>
    <div class="stat__delta text-faint">ตั้งแต่เปิดเว็บ</div>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">จำนวนครั้งที่เปิดหน้าเว็บรายวัน</h2></div>
  <div class="panel__body">
    <?php if ($total === 0): ?>
      <div class="empty-state" style="padding:30px 10px;">
        <?= icon('chart', '', 48) ?>
        <p>ยังไม่มีข้อมูลการเข้าชมในช่วงนี้</p>
      </div>
    <?php else: ?>
      <div class="chart">
        <?php foreach ($series as $day => $n): ?>
          <div class="chart__bar" style="height: <?= max(2, round($n / $peak * 100)) ?>%">
            <span><?= e(thai_date($day)) ?> · <?= fmt_num($n) ?> ครั้ง</span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="row row--between text-xs text-faint mt-8">
        <span><?= e(thai_date(array_key_first($series))) ?></span>
        <span>สูงสุด <?= fmt_num($peak) ?> ครั้งต่อวัน</span>
        <span><?= e(thai_date(array_key_last($series))) ?></span>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">หน้าที่มีคนเปิดมากที่สุด</h2></div>
    <div class="table-wrap">
      <table class="tbl">
        <tbody>
        <?php foreach ($topPaths as $p): ?>
          <tr>
            <td class="text-sm"><?= e(excerpt($p['path'], 56)) ?></td>
            <td class="text-right fw-700" style="width:80px;"><?= fmt_num((int) $p['n']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$topPaths): ?>
          <tr><td class="text-sm text-muted" style="padding:20px;">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">อัลบั้มที่มีคนเปิดมากที่สุด</h2></div>
    <div class="table-wrap">
      <table class="tbl">
        <tbody>
        <?php foreach ($topAlbums as $a): ?>
          <tr>
            <td><a href="<?= e(url('album.php?slug=' . urlencode($a['slug']))) ?>" target="_blank"><?= e($a['title']) ?></a></td>
            <td class="text-right fw-700" style="width:80px;"><?= fmt_num((int) $a['n']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$topAlbums): ?>
          <tr><td class="text-sm text-muted" style="padding:20px;">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">อุปกรณ์ที่ใช้เปิด</h2></div>
    <div class="panel__body">
      <?php foreach ($devices as $label => $n):
          $pct = round($n / $deviceTotal * 100); ?>
        <div class="mb-16">
          <div class="row row--between text-sm mb-8">
            <span><?= e($label) ?></span>
            <span class="fw-700"><?= $pct ?>% <span class="text-faint text-xs">(<?= fmt_num($n) ?>)</span></span>
          </div>
          <div class="storage__bar" style="background:var(--line);">
            <div class="storage__fill" style="width: <?= $pct ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">มาจากที่ไหน</h2></div>
    <div class="table-wrap">
      <table class="tbl">
        <tbody>
        <?php foreach ($referers as $r): ?>
          <tr>
            <td class="text-sm"><?= e(excerpt($r['referer'], 56)) ?></td>
            <td class="text-right fw-700" style="width:80px;"><?= fmt_num((int) $r['n']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$referers): ?>
          <tr><td class="text-sm text-muted" style="padding:20px;">
            ยังไม่มีข้อมูล — ส่วนใหญ่คนเข้าตรงจากลิงก์ที่แชร์ทาง LINE ซึ่งไม่ส่งข้อมูลต้นทางมาให้
          </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
