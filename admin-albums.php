<?php
/** อัลบั้มภาพ — the album list, with create / publish / delete. */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash('กรุณาตั้งชื่ออัลบั้ม', 'error');
            redirect('admin-albums.php');
        }
        $pdo->prepare(
            'INSERT INTO albums (type, title, slug, description, category_id, event_date, status, client_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'photo',
            $title,
            unique_slug('albums', $title),
            trim((string) ($_POST['description'] ?? '')),
            ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null,
            ($_POST['event_date'] ?? '') !== '' ? $_POST['event_date'] : null,
            'draft',
            trim((string) ($_POST['client_name'] ?? '')) ?: null,
        ]);
        $id = (int) $pdo->lastInsertId();
        log_activity('album.create', $title);
        flash('สร้างอัลบั้ม "' . $title . '" แล้ว อัปโหลดรูปได้เลยค่ะ');
        redirect('admin-album.php?id=' . $id);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT * FROM photos WHERE album_id = ?');
        $st->execute([$id]);
        foreach ($st->fetchAll() as $photo) {
            delete_photo_files($photo);
        }
        $pdo->prepare('DELETE FROM photos WHERE album_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM folders WHERE album_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM videos WHERE album_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM albums WHERE id = ?')->execute([$id]);
        @rmdir(upload_path('albums/' . $id));
        log_activity('album.delete', 'id=' . $id);
        flash('ลบอัลบั้มเรียบร้อยแล้ว');
        redirect('admin-albums.php');
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT status FROM albums WHERE id = ?');
        $st->execute([$id]);
        $now = $st->fetchColumn();
        $new = $now === 'published' ? 'draft' : 'published';
        $pdo->prepare('UPDATE albums SET status = ?, updated_at = ? WHERE id = ?')
            ->execute([$new, date('Y-m-d H:i:s'), $id]);
        flash($new === 'published' ? 'เผยแพร่อัลบั้มแล้ว' : 'ย้ายอัลบั้มกลับเป็นร่างแล้ว');
        redirect('admin-albums.php');
    }
}

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[]  = '(a.title LIKE ? OR a.client_name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($status !== '') {
    $where[]  = 'a.status = ?';
    $params[] = $status;
}
$whereSql = implode(' AND ', $where);

$st = $pdo->prepare(
    "SELECT a.*, c.name AS category_name,
            (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS photo_count,
            (SELECT COUNT(*) FROM videos v WHERE v.album_id = a.id) AS video_count
       FROM albums a LEFT JOIN categories c ON c.id = a.category_id
      WHERE $whereSql
      ORDER BY a.updated_at DESC, a.id DESC"
);
$st->execute($params);
$albums = $st->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();

$admin_title  = 'อัลบั้มภาพ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['อัลบั้มภาพ', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">อัลบั้มภาพ</h1>
    <p class="text-sm text-muted mb-0">ทั้งหมด <?= fmt_num(count($albums)) ?> อัลบั้ม</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-new-album]">
    <?= icon('plus', '', 18) ?> สร้างอัลบั้มใหม่
  </button>
</div>

<div class="panel">
  <form class="toolbar" method="get">
    <select name="status" onchange="this.form.submit()" style="width:auto;min-width:150px;">
      <option value="">ทุกสถานะ</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>เผยแพร่แล้ว</option>
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>ร่าง</option>
    </select>
    <div class="toolbar__search">
      <?= icon('search') ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่ออัลบั้มหรือชื่อลูกค้า...">
    </div>
    <button class="btn btn--light btn--sm" type="submit">ค้นหา</button>
    <?php if ($q !== '' || $status !== ''): ?>
      <a class="btn btn--light btn--sm" href="<?= e(url('admin-albums.php')) ?>">ล้าง</a>
    <?php endif; ?>
  </form>

  <?php if (!$albums): ?>
    <div class="panel__body">
      <div class="empty-state" style="padding:40px 10px;">
        <?= icon('images', '', 56) ?>
        <p>ยังไม่มีอัลบั้มที่ตรงกับเงื่อนไข</p>
        <button class="btn btn--primary btn--sm" type="button" data-modal-open="[data-new-album]">สร้างอัลบั้มใหม่</button>
      </div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:76px;">ปก</th>
            <th>ชื่ออัลบั้ม</th>
            <th style="width:130px;">หมวดหมู่</th>
            <th style="width:110px;">วันที่งาน</th>
            <th style="width:96px;">รูป / วิดีโอ</th>
            <th style="width:90px;">เข้าชม</th>
            <th style="width:100px;">สถานะ</th>
            <th style="width:130px;"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($albums as $a): ?>
          <tr>
            <td><img class="thumb" src="<?= e(album_cover($a)) ?>" alt="" loading="lazy"></td>
            <td>
              <a class="fw-700" href="<?= e(url('admin-album.php?id=' . $a['id'])) ?>"><?= e($a['title']) ?></a>
              <?php if ($a['client_name']): ?>
                <div class="text-xs text-faint">ลูกค้า: <?= e($a['client_name']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-sm text-muted"><?= e($a['category_name'] ?: '—') ?></td>
            <td class="text-sm text-muted"><?= e(thai_date($a['event_date'])) ?></td>
            <td class="text-sm"><?= fmt_num((int) $a['photo_count']) ?> / <?= fmt_num((int) $a['video_count']) ?></td>
            <td class="text-sm"><?= fmt_num((int) $a['views']) ?></td>
            <td>
              <span class="badge badge--<?= $a['status'] === 'published' ? 'ok' : 'muted' ?>">
                <?= $a['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
              </span>
            </td>
            <td>
              <div class="tbl__actions">
                <a class="icon-btn" href="<?= e(url('album.php?slug=' . urlencode($a['slug']))) ?>"
                   target="_blank" title="เปิดหน้าเว็บจริง"><?= icon('eye') ?></a>
                <a class="icon-btn" href="<?= e(url('admin-album.php?id=' . $a['id'])) ?>" title="จัดการ"><?= icon('edit') ?></a>
                <form method="post" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <button class="icon-btn" type="submit"
                          title="<?= $a['status'] === 'published' ? 'ย้ายกลับเป็นร่าง' : 'เผยแพร่' ?>">
                    <?= icon($a['status'] === 'published' ? 'lock' : 'check-circle') ?>
                  </button>
                </form>
                <form method="post" style="display:inline;"
                      data-confirm-submit="ลบอัลบั้ม &quot;<?= e($a['title']) ?>&quot; พร้อมรูปทั้งหมด <?= fmt_num((int) $a['photo_count']) ?> รูปถาวร? การลบนี้กู้คืนไม่ได้">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <button class="icon-btn" type="submit" title="ลบอัลบั้ม"><?= icon('trash') ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- create album -->
<div class="modal" data-new-album>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="modal__head">
        <h3 class="modal__title">สร้างอัลบั้มใหม่</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="na-title">ชื่ออัลบั้ม <span class="req">*</span></label>
          <input id="na-title" type="text" name="title" required maxlength="200"
                 placeholder="เช่น งานคอนเสิร์ต โรงเรียนดนตรี 2568">
        </div>
        <div class="field">
          <label for="na-client">ชื่อลูกค้า / โรงเรียน</label>
          <input id="na-client" type="text" name="client_name" maxlength="160" placeholder="ใช้สำหรับค้นหาภายใน">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="na-cat">หมวดหมู่</label>
            <select id="na-cat" name="category_id">
              <option value="">ไม่ระบุ</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="na-date">วันที่จัดงาน</label>
            <input id="na-date" type="date" name="event_date">
          </div>
        </div>
        <div class="field">
          <label for="na-desc">คำอธิบาย</label>
          <textarea id="na-desc" name="description" rows="3"
                    placeholder="บอกลูกค้าสั้น ๆ ว่าอัลบั้มนี้เก็บช่วงไหนของงานบ้าง"></textarea>
        </div>
        <p class="hint mb-0">อัลบั้มใหม่จะถูกสร้างเป็น "ร่าง" ก่อน เผยแพร่ได้เมื่ออัปโหลดรูปเสร็จแล้ว</p>
      </div>

      <div class="modal__foot">
        <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
        <button class="btn btn--primary" type="submit">สร้างอัลบั้ม</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
