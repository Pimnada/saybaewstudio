<?php
/** รีวิวจากลูกค้า. */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../image.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $avatar = null;
        try {
            $avatar = save_simple_image($_FILES['avatar'] ?? [], 'reviews', 400);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
            redirect('admin-reviews.php');
        }

        $fields = [
            trim((string) ($_POST['name'] ?? '')),
            trim((string) ($_POST['role'] ?? '')),
            max(1, min(5, (int) ($_POST['rating'] ?? 5))),
            trim((string) ($_POST['body'] ?? '')),
            ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft',
        ];

        if ($id > 0) {
            $sql = 'UPDATE reviews SET name = ?, role = ?, rating = ?, body = ?, status = ?'
                 . ($avatar ? ', avatar = ?' : '') . ' WHERE id = ?';
            $pdo->prepare($sql)->execute(array_merge($fields, $avatar ? [$avatar] : [], [$id]));
            flash('บันทึกรีวิวแล้ว');
        } else {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM reviews')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO reviews (name, role, rating, body, status, avatar, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute(array_merge($fields, [$avatar, $max + 1]));
            flash('เพิ่มรีวิวใหม่แล้ว');
        }
        redirect('admin-reviews.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
        flash('ลบรีวิวแล้ว');
        redirect('admin-reviews.php');
    }
}

$reviews = $pdo->query('SELECT * FROM reviews ORDER BY sort_order, id')->fetchAll();

$admin_title  = 'รีวิวจากลูกค้า';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['รีวิวจากลูกค้า', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">รีวิวจากลูกค้า</h1>
    <p class="text-sm text-muted mb-0">แสดงบนหน้าแรกและหน้ารีวิว · ลากแถวเพื่อจัดลำดับใหม่</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-review-modal]" data-new-review>
    <?= icon('plus', '', 18) ?> เพิ่มรีวิว
  </button>
</div>

<div class="panel">
  <?php if (!$reviews): ?>
    <div class="panel__body">
      <div class="empty-state" style="padding:40px 10px;"><?= icon('star', '', 56) ?><p>ยังไม่มีรีวิว</p></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:36px;"></th>
            <th style="width:60px;">รูป</th>
            <th style="width:180px;">ชื่อ</th>
            <th>ข้อความ</th>
            <th style="width:110px;">คะแนน</th>
            <th style="width:100px;">สถานะ</th>
            <th style="width:90px;"></th>
          </tr>
        </thead>
        <tbody data-sortable="<?= e(url('api-sort.php?table=reviews')) ?>">
        <?php foreach ($reviews as $r): ?>
          <tr data-sort-id="<?= (int) $r['id'] ?>" draggable="true">
            <td class="sortable-handle"><?= icon('drag', '', 18) ?></td>
            <td>
              <img class="thumb" style="width:40px;height:40px;border-radius:50%;"
                   src="<?= e($r['avatar'] ? upload_url($r['avatar']) : url('assets/img/avatar.svg')) ?>" alt="">
            </td>
            <td>
              <div class="fw-700"><?= e($r['name']) ?></div>
              <div class="text-xs text-faint"><?= e($r['role']) ?></div>
            </td>
            <td class="text-sm text-muted"><?= e(excerpt($r['body'], 90)) ?></td>
            <td><?= star_row((int) $r['rating']) ?></td>
            <td>
              <span class="badge badge--<?= $r['status'] === 'published' ? 'ok' : 'muted' ?>">
                <?= $r['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
              </span>
            </td>
            <td>
              <div class="tbl__actions">
                <button class="icon-btn" type="button" title="แก้ไข"
                        data-edit-review="<?= e(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
                <form method="post" data-confirm-submit="ลบรีวิวของ <?= e($r['name']) ?>?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
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

<div class="modal" data-review-modal>
  <div class="modal__box">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มรีวิว</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="row mb-16" style="gap:14px;">
          <img id="rv-preview" src="<?= asset('assets/img/avatar.svg') ?>" alt=""
               style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1.5px solid var(--line);">
          <div style="flex:1;">
            <label class="label" for="rv-avatar">รูปโปรไฟล์</label>
            <input id="rv-avatar" type="file" name="avatar" accept="image/*" data-preview="#rv-preview">
          </div>
        </div>

        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="rv-name">ชื่อ <span class="req">*</span></label>
            <input id="rv-name" type="text" name="name" data-f="name" required maxlength="120">
          </div>
          <div class="field">
            <label for="rv-role">บทบาท</label>
            <input id="rv-role" type="text" name="role" data-f="role" maxlength="120" placeholder="เช่น ผู้ปกครอง">
          </div>
        </div>

        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="rv-rating">คะแนน</label>
            <select id="rv-rating" name="rating" data-f="rating">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= str_repeat('★', $i) ?> (<?= $i ?>)</option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="field">
            <label for="rv-status">สถานะ</label>
            <select id="rv-status" name="status" data-f="status">
              <option value="published">เผยแพร่</option>
              <option value="draft">ร่าง</option>
            </select>
          </div>
        </div>

        <div class="field mb-0">
          <label for="rv-body">ข้อความรีวิว <span class="req">*</span></label>
          <textarea id="rv-body" name="body" data-f="body" rows="4" required></textarea>
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
  var modal = A.$('[data-review-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('#rv-preview').src = data.avatar
      ? window.SBS.base + '/uploads/' + data.avatar
      : <?= ejs(url('assets/img/avatar.svg')) ?>;
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขรีวิว' : 'เพิ่มรีวิว';
  }

  A.$$('[data-edit-review]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-review')));
      A.openModal(modal);
    });
  });

  A.$$('[data-new-review]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill({ id: '', rating: 5, status: 'published' });
    });
  });
});
</script>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
