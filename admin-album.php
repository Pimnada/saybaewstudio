<?php
/**
 * One album: upload, organise, publish, and hand it over to the customer.
 * This is the screen the studio spends most of its time on.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';
require_once __DIR__ . '/mailer.php';

$pdo = db();

if (isset($_GET['new'])) {
    redirect('admin-albums.php');
}

$albumId = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
$st->execute([$albumId]);
$album = $st->fetch();

if (!$album) {
    flash('ไม่พบอัลบั้มนี้', 'error');
    redirect('admin-albums.php');
}

// ------------------------------------------------------------------ POST ----

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_details') {
        $title = trim((string) ($_POST['title'] ?? $album['title']));
        $pdo->prepare(
            'UPDATE albums SET title = ?, slug = ?, description = ?, category_id = ?,
                    event_date = ?, client_name = ?, updated_at = ? WHERE id = ?'
        )->execute([
            $title,
            unique_slug('albums', $title, $albumId),
            trim((string) ($_POST['description'] ?? '')),
            ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null,
            ($_POST['event_date'] ?? '') !== '' ? $_POST['event_date'] : null,
            trim((string) ($_POST['client_name'] ?? '')) ?: null,
            date('Y-m-d H:i:s'),
            $albumId,
        ]);
        log_activity('album.update', $title);
        flash('บันทึกรายละเอียดอัลบั้มแล้ว');
        redirect('admin-album.php?id=' . $albumId);
    }

    if ($action === 'update_settings') {
        $access = in_array($_POST['access'] ?? 'public', ['public', 'code', 'hidden'], true)
            ? $_POST['access'] : 'public';
        $code = trim((string) ($_POST['access_code'] ?? ''));
        $pdo->prepare(
            'UPDATE albums SET status = ?, access = ?, access_code = ?, allow_download = ?,
                    is_featured = ?, updated_at = ? WHERE id = ?'
        )->execute([
            ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
            $access,
            $access === 'code' ? ($code !== '' ? $code : strtoupper(bin2hex(random_bytes(3)))) : null,
            isset($_POST['allow_download']) ? 1 : 0,
            isset($_POST['is_featured']) ? 1 : 0,
            date('Y-m-d H:i:s'),
            $albumId,
        ]);
        log_activity('album.settings', $album['title']);
        flash('บันทึกการตั้งค่าอัลบั้มแล้ว');
        redirect('admin-album.php?id=' . $albumId . '&tab=settings');
    }

    if ($action === 'add_folder') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM folders WHERE album_id = ?');
            $max->execute([$albumId]);
            $pdo->prepare('INSERT INTO folders (album_id, name, sort_order) VALUES (?, ?, ?)')
                ->execute([$albumId, $name, (int) $max->fetchColumn() + 1]);
            flash('สร้างโฟลเดอร์ "' . $name . '" แล้ว');
        }
        redirect('admin-album.php?id=' . $albumId);
    }

    if ($action === 'delete_folder') {
        $fid = (int) ($_POST['folder_id'] ?? 0);
        // Photos survive; they simply return to the album root.
        $pdo->prepare('UPDATE photos SET folder_id = NULL WHERE folder_id = ?')->execute([$fid]);
        $pdo->prepare('DELETE FROM folders WHERE id = ? AND album_id = ?')->execute([$fid, $albumId]);
        flash('ลบโฟลเดอร์แล้ว รูปที่อยู่ข้างในถูกย้ายกลับมาที่อัลบั้มหลัก');
        redirect('admin-album.php?id=' . $albumId);
    }

    if ($action === 'add_video') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $url   = trim((string) ($_POST['url'] ?? ''));
        if ($title !== '' && $url !== '') {
            $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM videos WHERE album_id = ?');
            $max->execute([$albumId]);
            $pdo->prepare(
                "INSERT INTO videos (album_id, title, provider, url, duration, sort_order, status)
                 VALUES (?, ?, 'youtube', ?, ?, ?, 'published')"
            )->execute([
                $albumId, $title, $url, trim((string) ($_POST['duration'] ?? '')),
                (int) $max->fetchColumn() + 1,
            ]);
            flash('เพิ่มวิดีโอแล้ว');
        }
        redirect('admin-album.php?id=' . $albumId . '&tab=videos');
    }

    if ($action === 'delete_video') {
        $pdo->prepare('DELETE FROM videos WHERE id = ? AND album_id = ?')
            ->execute([(int) ($_POST['video_id'] ?? 0), $albumId]);
        flash('ลบวิดีโอแล้ว');
        redirect('admin-album.php?id=' . $albumId . '&tab=videos');
    }

    if ($action === 'send_album') {
        $to   = trim((string) ($_POST['email'] ?? ''));
        $name = trim((string) ($_POST['customer'] ?? 'ลูกค้า'));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('อีเมลไม่ถูกต้อง', 'error');
            redirect('admin-album.php?id=' . $albumId . '&tab=settings');
        }
        $ok = send_email($to, 'album-ready', [
            'name'        => $name,
            'album_title' => $album['title'],
            'album_url'   => url('album.php?slug=' . urlencode($album['slug'])),
            'photo_count' => album_photo_count($albumId),
            'video_count' => album_video_count($albumId),
            'access_code' => $album['access'] === 'code' ? $album['access_code'] : '',
            'note'        => trim((string) ($_POST['note'] ?? '')),
        ]);
        log_activity('album.send', $album['title'] . ' → ' . $to);
        flash($ok
            ? (MAIL_LOG_ONLY
                ? 'บันทึกอีเมลไว้ในระบบแล้ว (โหมดทดสอบ ยังไม่ส่งออกจริง) ดูได้ที่หน้าอีเมลที่ส่งออก'
                : 'ส่งลิงก์อัลบั้มไปที่ ' . $to . ' แล้ว')
            : 'ส่งอีเมลไม่สำเร็จ ตรวจสอบการตั้งค่าอีเมลในหน้าตั้งค่าระบบ',
            $ok ? 'success' : 'error');
        redirect('admin-album.php?id=' . $albumId . '&tab=settings');
    }
}

// ------------------------------------------------------------------ read ----

$folders = $pdo->prepare(
    'SELECT f.*, (SELECT COUNT(*) FROM photos p WHERE p.folder_id = f.id) AS n
       FROM folders f WHERE f.album_id = ? ORDER BY f.sort_order, f.id'
);
$folders->execute([$albumId]);
$folders = $folders->fetchAll();

$folderId = isset($_GET['folder']) ? (int) $_GET['folder'] : 0;
$sort     = (string) ($_GET['sort'] ?? 'order');
$orderSql = match ($sort) {
    'newest' => 'id DESC',
    'oldest' => 'id ASC',
    'name'   => 'orig_name ASC',
    'size'   => 'bytes DESC',
    default  => 'sort_order ASC, id ASC',
};

$sql    = 'SELECT * FROM photos WHERE album_id = ?';
$params = [$albumId];
if ($folderId > 0) {
    $sql     .= ' AND folder_id = ?';
    $params[] = $folderId;
}
$sql .= ' ORDER BY ' . $orderSql;

$ps = $pdo->prepare($sql);
$ps->execute($params);
$photos = $ps->fetchAll();

$videos = $pdo->prepare('SELECT * FROM videos WHERE album_id = ? ORDER BY sort_order, id');
$videos->execute([$albumId]);
$videos = $videos->fetchAll();

$totalPhotos = album_photo_count($albumId);
$unfiled     = $pdo->prepare('SELECT COUNT(*) FROM photos WHERE album_id = ? AND folder_id IS NULL');
$unfiled->execute([$albumId]);
$unfiled = (int) $unfiled->fetchColumn();

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();

$bs = $pdo->prepare('SELECT COALESCE(SUM(bytes),0) FROM photos WHERE album_id = ?');
$bs->execute([$albumId]);
$albumBytes = (int) $bs->fetchColumn();

$tab = (string) ($_GET['tab'] ?? 'photos');

$admin_title   = $album['title'];
$admin_crumbs  = [['หน้าแรก', 'admin.php'], ['อัลบั้มภาพ', 'admin-albums.php'], [$album['title'], null]];
$admin_scripts = ['assets/js/uploader.js'];
include __DIR__ . '/inc/admin-head.php';
?>

<h1 class="page-title">จัดการอัลบั้มภาพ</h1>

<div class="album-hero">

  <div class="panel" style="margin:0;">
    <div class="panel__body album-hero__card">
      <img class="album-hero__cover" src="<?= e(album_cover($album)) ?>" alt="" loading="lazy">
      <div style="min-width:0;">
        <div class="row mb-8" style="gap:10px;">
          <h2 style="font-size:19px;margin:0;"><?= e($album['title']) ?></h2>
          <span class="badge badge--<?= $album['status'] === 'published' ? 'ok' : 'muted' ?>">
            <?= $album['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
          </span>
          <?php if ($album['access'] === 'code'): ?>
            <span class="badge badge--warn"><?= icon('lock', '', 13) ?> มีรหัส</span>
          <?php endif; ?>
        </div>

        <div class="album-hero__meta">
          <span><?= icon('images') ?> อัลบั้มภาพ</span>
          <span><?= icon('calendar') ?> สร้างเมื่อ <?= e(thai_date($album['created_at'])) ?></span>
          <span><?= icon('refresh') ?> อัปเดตล่าสุด <?= e(thai_date($album['updated_at'])) ?></span>
          <span><?= icon('database') ?> <?= e(fmt_bytes($albumBytes)) ?></span>
        </div>

        <p class="text-sm text-muted"><?= e($album['description'] ?: 'ยังไม่มีคำอธิบายอัลบั้ม') ?></p>

        <div class="row" style="gap:8px;">
          <button class="btn btn--light btn--sm" type="button" data-modal-open="[data-edit-album]">
            <?= icon('edit', '', 16) ?> แก้ไขรายละเอียด
          </button>
          <a class="btn btn--light btn--sm" href="<?= e(url('album.php?slug=' . urlencode($album['slug']))) ?>"
             target="_blank" rel="noopener"><?= icon('external', '', 16) ?> ดูหน้าเว็บจริง</a>
          <button class="btn btn--light btn--sm" type="button"
                  data-copy="<?= e(url('album.php?slug=' . urlencode($album['slug']))) ?>">
            <?= icon('link', '', 16) ?> คัดลอกลิงก์
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="panel" style="margin:0;">
    <div class="panel__head"><h2 class="panel__title">สถิติอัลบั้มนี้</h2></div>
    <div class="panel__body">
      <div class="stat-strip">
        <div>
          <div class="stat-strip__value"><?= fmt_num($totalPhotos) ?></div>
          <div class="stat-strip__label">รูปภาพ</div>
        </div>
        <div>
          <div class="stat-strip__value"><?= fmt_num(count($videos)) ?></div>
          <div class="stat-strip__label">วิดีโอ</div>
        </div>
        <div>
          <div class="stat-strip__value"><?= fmt_num((int) $album['views']) ?></div>
          <div class="stat-strip__label">เข้าชม</div>
        </div>
        <div>
          <div class="stat-strip__value"><?= fmt_num((int) $album['downloads']) ?></div>
          <div class="stat-strip__label">ดาวน์โหลด</div>
        </div>
      </div>

      <div class="row mt-16" style="gap:10px;border-top:1px solid var(--line);padding-top:14px;">
        <span class="row text-sm text-muted" style="gap:6px;">
          <?= icon('share', '', 16) ?> แชร์ <?= fmt_num((int) $album['shares']) ?> ครั้ง
        </span>
        <span class="row text-sm text-muted" style="gap:6px;">
          <?= icon('bookmark', '', 16) ?> บันทึก <?= fmt_num((int) $album['saves']) ?> ครั้ง
        </span>
      </div>
    </div>
  </div>

</div>

<div data-tab-scope>
  <div class="panel">
    <div class="tabs" data-tabs>
      <button class="tab <?= $tab === 'photos' ? 'is-active' : '' ?>" type="button" data-tab="photos">
        <?= icon('image') ?> รูปภาพ (<?= fmt_num($totalPhotos) ?>)
      </button>
      <button class="tab <?= $tab === 'videos' ? 'is-active' : '' ?>" type="button" data-tab="videos">
        <?= icon('video') ?> วิดีโอ (<?= fmt_num(count($videos)) ?>)
      </button>
      <button class="tab <?= $tab === 'settings' ? 'is-active' : '' ?>" type="button" data-tab="settings">
        <?= icon('settings') ?> การตั้งค่าอัลบั้ม
      </button>
    </div>
  </div>

  <!-- ============================================================ photos -->
  <div class="tab-panel <?= $tab === 'photos' ? 'is-active' : '' ?>" data-tab-panel="photos">
    <div class="album-layout">

      <div class="panel" style="margin:0;">
        <div class="toolbar">
          <button class="btn btn--dark btn--sm" type="button" data-modal-open="[data-upload-modal]">
            <?= icon('upload', '', 16) ?> อัปโหลดรูปภาพ
          </button>
          <button class="btn btn--light btn--sm" type="button" data-modal-open="[data-folder-modal]">
            <?= icon('folder-plus', '', 16) ?> สร้างโฟลเดอร์
          </button>

          <div class="dropdown" data-dropdown>
            <button class="btn btn--light btn--sm" type="button" data-dropdown-toggle>
              <?= icon('sort', '', 16) ?> จัดเรียง
            </button>
            <div class="dropdown__menu">
              <?php
              $sorts = [
                  'order'  => 'ลำดับที่กำหนดเอง',
                  'newest' => 'อัปโหลดล่าสุดก่อน',
                  'oldest' => 'อัปโหลดเก่าสุดก่อน',
                  'name'   => 'ชื่อไฟล์ A–Z',
                  'size'   => 'ขนาดไฟล์ใหญ่ก่อน',
              ];
              foreach ($sorts as $key => $label): ?>
                <a class="dropdown__item" href="<?= e(url('admin-album.php?id=' . $albumId
                    . ($folderId ? '&folder=' . $folderId : '') . '&sort=' . $key)) ?>">
                  <?= $sort === $key ? icon('check') : icon('dot') ?> <?= e($label) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="toolbar__search">
            <?= icon('search') ?>
            <input type="search" data-photo-search placeholder="ค้นหารูปภาพ...">
          </div>

          <div class="viewmode" data-viewmode>
            <button type="button" class="is-active" data-size="md" title="ขนาดกลาง"><?= icon('grid-lg') ?></button>
            <button type="button" data-size="sm" title="ขนาดเล็ก"><?= icon('grid-sm') ?></button>
            <button type="button" data-size="list" title="มุมมองรายการ"><?= icon('list') ?></button>
          </div>
        </div>

        <?php if (!$photos): ?>
          <div class="panel__body">
            <div class="empty-state" style="padding:40px 10px;">
              <?= icon('image', '', 56) ?>
              <p>ยังไม่มีรูปใน<?= $folderId ? 'โฟลเดอร์นี้' : 'อัลบั้มนี้' ?><br>
                 <span class="text-xs">ลากไฟล์มาวางได้เลย รองรับหลายร้อยรูปพร้อมกัน</span></p>
              <button class="btn btn--primary btn--sm" type="button" data-modal-open="[data-upload-modal]">
                อัปโหลดรูปภาพ
              </button>
            </div>
          </div>
        <?php else: ?>
          <div class="ph-grid" data-size="md" data-photo-grid>
            <?php foreach ($photos as $p): ?>
              <div class="ph" data-photo-id="<?= (int) $p['id'] ?>"
                   data-name="<?= e(mb_strtolower($p['orig_name'])) ?>">
                <div class="ph__media">
                  <input class="ph__check" type="checkbox" data-photo-check aria-label="เลือกรูปนี้">
                  <button class="ph__menu" type="button" data-photo-menu="<?= (int) $p['id'] ?>"
                          title="ตัวเลือกเพิ่มเติม"><?= icon('more-vertical', '', 15) ?></button>
                  <img src="<?= e(photo_url($p, 'thumb')) ?>" alt="<?= e($p['orig_name']) ?>" loading="lazy"
                       data-full="<?= e(photo_url($p, 'preview')) ?>">
                </div>
                <div class="ph__body">
                  <div class="ph__name" title="<?= e($p['orig_name']) ?>"><?= e($p['orig_name']) ?></div>
                  <div class="ph__meta">
                    <span><?= e(fmt_bytes((int) $p['bytes'])) ?></span>
                    <span><?= e(thai_date($p['created_at'])) ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ------------------------------------------------------- side rail -->
      <div class="rail">
        <div class="panel" style="margin:0;">
          <div class="panel__head"><h2 class="panel__title">จัดการรูปภาพ</h2></div>
          <div class="panel__body" style="padding:8px;">
            <button class="rail-action" type="button" data-select-all><?= icon('select-all') ?> เลือกทั้งหมด</button>
            <button class="rail-action" type="button" data-select-none><?= icon('select-none') ?> ยกเลิกการเลือก</button>
            <button class="rail-action rail-action--danger" type="button" data-bulk="delete" disabled>
              <?= icon('trash') ?> ลบที่เลือก
            </button>
            <button class="rail-action" type="button" data-bulk="download" disabled>
              <?= icon('download') ?> ดาวน์โหลดที่เลือก
            </button>
            <button class="rail-action" type="button" data-modal-open="[data-move-modal]" data-needs-selection disabled>
              <?= icon('folder-move') ?> ย้ายไปยังโฟลเดอร์
            </button>
            <button class="rail-action" type="button" data-bulk="cover" disabled>
              <?= icon('image') ?> ตั้งเป็นภาพปก
            </button>
            <button class="rail-action" type="button"
                    data-copy="<?= e(url('album.php?slug=' . urlencode($album['slug']))) ?>">
              <?= icon('link') ?> คัดลอกลิงก์อัลบั้ม
            </button>
            <div class="text-xs text-faint text-center" style="padding:8px 0 4px;">
              เลือกอยู่ <span data-selected-count>0</span> รูป
            </div>
          </div>
        </div>

        <div class="panel" style="margin:0;">
          <div class="panel__head">
            <h2 class="panel__title">โฟลเดอร์ในอัลบั้ม</h2>
            <span class="spacer"></span>
            <button class="icon-btn" type="button" data-modal-open="[data-folder-modal]" title="เพิ่มโฟลเดอร์">
              <?= icon('plus') ?>
            </button>
          </div>
          <div class="panel__body" style="padding:8px;">
            <a class="folder-item <?= $folderId === 0 ? 'is-active' : '' ?>"
               href="<?= e(url('admin-album.php?id=' . $albumId)) ?>">
              <?= icon('folder') ?> ทั้งหมด
              <span class="folder-item__count"><?= fmt_num($totalPhotos) ?></span>
            </a>
            <?php foreach ($folders as $f): ?>
              <a class="folder-item <?= $folderId === (int) $f['id'] ? 'is-active' : '' ?>"
                 href="<?= e(url('admin-album.php?id=' . $albumId . '&folder=' . $f['id'])) ?>">
                <?= icon('folder') ?> <?= e($f['name']) ?>
                <span class="folder-item__count"><?= fmt_num((int) $f['n']) ?></span>
              </a>
            <?php endforeach; ?>
            <?php if ($unfiled > 0 && $folders): ?>
              <div class="text-xs text-faint" style="padding:8px 12px 4px;">
                ยังไม่ได้จัดเข้าโฟลเดอร์ <?= fmt_num($unfiled) ?> รูป
              </div>
            <?php endif; ?>
          </div>
          <?php if ($folders): ?>
            <div class="panel__foot" style="padding:10px 12px;">
              <form method="post" style="width:100%;"
                    data-confirm-submit="ลบโฟลเดอร์นี้? รูปข้างในจะถูกย้ายกลับไปที่อัลบั้มหลัก ไม่ได้ถูกลบ">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_folder">
                <div class="row" style="gap:8px;">
                  <select name="folder_id" style="flex:1;">
                    <?php foreach ($folders as $f): ?>
                      <option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn--light btn--sm" type="submit"><?= icon('trash', '', 15) ?></button>
                </div>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- ============================================================ videos -->
  <div class="tab-panel <?= $tab === 'videos' ? 'is-active' : '' ?>" data-tab-panel="videos">
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">วิดีโอในอัลบั้มนี้</h2>
        <span class="spacer"></span>
        <button class="btn btn--primary btn--sm" type="button" data-modal-open="[data-video-modal]">
          <?= icon('plus', '', 16) ?> เพิ่มวิดีโอ
        </button>
      </div>

      <?php if (!$videos): ?>
        <div class="panel__body">
          <div class="empty-state" style="padding:40px 10px;">
            <?= icon('video', '', 56) ?>
            <p>ยังไม่มีวิดีโอ — วางลิงก์ YouTube เพื่อแทรกวิดีโอไฮไลต์ของงาน</p>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr><th>ชื่อวิดีโอ</th><th style="width:110px;">ความยาว</th><th>ลิงก์</th><th style="width:60px;"></th></tr>
            </thead>
            <tbody>
            <?php foreach ($videos as $v): ?>
              <tr>
                <td class="fw-700"><?= e($v['title']) ?></td>
                <td class="text-sm text-muted"><?= e($v['duration'] ?: '—') ?></td>
                <td class="text-sm"><a href="<?= e($v['url']) ?>" target="_blank" rel="noopener"><?= e(excerpt($v['url'], 44)) ?></a></td>
                <td>
                  <form method="post" data-confirm-submit="ลบวิดีโอนี้?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_video">
                    <input type="hidden" name="video_id" value="<?= (int) $v['id'] ?>">
                    <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ========================================================== settings -->
  <div class="tab-panel <?= $tab === 'settings' ? 'is-active' : '' ?>" data-tab-panel="settings">
    <div class="grid" style="grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap:20px; align-items:start;">

      <form class="panel" method="post" style="margin:0;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_settings">
        <div class="panel__head"><h2 class="panel__title">การเผยแพร่และสิทธิ์เข้าชม</h2></div>
        <div class="panel__body">
          <div class="field">
            <label for="s-status">สถานะ</label>
            <select id="s-status" name="status">
              <option value="draft" <?= $album['status'] === 'draft' ? 'selected' : '' ?>>ร่าง — ยังไม่แสดงบนเว็บ</option>
              <option value="published" <?= $album['status'] === 'published' ? 'selected' : '' ?>>เผยแพร่ — แสดงบนหน้าผลงาน</option>
            </select>
          </div>

          <div class="field">
            <label for="s-access">การเข้าชม</label>
            <select id="s-access" name="access" data-access-select>
              <option value="public" <?= $album['access'] === 'public' ? 'selected' : '' ?>>สาธารณะ — ใครก็เปิดดูได้</option>
              <option value="code" <?= $album['access'] === 'code' ? 'selected' : '' ?>>ใส่รหัสก่อนเข้าชม</option>
              <option value="hidden" <?= $album['access'] === 'hidden' ? 'selected' : '' ?>>ซ่อน — เปิดได้เฉพาะคนที่มีลิงก์</option>
            </select>
          </div>

          <div class="field" data-access-code style="<?= $album['access'] === 'code' ? '' : 'display:none;' ?>">
            <label for="s-code">รหัสเข้าชม</label>
            <input id="s-code" type="text" name="access_code" value="<?= e($album['access_code'] ?? '') ?>"
                   placeholder="เว้นว่างเพื่อให้ระบบสุ่มให้">
            <p class="hint">ส่งรหัสนี้ให้ลูกค้าพร้อมลิงก์ ระบบจะจำไว้จนกว่าจะปิดเบราว์เซอร์</p>
          </div>

          <label class="check mb-16">
            <input type="checkbox" name="allow_download" <?= $album['allow_download'] ? 'checked' : '' ?>>
            <span>ให้ลูกค้าดาวน์โหลดไฟล์ขนาดเต็มได้</span>
          </label>

          <label class="check">
            <input type="checkbox" name="is_featured" <?= $album['is_featured'] ? 'checked' : '' ?>>
            <span>ปักหมุดให้แสดงบนหน้าแรก</span>
          </label>
        </div>
        <div class="panel__foot">
          <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกการตั้งค่า</button>
        </div>
      </form>

      <div>
        <form class="panel" method="post" style="margin:0 0 20px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_album">
          <div class="panel__head"><h2 class="panel__title">ส่งลิงก์อัลบั้มให้ลูกค้า</h2></div>
          <div class="panel__body">
            <div class="field">
              <label for="s-customer">ชื่อลูกค้า</label>
              <input id="s-customer" type="text" name="customer" value="<?= e($album['client_name'] ?? '') ?>"
                     placeholder="เช่น คุณแม่เมย์">
            </div>
            <div class="field">
              <label for="s-email">อีเมลผู้รับ <span class="req">*</span></label>
              <input id="s-email" type="email" name="email" required placeholder="customer@example.com">
            </div>
            <div class="field mb-0">
              <label for="s-note">ข้อความเพิ่มเติม</label>
              <textarea id="s-note" name="note" rows="3"
                        placeholder="เช่น รูปหมู่รายห้องอยู่ในโฟลเดอร์ 'พิธีมอบรางวัล' นะคะ"></textarea>
            </div>
            <?php if (MAIL_LOG_ONLY): ?>
              <div class="alert alert--warn mt-16 mb-0">
                <?= icon('help', '', 20) ?>
                <span>ตอนนี้อยู่ในโหมดทดสอบ อีเมลจะถูกบันทึกไว้ในระบบแต่ยังไม่ส่งออกจริง</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="panel__foot">
            <button class="btn btn--primary" type="submit"><?= icon('mail', '', 16) ?> ส่งอีเมล</button>
            <a class="btn btn--light" href="<?= e(url('admin-emails.php')) ?>">ดูอีเมลที่ส่งไปแล้ว</a>
          </div>
        </form>

        <div class="panel" style="margin:0;border-color:var(--danger);">
          <div class="panel__head" style="border-color:var(--danger);">
            <h2 class="panel__title" style="color:var(--danger);">โซนอันตราย</h2>
          </div>
          <div class="panel__body">
            <p class="text-sm text-muted">
              ลบอัลบั้มนี้พร้อมรูปทั้งหมด <?= fmt_num($totalPhotos) ?> รูป
              (<?= e(fmt_bytes($albumBytes)) ?>) ออกจากเซิร์ฟเวอร์ถาวร กู้คืนไม่ได้
            </p>
            <form method="post" action="<?= e(url('admin-albums.php')) ?>"
                  data-confirm-submit="ลบอัลบั้ม &quot;<?= e($album['title']) ?>&quot; และรูปทั้งหมดถาวร? การลบนี้กู้คืนไม่ได้">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $albumId ?>">
              <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', '', 16) ?> ลบอัลบั้มนี้</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Floating per-photo menu, positioned next to whichever ⋯ was clicked. -->
<div class="dropdown__menu" data-photo-actions style="position:fixed;display:none;">
  <a class="dropdown__item" data-pa="download" href="#"><?= icon('download') ?> ดาวน์โหลดไฟล์ต้นฉบับ</a>
  <a class="dropdown__item" data-pa="open" href="#" target="_blank"><?= icon('zoom') ?> เปิดรูปขนาดเต็ม</a>
  <button class="dropdown__item" data-pa="cover" type="button"><?= icon('image') ?> ตั้งเป็นภาพปก</button>
  <div class="dropdown__sep"></div>
  <button class="dropdown__item dropdown__item--danger" data-pa="delete" type="button"><?= icon('trash') ?> ลบรูปนี้</button>
</div>

<!-- ============================================================== modals -->

<div class="modal" data-upload-modal>
  <div class="modal__box modal__box--lg">
    <div class="modal__head">
      <h3 class="modal__title">อัปโหลดรูปภาพ</h3>
      <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
    </div>
    <div class="modal__body">
      <?php if ($folders): ?>
        <div class="field">
          <label for="up-folder">อัปโหลดเข้าโฟลเดอร์</label>
          <select id="up-folder" data-up-folder>
            <option value="">อัลบั้มหลัก (ไม่เข้าโฟลเดอร์)</option>
            <?php foreach ($folders as $f): ?>
              <option value="<?= (int) $f['id'] ?>" <?= $folderId === (int) $f['id'] ? 'selected' : '' ?>>
                <?= e($f['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <?php $maxUpload = max_upload_bytes(); ?>
      <div class="dropzone" data-dropzone data-album="<?= $albumId ?>"
           data-endpoint="<?= e(url('api-upload.php')) ?>"
           data-max-bytes="<?= $maxUpload ?>">
        <?= icon('upload', '', 40) ?>
        <strong>ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</strong>
        <span class="text-sm">
          รองรับ JPG, PNG, WebP · เลือกได้ทีละหลายร้อยรูป ·
          ไม่เกิน <?= e(fmt_bytes($maxUpload)) ?> ต่อไฟล์
        </span>
      </div>

      <?php if ($maxUpload < 12 * 1024 * 1024): ?>
        <div class="alert alert--warn mt-16">
          <?= icon('help', '', 20) ?>
          <span>
            เซิร์ฟเวอร์นี้รับไฟล์ได้ไม่เกิน <strong><?= e(fmt_bytes($maxUpload)) ?></strong> ต่อไฟล์
            ซึ่งเล็กกว่าไฟล์จากกล้องทั่วไป (5–15 MB) ไฟล์ใหญ่จะอัปโหลดไม่ผ่าน —
            ต้องเพิ่ม <code>upload_max_filesize</code> และ <code>post_max_size</code> ก่อน
            (ตอนนี้ตั้งไว้ที่ <?= e((string) ini_get('upload_max_filesize')) ?>
            และ <?= e((string) ini_get('post_max_size')) ?>)
          </span>
        </div>
      <?php endif; ?>
      <input class="hidden" type="file" multiple accept="image/jpeg,image/png,image/webp" data-file-input>

      <div class="up-summary mt-16" data-up-summary style="display:none;">
        <div class="up-bar"><div class="up-bar__fill" data-up-bar></div></div>
        <span class="text-sm fw-700" data-up-count>0 / 0 ไฟล์</span>
      </div>

      <div class="up-list mt-8" data-up-list></div>

      <p class="hint">
        ไฟล์ต้นฉบับถูกเก็บไว้ครบทุกพิกเซลสำหรับให้ลูกค้าดาวน์โหลด
        ระบบจะสร้างรูปย่อและรูปตัวอย่างเพิ่มให้อัตโนมัติเพื่อให้หน้าเว็บเปิดไว
      </p>
    </div>
    <div class="modal__foot">
      <button class="btn btn--light" type="button" data-modal-close>ปิดหน้าต่าง</button>
    </div>
  </div>
</div>

<div class="modal" data-folder-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_folder">
      <div class="modal__head">
        <h3 class="modal__title">สร้างโฟลเดอร์ใหม่</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>
      <div class="modal__body">
        <div class="field mb-0">
          <label for="nf-name">ชื่อโฟลเดอร์ <span class="req">*</span></label>
          <input id="nf-name" type="text" name="name" required maxlength="160"
                 placeholder="เช่น การแสดงเดี่ยว, วงดนตรี, พิธีมอบรางวัล">
          <p class="hint">แบ่งตามลำดับเวลาของงานช่วยให้ผู้ปกครองหารูปลูกเจอเร็วที่สุด</p>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
        <button class="btn btn--primary" type="submit">สร้างโฟลเดอร์</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" data-move-modal>
  <div class="modal__box">
    <div class="modal__head">
      <h3 class="modal__title">ย้ายรูปที่เลือกไปยังโฟลเดอร์</h3>
      <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
    </div>
    <div class="modal__body">
      <div class="field mb-0">
        <label for="mv-folder">ปลายทาง</label>
        <select id="mv-folder" data-move-folder>
          <option value="">อัลบั้มหลัก (นำออกจากโฟลเดอร์)</option>
          <?php foreach ($folders as $f): ?>
            <option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint">ย้าย <span data-selected-count>0</span> รูปที่เลือกอยู่</p>
      </div>
    </div>
    <div class="modal__foot">
      <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
      <button class="btn btn--primary" type="button" data-move-confirm>ย้ายรูป</button>
    </div>
  </div>
</div>

<div class="modal" data-video-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_video">
      <div class="modal__head">
        <h3 class="modal__title">เพิ่มวิดีโอ</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>
      <div class="modal__body">
        <div class="field">
          <label for="v-title">ชื่อวิดีโอ <span class="req">*</span></label>
          <input id="v-title" type="text" name="title" required placeholder="เช่น ไฮไลต์การแสดงชุดใหญ่">
        </div>
        <div class="field">
          <label for="v-url">ลิงก์ YouTube <span class="req">*</span></label>
          <input id="v-url" type="url" name="url" required placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="field mb-0">
          <label for="v-dur">ความยาว</label>
          <input id="v-dur" type="text" name="duration" placeholder="เช่น 3:24">
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
        <button class="btn btn--primary" type="submit">เพิ่มวิดีโอ</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" data-edit-album>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_details">
      <div class="modal__head">
        <h3 class="modal__title">แก้ไขรายละเอียดอัลบั้ม</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>
      <div class="modal__body">
        <div class="field">
          <label for="ea-title">ชื่ออัลบั้ม <span class="req">*</span></label>
          <input id="ea-title" type="text" name="title" required value="<?= e($album['title']) ?>" maxlength="200">
        </div>
        <div class="field">
          <label for="ea-client">ชื่อลูกค้า / โรงเรียน</label>
          <input id="ea-client" type="text" name="client_name" value="<?= e($album['client_name'] ?? '') ?>" maxlength="160">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="ea-cat">หมวดหมู่</label>
            <select id="ea-cat" name="category_id">
              <option value="">ไม่ระบุ</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $album['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="ea-date">วันที่จัดงาน</label>
            <input id="ea-date" type="date" name="event_date" value="<?= e($album['event_date'] ?? '') ?>">
          </div>
        </div>
        <div class="field mb-0">
          <label for="ea-desc">คำอธิบาย</label>
          <textarea id="ea-desc" name="description" rows="3"><?= e($album['description']) ?></textarea>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
        <button class="btn btn--primary" type="submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<script>
/* Album-specific behaviour: selection, bulk actions and the view switcher. */
// admin.js is deferred, so it has run and defined SBSAdmin by the time
// DOMContentLoaded fires — but not yet when this inline block is parsed.
document.addEventListener('DOMContentLoaded', function () {
  var A = window.SBSAdmin;
  var grid = A.$('[data-photo-grid]');

  /* view mode */
  var vm = A.$('[data-viewmode]');
  if (vm && grid) {
    var saved = localStorage.getItem('sbs-photo-view');
    if (saved) { applyView(saved); }

    vm.addEventListener('click', function (ev) {
      var btn = ev.target.closest('button[data-size]');
      if (!btn) { return; }
      applyView(btn.getAttribute('data-size'));
      localStorage.setItem('sbs-photo-view', btn.getAttribute('data-size'));
    });
  }

  function applyView(size) {
    if (!grid) { return; }
    A.$$('button[data-size]', vm).forEach(function (b) {
      b.classList.toggle('is-active', b.getAttribute('data-size') === size);
    });
    if (size === 'list') {
      grid.setAttribute('data-view', 'list');
      grid.setAttribute('data-size', 'md');
    } else {
      grid.removeAttribute('data-view');
      grid.setAttribute('data-size', size);
    }
  }

  /* search within the loaded grid */
  var search = A.$('[data-photo-search]');
  if (search && grid) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      A.$$('.ph', grid).forEach(function (ph) {
        var name = ph.getAttribute('data-name') || '';
        ph.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }

  /* selection */
  function selected() {
    return A.$$('[data-photo-check]:checked').map(function (cb) {
      return cb.closest('.ph').getAttribute('data-photo-id');
    });
  }

  function refresh() {
    var n = selected().length;
    A.$$('[data-selected-count]').forEach(function (el) { el.textContent = n; });
    A.$$('[data-bulk], [data-needs-selection]').forEach(function (btn) { btn.disabled = n === 0; });
    A.$$('.ph').forEach(function (ph) {
      var cb = ph.querySelector('[data-photo-check]');
      ph.classList.toggle('is-selected', !!(cb && cb.checked));
    });
  }

  document.addEventListener('change', function (ev) {
    if (ev.target.matches('[data-photo-check]')) { refresh(); }
  });

  var selAll = A.$('[data-select-all]');
  if (selAll) {
    selAll.addEventListener('click', function () {
      A.$$('[data-photo-check]').forEach(function (cb) {
        if (cb.closest('.ph').style.display !== 'none') { cb.checked = true; }
      });
      refresh();
    });
  }

  var selNone = A.$('[data-select-none]');
  if (selNone) {
    selNone.addEventListener('click', function () {
      A.$$('[data-photo-check]').forEach(function (cb) { cb.checked = false; });
      refresh();
    });
  }

  /* open the full-size preview */
  if (grid) {
    grid.addEventListener('click', function (ev) {
      var img = ev.target.closest('img[data-full]');
      if (img) { window.open(img.getAttribute('data-full'), '_blank'); }
    });
  }

  /* per-photo ⋯ menu */
  var paMenu = A.$('[data-photo-actions]');
  var paId   = null;

  function closePa() { paMenu.style.display = 'none'; paId = null; }

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-photo-menu]');
    if (btn) {
      ev.preventDefault();
      ev.stopPropagation();
      paId = btn.getAttribute('data-photo-menu');

      var img  = btn.closest('.ph').querySelector('img[data-full]');
      var rect = btn.getBoundingClientRect();

      paMenu.querySelector('[data-pa="download"]').href =
        <?= ejs(url('dl.php?photo=')) ?> + paId;
      paMenu.querySelector('[data-pa="open"]').href = img ? img.getAttribute('data-full') : '#';

      paMenu.style.display = 'block';
      paMenu.style.top  = Math.min(rect.bottom + 6, window.innerHeight - 210) + 'px';
      paMenu.style.left = Math.min(rect.left - 150, window.innerWidth - 210) + 'px';
      return;
    }
    if (!ev.target.closest('[data-photo-actions]')) { closePa(); }
  });

  paMenu.addEventListener('click', function (ev) {
    var item = ev.target.closest('[data-pa]');
    if (!item || !paId) { return; }
    var kind = item.getAttribute('data-pa');
    var id   = paId;

    if (kind === 'download' || kind === 'open') { closePa(); return; }
    ev.preventDefault();

    if (kind === 'cover') {
      A.post(<?= ejs(url('api-photos.php')) ?>, { action: 'cover', ids: [id], album_id: <?= $albumId ?> })
        .then(function () { A.toast('ตั้งเป็นภาพปกแล้ว'); setTimeout(function () { location.reload(); }, 700); })
        .catch(function (err) { A.toast(err.message, 'error'); });
    }

    if (kind === 'delete') {
      A.confirm('ลบรูปนี้ถาวร? การลบนี้กู้คืนไม่ได้', function () {
        A.post(<?= ejs(url('api-photos.php')) ?>, { action: 'delete', ids: [id] })
          .then(function () {
            var el = A.$('[data-photo-id="' + id + '"]');
            if (el) { el.remove(); }
            refresh();
            A.toast('ลบรูปแล้ว');
          })
          .catch(function (err) { A.toast(err.message, 'error'); });
      }, 'ยืนยันการลบรูป');
    }

    closePa();
  });

  /* bulk actions */
  A.$$('[data-bulk]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var ids = selected();
      if (!ids.length) { return; }
      var kind = btn.getAttribute('data-bulk');

      if (kind === 'download') {
        window.location = <?= ejs(url('api-photos.php')) ?> + '?action=zip&ids=' + ids.join(',');
        return;
      }

      if (kind === 'delete') {
        A.confirm('ลบรูปที่เลือก ' + ids.length + ' รูปถาวร? การลบนี้กู้คืนไม่ได้', function () {
          A.post(<?= ejs(url('api-photos.php')) ?>, { action: 'delete', ids: ids })
            .then(function () {
              ids.forEach(function (id) {
                var el = A.$('[data-photo-id="' + id + '"]');
                if (el) { el.remove(); }
              });
              refresh();
              A.toast('ลบ ' + ids.length + ' รูปแล้ว');
            })
            .catch(function (err) { A.toast(err.message, 'error'); });
        }, 'ยืนยันการลบรูป');
        return;
      }

      if (kind === 'cover') {
        A.post(<?= ejs(url('api-photos.php')) ?>, { action: 'cover', ids: [ids[0]], album_id: <?= $albumId ?> })
          .then(function () { A.toast('ตั้งเป็นภาพปกแล้ว'); setTimeout(function () { location.reload(); }, 700); })
          .catch(function (err) { A.toast(err.message, 'error'); });
      }
    });
  });

  var moveBtn = A.$('[data-move-confirm]');
  if (moveBtn) {
    moveBtn.addEventListener('click', function () {
      var ids = selected();
      if (!ids.length) { return; }
      A.post(<?= ejs(url('api-photos.php')) ?>, {
        action: 'move',
        ids: ids,
        folder_id: A.$('[data-move-folder]').value,
        album_id: <?= $albumId ?>
      }).then(function () {
        A.closeModal();
        A.toast('ย้าย ' + ids.length + ' รูปแล้ว');
        setTimeout(function () { location.reload(); }, 700);
      }).catch(function (err) { A.toast(err.message, 'error'); });
    });
  }

  /* access-code field only matters for the "code" mode */
  var access = A.$('[data-access-select]');
  if (access) {
    access.addEventListener('change', function () {
      A.$('[data-access-code]').style.display = access.value === 'code' ? '' : 'none';
    });
  }

  refresh();
});
</script>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
