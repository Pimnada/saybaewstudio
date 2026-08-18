<?php
/** ตั้งค่าระบบ — server health, storage, categories and maintenance. Owner only. */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../image.php';

require_owner();
$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_category') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM categories')->fetchColumn();
            $pdo->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)')
                ->execute([$name, unique_slug('categories', $name), $max + 1]);
            flash('เพิ่มหมวดหมู่ "' . $name . '" แล้ว');
        }
        redirect('admin-system.php');
    }

    if ($action === 'delete_category') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE albums SET category_id = NULL WHERE category_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash('ลบหมวดหมู่แล้ว อัลบั้มที่เคยอยู่ในหมวดนี้ยังอยู่ครบ');
        redirect('admin-system.php');
    }

    if ($action === 'recalc_storage') {
        set_setting('storage_bytes_at', '0');
        flash('คำนวณพื้นที่ใช้งานใหม่แล้ว');
        redirect('admin-system.php');
    }

    if ($action === 'clean_tmp') {
        $n = 0;
        foreach (glob(upload_path('tmp/*')) ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - 3600) {
                @unlink($file);
                $n++;
            }
        }
        flash('ลบไฟล์ ZIP ชั่วคราวแล้ว ' . $n . ' ไฟล์');
        redirect('admin-system.php');
    }

    if ($action === 'find_orphans') {
        redirect('admin-system.php?orphans=1');
    }

    if ($action === 'delete_orphans') {
        $known = [];
        foreach ($pdo->query('SELECT album_id, filename FROM photos') as $row) {
            $known[$row['album_id'] . '/' . $row['filename']] = true;
        }
        $removed = 0;
        foreach (glob(upload_path('albums/*'), GLOB_ONLYDIR) ?: [] as $dir) {
            $albumId = basename($dir);
            foreach (['orig', 'preview', 'thumb'] as $size) {
                foreach (glob($dir . '/' . $size . '/*') ?: [] as $file) {
                    if (!isset($known[$albumId . '/' . basename($file)])) {
                        @unlink($file);
                        $removed++;
                    }
                }
            }
        }
        set_setting('storage_bytes_at', '0');
        log_activity('system.orphans', $removed . ' ไฟล์');
        flash('ลบไฟล์กำพร้า ' . $removed . ' ไฟล์แล้ว');
        redirect('admin-system.php');
    }
}

$categories = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM albums a WHERE a.category_id = c.id) AS n
       FROM categories c ORDER BY c.sort_order, c.id'
)->fetchAll();

$photoBytes = (int) $pdo->query('SELECT COALESCE(SUM(bytes), 0) FROM photos')->fetchColumn();
$photoCount = (int) $pdo->query('SELECT COUNT(*) FROM photos')->fetchColumn();

$tmpFiles = count(glob(upload_path('tmp/*')) ?: []);

// Files on disk with no database row — usually a failed delete or an interrupted upload.
$orphanCount = null;
if (isset($_GET['orphans'])) {
    $known = [];
    foreach ($pdo->query('SELECT album_id, filename FROM photos') as $row) {
        $known[$row['album_id'] . '/' . $row['filename']] = true;
    }
    $orphanCount = 0;
    foreach (glob(upload_path('albums/*'), GLOB_ONLYDIR) ?: [] as $dir) {
        $albumId = basename($dir);
        foreach (['orig', 'preview', 'thumb'] as $size) {
            foreach (glob($dir . '/' . $size . '/*') ?: [] as $file) {
                if (!isset($known[$albumId . '/' . basename($file)])) {
                    $orphanCount++;
                }
            }
        }
    }
}

$checks = [
    ['PHP',                PHP_VERSION, version_compare(PHP_VERSION, '8.1', '>=')],
    ['ฐานข้อมูล',           DB_DRIVER === 'sqlite' ? 'SQLite (เครื่องพัฒนา)' : 'MySQL', true],
    ['ตัวประมวลผลภาพ',      has_imagick() ? 'Imagick' : (extension_loaded('gd') ? 'GD' : 'ไม่มี'),
                            has_imagick() || extension_loaded('gd')],
    ['บีบไฟล์ ZIP',         class_exists('ZipArchive') ? 'พร้อมใช้งาน' : 'ไม่มี', class_exists('ZipArchive')],
    ['อ่านข้อมูล EXIF',      function_exists('exif_read_data') ? 'พร้อมใช้งาน' : 'ไม่มี', function_exists('exif_read_data')],
    ['ขนาดไฟล์อัปโหลดสูงสุด', ini_get('upload_max_filesize'), true],
    ['ขนาดคำขอสูงสุด',       ini_get('post_max_size'), true],
    ['หน่วยความจำต่อคำขอ',    ini_get('memory_limit'), true],
    ['โฟลเดอร์ uploads',    is_writable(upload_path()) ? 'เขียนได้' : 'เขียนไม่ได้', is_writable(upload_path())],
    ['การส่งอีเมล',         MAIL_LOG_ONLY ? 'โหมดทดสอบ (ไม่ส่งจริง)'
                            : (SMTP_HOST !== '' ? 'SMTP: ' . SMTP_HOST : 'PHP mail()'), true],
];

$admin_title  = 'ตั้งค่าระบบ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ตั้งค่าระบบ', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ตั้งค่าระบบ</h1>
    <p class="text-sm text-muted mb-0">สถานะเซิร์ฟเวอร์ พื้นที่จัดเก็บ และงานดูแลระบบ</p>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">สถานะเซิร์ฟเวอร์</h2></div>
    <div class="table-wrap">
      <table class="tbl">
        <tbody>
        <?php foreach ($checks as [$label, $value, $ok]): ?>
          <tr>
            <td style="width:190px;" class="text-sm text-muted"><?= e($label) ?></td>
            <td class="text-sm fw-700"><?= e($value) ?></td>
            <td style="width:44px;" class="text-right">
              <span style="color: var(--<?= $ok ? 'ok' : 'danger' ?>);">
                <?= icon($ok ? 'check-circle' : 'close', '', 18) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2 class="panel__title">พื้นที่จัดเก็บ</h2></div>
    <div class="panel__body">
      <div class="stat-strip mb-16">
        <div>
          <div class="stat-strip__value"><?= e(fmt_bytes($photoBytes)) ?></div>
          <div class="stat-strip__label">ไฟล์ต้นฉบับ</div>
        </div>
        <div>
          <div class="stat-strip__value"><?= fmt_num($photoCount) ?></div>
          <div class="stat-strip__label">รูปในระบบ</div>
        </div>
        <div>
          <div class="stat-strip__value"><?= (int) STORAGE_QUOTA_GB ?> GB</div>
          <div class="stat-strip__label">โควตา</div>
        </div>
        <div>
          <div class="stat-strip__value"><?= fmt_num($tmpFiles) ?></div>
          <div class="stat-strip__label">ไฟล์ชั่วคราว</div>
        </div>
      </div>

      <p class="text-sm text-muted">
        แต่ละรูปถูกเก็บ 3 ขนาด — ต้นฉบับสำหรับให้ลูกค้าดาวน์โหลด, ตัวอย่าง 2048px สำหรับดูเต็มจอ
        และรูปย่อ 600px สำหรับหน้ากริด ตัวเลข "ไฟล์ต้นฉบับ" ด้านบนนับเฉพาะต้นฉบับ
      </p>

      <div class="row row--wrap" style="gap:8px;">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="recalc_storage">
          <button class="btn btn--light btn--sm" type="submit"><?= icon('refresh', '', 16) ?> คำนวณพื้นที่ใหม่</button>
        </form>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="clean_tmp">
          <button class="btn btn--light btn--sm" type="submit"><?= icon('trash', '', 16) ?> ล้างไฟล์ ZIP ชั่วคราว</button>
        </form>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="find_orphans">
          <button class="btn btn--light btn--sm" type="submit"><?= icon('search', '', 16) ?> ตรวจหาไฟล์กำพร้า</button>
        </form>
      </div>

      <?php if ($orphanCount !== null): ?>
        <div class="alert alert--<?= $orphanCount > 0 ? 'warn' : 'success' ?> mt-16 mb-0">
          <?= icon($orphanCount > 0 ? 'help' : 'check-circle', '', 20) ?>
          <span>
            <?php if ($orphanCount > 0): ?>
              พบไฟล์ที่ไม่มีข้อมูลในฐานข้อมูล <?= fmt_num($orphanCount) ?> ไฟล์
              <form method="post" class="mt-8" data-confirm-submit="ลบไฟล์กำพร้า <?= fmt_num($orphanCount) ?> ไฟล์ถาวร?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_orphans">
                <button class="btn btn--danger btn--sm" type="submit">ลบไฟล์เหล่านี้</button>
              </form>
            <?php else: ?>
              ไม่พบไฟล์กำพร้า ทุกไฟล์บนดิสก์มีข้อมูลตรงกับฐานข้อมูล
            <?php endif; ?>
          </span>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<div class="panel">
  <div class="panel__head">
    <h2 class="panel__title">หมวดหมู่อัลบั้ม</h2>
    <span class="spacer"></span>
    <button class="btn btn--primary btn--sm" type="button" data-modal-open="[data-cat-modal]">
      <?= icon('plus', '', 16) ?> เพิ่มหมวดหมู่
    </button>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:36px;"></th>
          <th>ชื่อหมวดหมู่</th>
          <th style="width:180px;">slug</th>
          <th style="width:130px;">จำนวนอัลบั้ม</th>
          <th style="width:60px;"></th>
        </tr>
      </thead>
      <tbody data-sortable="<?= e(url('api-sort.php?table=categories')) ?>">
      <?php foreach ($categories as $c): ?>
        <tr data-sort-id="<?= (int) $c['id'] ?>" draggable="true">
          <td class="sortable-handle"><?= icon('drag', '', 18) ?></td>
          <td class="fw-700"><?= e($c['name']) ?></td>
          <td class="text-sm text-muted"><?= e($c['slug']) ?></td>
          <td class="text-sm"><?= fmt_num((int) $c['n']) ?></td>
          <td>
            <form method="post" data-confirm-submit="ลบหมวดหมู่ &quot;<?= e($c['name']) ?>&quot;? อัลบั้มจะไม่ถูกลบ">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_category">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">การตั้งค่าที่แก้ได้เฉพาะในไฟล์ config.php</h2></div>
  <div class="panel__body">
    <p class="text-sm text-muted">
      ค่าเหล่านี้เป็นความลับหรือเป็นค่าระดับเซิร์ฟเวอร์ จึงไม่เปิดให้แก้จากหน้าเว็บ —
      แก้ในไฟล์ <code>config.php</code> บนเซิร์ฟเวอร์ ซึ่งไม่ถูกเขียนทับตอนอัปเดตเว็บ
    </p>
    <table class="tbl" style="border:1px solid var(--line);border-radius:var(--r-sm);overflow:hidden;">
      <tbody>
        <tr><th style="width:220px;">MAIL_LOG_ONLY</th>
            <td class="text-sm"><?= MAIL_LOG_ONLY ? 'true — ยังไม่ส่งอีเมลออกจริง' : 'false — ส่งอีเมลจริง' ?></td></tr>
        <tr><th>SMTP_HOST</th><td class="text-sm"><?= e(SMTP_HOST ?: '(ไม่ได้ตั้ง — ใช้ PHP mail())') ?></td></tr>
        <tr><th>MAIL_FROM</th><td class="text-sm"><?= e(MAIL_FROM) ?></td></tr>
        <tr><th>SITE_URL</th><td class="text-sm"><?= e(SITE_URL) ?></td></tr>
        <tr><th>STORAGE_QUOTA_GB</th><td class="text-sm"><?= (int) STORAGE_QUOTA_GB ?></td></tr>
        <tr><th>APP_DEBUG</th>
            <td class="text-sm">
              <?= APP_DEBUG ? 'true' : 'false' ?>
              <?php if (APP_DEBUG && DB_DRIVER !== 'sqlite'): ?>
                <span class="badge badge--danger">ควรปิดบนเซิร์ฟเวอร์จริง</span>
              <?php endif; ?>
            </td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" data-cat-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_category">
      <div class="modal__head">
        <h3 class="modal__title">เพิ่มหมวดหมู่อัลบั้ม</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>
      <div class="modal__body">
        <div class="field mb-0">
          <label for="ct-name">ชื่อหมวดหมู่ <span class="req">*</span></label>
          <input id="ct-name" type="text" name="name" required maxlength="120" placeholder="เช่น งานแต่งงาน">
          <p class="hint">หมวดหมู่จะกลายเป็นปุ่มกรองบนหน้าแรกและหน้าผลงานทันที</p>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
        <button class="btn btn--primary" type="submit">เพิ่ม</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
