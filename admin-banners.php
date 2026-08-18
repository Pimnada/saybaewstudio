<?php
/** แบนเนอร์ — hero and promo images. */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';

$pdo = db();

$positions = [
    'hero'    => 'แบนเนอร์หน้าแรก (โมเสกรูป)',
    'promo'   => 'แถบโปรโมชัน',
    'sidebar' => 'ด้านข้าง',
];

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $image = null;
        try {
            $image = save_simple_image($_FILES['image'] ?? [], 'banners', 2000);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
            redirect('admin-banners.php');
        }
        if ($id === 0 && !$image) {
            flash('กรุณาเลือกรูปแบนเนอร์', 'error');
            redirect('admin-banners.php');
        }

        $args = [
            trim((string) ($_POST['title'] ?? '')),
            trim((string) ($_POST['subtitle'] ?? '')),
            trim((string) ($_POST['link'] ?? '')),
            isset($positions[$_POST['position'] ?? '']) ? $_POST['position'] : 'hero',
            ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft',
        ];

        if ($id > 0) {
            $sql = 'UPDATE banners SET title = ?, subtitle = ?, link = ?, position = ?, status = ?'
                 . ($image ? ', image = ?' : '') . ' WHERE id = ?';
            if ($image) { $args[] = $image; }
            $args[] = $id;
            $pdo->prepare($sql)->execute($args);
            flash('บันทึกแบนเนอร์แล้ว');
        } else {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM banners')->fetchColumn();
            $args[] = $image;
            $args[] = $max + 1;
            $pdo->prepare(
                'INSERT INTO banners (title, subtitle, link, position, status, image, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute($args);
            flash('เพิ่มแบนเนอร์ใหม่แล้ว');
        }
        redirect('admin-banners.php');
    }

    if ($action === 'delete') {
        $st = $pdo->prepare('SELECT image FROM banners WHERE id = ?');
        $st->execute([$id]);
        $img = $st->fetchColumn();
        if ($img && is_file(upload_path($img))) {
            @unlink(upload_path($img));
        }
        $pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
        flash('ลบแบนเนอร์แล้ว');
        redirect('admin-banners.php');
    }
}

$banners = $pdo->query('SELECT * FROM banners ORDER BY sort_order, id')->fetchAll();

$admin_title  = 'แบนเนอร์';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['แบนเนอร์', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">แบนเนอร์</h1>
    <p class="text-sm text-muted mb-0">รูปที่ใช้ตกแต่งหน้าแรกและแถบโปรโมชัน</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-bn-modal]" data-new-bn>
    <?= icon('plus', '', 18) ?> เพิ่มแบนเนอร์
  </button>
</div>

<div class="panel">
  <?php if (!$banners): ?>
    <div class="panel__body">
      <div class="empty-state" style="padding:40px 10px;">
        <?= icon('banner', '', 56) ?>
        <p>ยังไม่มีแบนเนอร์<br><span class="text-xs">ถ้าไม่เพิ่ม หน้าแรกจะใช้รูปจากอัลบั้มที่เผยแพร่แทนโดยอัตโนมัติ</span></p>
      </div>
    </div>
  <?php else: ?>
    <div class="ph-grid" data-size="lg">
      <?php foreach ($banners as $b): ?>
        <div class="ph">
          <div class="ph__media">
            <img src="<?= e(upload_url($b['image'])) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
          </div>
          <div class="ph__body">
            <div class="ph__name"><?= e($b['title'] ?: '(ไม่มีชื่อ)') ?></div>
            <div class="ph__meta">
              <span><?= e($positions[$b['position']] ?? $b['position']) ?></span>
              <span class="badge badge--<?= $b['status'] === 'published' ? 'ok' : 'muted' ?>">
                <?= $b['status'] === 'published' ? 'ใช้งาน' : 'ปิด' ?>
              </span>
            </div>
            <div class="row mt-8" style="gap:4px;">
              <button class="icon-btn" type="button" title="แก้ไข"
                      data-edit-bn="<?= e(json_encode($b, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
              <form method="post" data-confirm-submit="ลบแบนเนอร์นี้?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="modal" data-bn-modal>
  <div class="modal__box">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มแบนเนอร์</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="bn-image">รูปแบนเนอร์</label>
          <img id="bn-preview" src="<?= asset('assets/img/placeholder.svg') ?>" alt=""
               style="width:100%;max-height:170px;object-fit:cover;border-radius:var(--r-sm);margin-bottom:8px;">
          <input id="bn-image" type="file" name="image" accept="image/*" data-preview="#bn-preview">
        </div>
        <div class="field">
          <label for="bn-title">หัวข้อ</label>
          <input id="bn-title" type="text" name="title" data-f="title" maxlength="200">
        </div>
        <div class="field">
          <label for="bn-sub">คำบรรยาย</label>
          <input id="bn-sub" type="text" name="subtitle" data-f="subtitle" maxlength="255">
        </div>
        <div class="field">
          <label for="bn-link">ลิงก์เมื่อคลิก</label>
          <input id="bn-link" type="text" name="link" data-f="link" placeholder="albums.php">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="bn-pos">ตำแหน่ง</label>
            <select id="bn-pos" name="position" data-f="position">
              <?php foreach ($positions as $k => $label): ?>
                <option value="<?= e($k) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="bn-status">สถานะ</label>
            <select id="bn-status" name="status" data-f="status">
              <option value="published">ใช้งาน</option>
              <option value="draft">ปิด</option>
            </select>
          </div>
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
  var modal = A.$('[data-bn-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('#bn-preview').src = data.image
      ? window.SBS.base + '/uploads/' + data.image
      : <?= ejs(url('assets/img/placeholder.svg')) ?>;
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขแบนเนอร์' : 'เพิ่มแบนเนอร์';
  }

  A.$$('[data-edit-bn]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-bn')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-bn]').forEach(function (btn) {
    btn.addEventListener('click', function () { fill({ id: '', status: 'published', position: 'hero' }); });
  });
});
</script>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
