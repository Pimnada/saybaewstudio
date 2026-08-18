<?php
/** ประเภทงาน — the tiles on the homepage and the list on services.php. */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash('กรุณากรอกชื่อประเภทงาน', 'error');
            redirect('admin-services.php');
        }

        $image = null;
        try {
            $image = save_simple_image($_FILES['image'] ?? [], 'services', 1200);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
            redirect('admin-services.php');
        }

        $desc   = trim((string) ($_POST['description'] ?? ''));
        $status = ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft';

        if ($id > 0) {
            $sql = 'UPDATE services SET title = ?, slug = ?, description = ?, status = ?'
                 . ($image ? ', image = ?' : '') . ' WHERE id = ?';
            $args = [$title, unique_slug('services', $title, $id), $desc, $status];
            if ($image) { $args[] = $image; }
            $args[] = $id;
            $pdo->prepare($sql)->execute($args);
            flash('บันทึกประเภทงานแล้ว');
        } else {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM services')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO services (title, slug, description, image, status, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$title, unique_slug('services', $title), $desc, $image, $status, $max + 1]);
            flash('เพิ่มประเภทงานใหม่แล้ว');
        }
        redirect('admin-services.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
        flash('ลบประเภทงานแล้ว');
        redirect('admin-services.php');
    }
}

$services = $pdo->query('SELECT * FROM services ORDER BY sort_order, id')->fetchAll();

$admin_title  = 'ประเภทงาน';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ประเภทงาน', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ประเภทงานที่รับถ่าย</h1>
    <p class="text-sm text-muted mb-0">แสดงเป็นการ์ดรูปบนหน้าแรก และเป็นตัวเลือกในฟอร์มติดต่อ</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-svc-modal]" data-new-svc>
    <?= icon('plus', '', 18) ?> เพิ่มประเภทงาน
  </button>
</div>

<div class="panel">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:36px;"></th>
          <th style="width:76px;">รูป</th>
          <th style="width:220px;">ชื่อ</th>
          <th>คำอธิบาย</th>
          <th style="width:100px;">สถานะ</th>
          <th style="width:90px;"></th>
        </tr>
      </thead>
      <tbody data-sortable="<?= e(url('api-sort.php?table=services')) ?>">
      <?php foreach ($services as $s): ?>
        <tr data-sort-id="<?= (int) $s['id'] ?>" draggable="true">
          <td class="sortable-handle"><?= icon('drag', '', 18) ?></td>
          <td><img class="thumb" src="<?= e($s['image'] ? upload_url($s['image']) : url('assets/img/placeholder.svg')) ?>" alt=""></td>
          <td>
            <div class="fw-700"><?= e($s['title']) ?></div>
            <div class="text-xs text-faint"><?= e($s['slug']) ?></div>
          </td>
          <td class="text-sm text-muted"><?= e(excerpt($s['description'], 90)) ?></td>
          <td>
            <span class="badge badge--<?= $s['status'] === 'published' ? 'ok' : 'muted' ?>">
              <?= $s['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
            </span>
          </td>
          <td>
            <div class="tbl__actions">
              <button class="icon-btn" type="button" title="แก้ไข"
                      data-edit-svc="<?= e(json_encode($s, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
              <form method="post" data-confirm-submit="ลบ &quot;<?= e($s['title']) ?>&quot;?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" data-svc-modal>
  <div class="modal__box">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มประเภทงาน</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="sv-title">ชื่อประเภทงาน <span class="req">*</span></label>
          <input id="sv-title" type="text" name="title" data-f="title" required maxlength="160">
        </div>
        <div class="field">
          <label for="sv-desc">คำอธิบายสั้น</label>
          <textarea id="sv-desc" name="description" data-f="description" rows="3"></textarea>
        </div>
        <div class="field">
          <label for="sv-image">รูปประกอบ</label>
          <img id="sv-preview" src="<?= asset('assets/img/placeholder.svg') ?>" alt=""
               style="width:100%;max-height:150px;object-fit:cover;border-radius:var(--r-sm);margin-bottom:8px;">
          <input id="sv-image" type="file" name="image" accept="image/*" data-preview="#sv-preview">
          <p class="hint">แนะนำสัดส่วน 4:3 ขนาดอย่างน้อย 800×600 พิกเซล</p>
        </div>
        <div class="field mb-0">
          <label for="sv-status">สถานะ</label>
          <select id="sv-status" name="status" data-f="status">
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
  var modal = A.$('[data-svc-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('#sv-preview').src = data.image
      ? window.SBS.base + '/uploads/' + data.image
      : <?= ejs(url('assets/img/placeholder.svg')) ?>;
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขประเภทงาน' : 'เพิ่มประเภทงาน';
  }

  A.$$('[data-edit-svc]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-svc')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-svc]').forEach(function (btn) {
    btn.addEventListener('click', function () { fill({ id: '', status: 'published' }); });
  });
});
</script>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
