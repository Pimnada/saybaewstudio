<?php
/** อัลบั้มวิดีโอ — every video across all albums, in one place. */

require_once __DIR__ . '/../auth.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $url   = trim((string) ($_POST['url'] ?? ''));
        if ($title === '' || $url === '') {
            flash('กรุณากรอกชื่อวิดีโอและลิงก์', 'error');
            redirect('admin-videos.php');
        }
        $albumId  = ($_POST['album_id'] ?? '') !== '' ? (int) $_POST['album_id'] : null;
        $duration = trim((string) ($_POST['duration'] ?? ''));
        $status   = ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft';

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE videos SET album_id = ?, title = ?, url = ?, duration = ?, status = ? WHERE id = ?'
            )->execute([$albumId, $title, $url, $duration, $status, $id]);
            flash('บันทึกวิดีโอแล้ว');
        } else {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM videos')->fetchColumn();
            $pdo->prepare(
                "INSERT INTO videos (album_id, title, provider, url, duration, status, sort_order)
                 VALUES (?, ?, 'youtube', ?, ?, ?, ?)"
            )->execute([$albumId, $title, $url, $duration, $status, $max + 1]);
            flash('เพิ่มวิดีโอใหม่แล้ว');
        }
        redirect('admin-videos.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
        flash('ลบวิดีโอแล้ว');
        redirect('admin-videos.php');
    }
}

$videos = $pdo->query(
    'SELECT v.*, a.title AS album_title, a.slug AS album_slug
       FROM videos v LEFT JOIN albums a ON a.id = v.album_id
      ORDER BY v.sort_order, v.id'
)->fetchAll();

$albums = $pdo->query('SELECT id, title FROM albums ORDER BY event_date DESC, id DESC')->fetchAll();

/** YouTube gives us a thumbnail for free — no need to store one. */
function youtube_id(?string $url): string
{
    if (!$url) {
        return '';
    }
    preg_match('/(?:v=|youtu\.be\/|embed\/|shorts\/)([A-Za-z0-9_\-]{6,})/', $url, $m);
    return $m[1] ?? '';
}

$admin_title  = 'อัลบั้มวิดีโอ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['อัลบั้มวิดีโอ', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">อัลบั้มวิดีโอ</h1>
    <p class="text-sm text-muted mb-0">
      วิดีโอทั้งหมด <?= fmt_num(count($videos)) ?> รายการ · ฝังจาก YouTube เพื่อไม่ให้กินพื้นที่เซิร์ฟเวอร์
    </p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-vd-modal]" data-new-vd>
    <?= icon('plus', '', 18) ?> เพิ่มวิดีโอ
  </button>
</div>

<div class="panel">
  <?php if (!$videos): ?>
    <div class="panel__body">
      <div class="empty-state" style="padding:40px 10px;">
        <?= icon('video', '', 56) ?>
        <p>ยังไม่มีวิดีโอ<br><span class="text-xs">อัปโหลดวิดีโอขึ้น YouTube แล้ววางลิงก์ที่นี่</span></p>
      </div>
    </div>
  <?php else: ?>
    <div class="ph-grid" data-size="lg">
      <?php foreach ($videos as $v): $yt = youtube_id($v['url']); ?>
        <div class="ph">
          <div class="ph__media">
            <?php if ($yt): ?>
              <img src="https://i.ytimg.com/vi/<?= e($yt) ?>/mqdefault.jpg" alt="<?= e($v['title']) ?>" loading="lazy">
            <?php else: ?>
              <img src="<?= asset('assets/img/placeholder.svg') ?>" alt="">
            <?php endif; ?>
          </div>
          <div class="ph__body">
            <div class="ph__name" title="<?= e($v['title']) ?>"><?= e($v['title']) ?></div>
            <div class="ph__meta">
              <span><?= e($v['album_title'] ?: 'ไม่ผูกกับอัลบั้ม') ?></span>
              <span><?= e($v['duration'] ?: '—') ?></span>
            </div>
            <div class="row mt-8" style="gap:4px;">
              <a class="icon-btn" href="<?= e($v['url']) ?>" target="_blank" rel="noopener"
                 title="เปิดบน YouTube"><?= icon('play') ?></a>
              <button class="icon-btn" type="button" title="แก้ไข"
                      data-edit-vd="<?= e(json_encode($v, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
              <form method="post" data-confirm-submit="ลบวิดีโอ &quot;<?= e($v['title']) ?>&quot;?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
              </form>
              <span class="spacer"></span>
              <span class="badge badge--<?= $v['status'] === 'published' ? 'ok' : 'muted' ?>">
                <?= $v['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="modal" data-vd-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มวิดีโอ</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="vd-title">ชื่อวิดีโอ <span class="req">*</span></label>
          <input id="vd-title" type="text" name="title" data-f="title" required maxlength="200">
        </div>
        <div class="field">
          <label for="vd-url">ลิงก์ YouTube <span class="req">*</span></label>
          <input id="vd-url" type="url" name="url" data-f="url" required
                 placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="vd-album">อยู่ในอัลบั้ม</label>
            <select id="vd-album" name="album_id" data-f="album_id">
              <option value="">ไม่ผูกกับอัลบั้ม</option>
              <?php foreach ($albums as $a): ?>
                <option value="<?= (int) $a['id'] ?>"><?= e($a['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="vd-dur">ความยาว</label>
            <input id="vd-dur" type="text" name="duration" data-f="duration" placeholder="3:24">
          </div>
        </div>
        <div class="field mb-0" style="max-width:220px;">
          <label for="vd-status">สถานะ</label>
          <select id="vd-status" name="status" data-f="status">
            <option value="published">เผยแพร่</option>
            <option value="draft">ร่าง</option>
          </select>
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
// admin.js is deferred, so it has run and defined SBSAdmin by the time
// DOMContentLoaded fires — but not yet when this inline block is parsed.
document.addEventListener('DOMContentLoaded', function () {
  var A = window.SBSAdmin;
  var modal = A.$('[data-vd-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขวิดีโอ' : 'เพิ่มวิดีโอ';
  }

  A.$$('[data-edit-vd]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-vd')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-vd]').forEach(function (btn) {
    btn.addEventListener('click', function () { fill({ id: '', status: 'published' }); });
  });
});
</script>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
