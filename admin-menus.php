<?php
/** เมนู & การนำทาง — the header and footer link lists. */

require_once __DIR__ . '/auth.php';

$pdo = db();

$locations = [
    'header'      => 'เมนูด้านบน',
    'footer_menu' => 'เมนูในฟุตเตอร์',
];

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $label = trim((string) ($_POST['label'] ?? ''));
        $url   = trim((string) ($_POST['url'] ?? ''));
        if ($label === '' || $url === '') {
            flash('กรุณากรอกทั้งชื่อเมนูและลิงก์', 'error');
            redirect('admin-menus.php');
        }
        $loc    = isset($locations[$_POST['location'] ?? '']) ? $_POST['location'] : 'header';
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'hidden';

        if ($id > 0) {
            $pdo->prepare('UPDATE menus SET label = ?, url = ?, location = ?, status = ? WHERE id = ?')
                ->execute([$label, $url, $loc, $status, $id]);
            flash('บันทึกเมนูแล้ว');
        } else {
            $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM menus WHERE location = ?');
            $max->execute([$loc]);
            $pdo->prepare('INSERT INTO menus (label, url, location, status, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([$label, $url, $loc, $status, (int) $max->fetchColumn() + 1]);
            flash('เพิ่มเมนูใหม่แล้ว');
        }
        redirect('admin-menus.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM menus WHERE id = ?')->execute([$id]);
        flash('ลบเมนูแล้ว');
        redirect('admin-menus.php');
    }
}

$byLocation = [];
foreach ($pdo->query('SELECT * FROM menus ORDER BY location, sort_order, id') as $row) {
    $byLocation[$row['location']][] = $row;
}

$admin_title  = 'เมนู & การนำทาง';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['เมนู & การนำทาง', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">เมนู &amp; การนำทาง</h1>
    <p class="text-sm text-muted mb-0">ลากแถวเพื่อจัดลำดับเมนูบนหน้าเว็บจริง</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-mn-modal]" data-new-mn>
    <?= icon('plus', '', 18) ?> เพิ่มเมนู
  </button>
</div>

<div class="grid grid-2" style="align-items:start;">
  <?php foreach ($locations as $key => $label): ?>
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title"><?= e($label) ?></h2>
        <span class="spacer"></span>
        <span class="text-xs text-faint"><?= fmt_num(count($byLocation[$key] ?? [])) ?> รายการ</span>
      </div>
      <div class="table-wrap">
        <table class="tbl">
          <tbody data-sortable="<?= e(url('api-sort.php?table=menus')) ?>">
          <?php foreach ($byLocation[$key] ?? [] as $m): ?>
            <tr data-sort-id="<?= (int) $m['id'] ?>" draggable="true">
              <td class="sortable-handle" style="width:36px;"><?= icon('drag', '', 18) ?></td>
              <td>
                <div class="fw-700"><?= e($m['label']) ?></div>
                <div class="text-xs text-faint"><?= e($m['url']) ?></div>
              </td>
              <td style="width:80px;">
                <?php if ($m['status'] !== 'active'): ?>
                  <span class="badge badge--muted">ซ่อน</span>
                <?php endif; ?>
              </td>
              <td style="width:90px;">
                <div class="tbl__actions">
                  <button class="icon-btn" type="button" title="แก้ไข"
                          data-edit-mn="<?= e(json_encode($m, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
                  <form method="post" data-confirm-submit="ลบเมนู &quot;<?= e($m['label']) ?>&quot;?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($byLocation[$key])): ?>
            <tr><td class="text-sm text-muted" style="padding:20px;">ยังไม่มีเมนูในตำแหน่งนี้</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">ลิงก์ที่ใช้ได้</h2></div>
  <div class="panel__body">
    <p class="text-sm text-muted">
      ใส่เป็นชื่อไฟล์ตรง ๆ ได้เลย เช่น <code>index.php</code>, <code>albums.php</code>,
      <code>services.php</code>, <code>reviews.php</code>, <code>blog.php</code>,
      <code>contact.php</code>, <code>page.php?slug=privacy</code>
      หรือใส่ URL เต็มขึ้นต้นด้วย https:// สำหรับลิงก์ภายนอก
    </p>
  </div>
</div>

<div class="modal" data-mn-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มเมนู</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="mn-label">ชื่อเมนู <span class="req">*</span></label>
          <input id="mn-label" type="text" name="label" data-f="label" required maxlength="120">
        </div>
        <div class="field">
          <label for="mn-url">ลิงก์ <span class="req">*</span></label>
          <input id="mn-url" type="text" name="url" data-f="url" required placeholder="albums.php">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="mn-loc">ตำแหน่ง</label>
            <select id="mn-loc" name="location" data-f="location">
              <?php foreach ($locations as $k => $label): ?>
                <option value="<?= e($k) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="mn-status">สถานะ</label>
            <select id="mn-status" name="status" data-f="status">
              <option value="active">แสดง</option>
              <option value="hidden">ซ่อน</option>
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
  var modal = A.$('[data-mn-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขเมนู' : 'เพิ่มเมนู';
  }

  A.$$('[data-edit-mn]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-mn')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-mn]').forEach(function (btn) {
    btn.addEventListener('click', function () { fill({ id: '', status: 'active', location: 'header' }); });
  });
});
</script>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
