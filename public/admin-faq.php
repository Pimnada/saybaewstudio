<?php
/** FAQ. */

require_once __DIR__ . '/../auth.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $q      = trim((string) ($_POST['question'] ?? ''));
        $a      = trim((string) ($_POST['answer'] ?? ''));
        $status = ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft';

        if ($q === '') {
            flash('กรุณากรอกคำถาม', 'error');
            redirect('admin-faq.php');
        }
        if ($id > 0) {
            $pdo->prepare('UPDATE faqs SET question = ?, answer = ?, status = ? WHERE id = ?')
                ->execute([$q, $a, $status, $id]);
            flash('บันทึกคำถามแล้ว');
        } else {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM faqs')->fetchColumn();
            $pdo->prepare('INSERT INTO faqs (question, answer, status, sort_order) VALUES (?, ?, ?, ?)')
                ->execute([$q, $a, $status, $max + 1]);
            flash('เพิ่มคำถามใหม่แล้ว');
        }
        redirect('admin-faq.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM faqs WHERE id = ?')->execute([$id]);
        flash('ลบคำถามแล้ว');
        redirect('admin-faq.php');
    }
}

$faqs = $pdo->query('SELECT * FROM faqs ORDER BY sort_order, id')->fetchAll();

$admin_title  = 'FAQ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['FAQ', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">คำถามที่พบบ่อย</h1>
    <p class="text-sm text-muted mb-0">แสดงบนหน้าแรกและหน้าติดต่อ · ลากแถวเพื่อจัดลำดับใหม่</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-faq-modal]" data-new-faq>
    <?= icon('plus', '', 18) ?> เพิ่มคำถาม
  </button>
</div>

<div class="panel">
  <?php if (!$faqs): ?>
    <div class="panel__body">
      <div class="empty-state" style="padding:40px 10px;"><?= icon('help', '', 56) ?><p>ยังไม่มีคำถาม</p></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:36px;"></th>
            <th style="width:34%;">คำถาม</th>
            <th>คำตอบ</th>
            <th style="width:100px;">สถานะ</th>
            <th style="width:90px;"></th>
          </tr>
        </thead>
        <tbody data-sortable="<?= e(url('api-sort.php?table=faqs')) ?>">
        <?php foreach ($faqs as $f): ?>
          <tr data-sort-id="<?= (int) $f['id'] ?>" draggable="true">
            <td class="sortable-handle"><?= icon('drag', '', 18) ?></td>
            <td class="fw-700"><?= e($f['question']) ?></td>
            <td class="text-sm text-muted"><?= e(excerpt($f['answer'], 110)) ?></td>
            <td>
              <span class="badge badge--<?= $f['status'] === 'published' ? 'ok' : 'muted' ?>">
                <?= $f['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
              </span>
            </td>
            <td>
              <div class="tbl__actions">
                <button class="icon-btn" type="button" title="แก้ไข"
                        data-edit-faq="<?= e(json_encode($f, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
                <form method="post" data-confirm-submit="ลบคำถามนี้?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
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

<div class="modal" data-faq-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มคำถาม</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="fq-q">คำถาม <span class="req">*</span></label>
          <input id="fq-q" type="text" name="question" data-f="question" required maxlength="255">
        </div>
        <div class="field">
          <label for="fq-a">คำตอบ</label>
          <textarea id="fq-a" name="answer" data-f="answer" rows="5"></textarea>
        </div>
        <div class="field mb-0">
          <label for="fq-status">สถานะ</label>
          <select id="fq-status" name="status" data-f="status">
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
  var modal = A.$('[data-faq-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขคำถาม' : 'เพิ่มคำถาม';
  }

  A.$$('[data-edit-faq]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-faq')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-faq]').forEach(function (btn) {
    btn.addEventListener('click', function () { fill({ id: '', status: 'published' }); });
  });
});
</script>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
