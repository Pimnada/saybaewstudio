<?php
/**
 * ตั้งค่าแชทอัตโนมัติ — keyword → canned reply.
 *
 * The rules are stored and previewable here. Wiring them to a live LINE or
 * Facebook webhook is a separate step that needs the studio's channel tokens;
 * until then this page is the single place those answers are written and kept
 * in step, and the tester below shows exactly which rule a message would hit.
 */

require_once __DIR__ . '/auth.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $keyword = trim((string) ($_POST['keyword'] ?? ''));
        $reply   = trim((string) ($_POST['reply'] ?? ''));
        if ($keyword === '' || $reply === '') {
            flash('กรุณากรอกทั้งคำที่ตรวจจับและข้อความตอบกลับ', 'error');
            redirect('admin-autoreply.php');
        }
        $match   = in_array($_POST['match_type'] ?? '', ['contains', 'exact', 'starts'], true)
            ? $_POST['match_type'] : 'contains';
        $channel = in_array($_POST['channel'] ?? '', ['all', 'line', 'facebook', 'instagram', 'web'], true)
            ? $_POST['channel'] : 'all';
        $status  = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'paused';

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE autoreplies SET keyword = ?, reply = ?, match_type = ?, channel = ?, status = ? WHERE id = ?'
            )->execute([$keyword, $reply, $match, $channel, $status, $id]);
            flash('บันทึกกฎการตอบกลับแล้ว');
        } else {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM autoreplies')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO autoreplies (keyword, reply, match_type, channel, status, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$keyword, $reply, $match, $channel, $status, $max + 1]);
            flash('เพิ่มกฎการตอบกลับใหม่แล้ว');
        }
        redirect('admin-autoreply.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM autoreplies WHERE id = ?')->execute([$id]);
        flash('ลบกฎแล้ว');
        redirect('admin-autoreply.php');
    }

    if ($action === 'toggle_all') {
        set_setting('autoreply_enabled', isset($_POST['enabled']) ? '1' : '0');
        flash('บันทึกการตั้งค่าแล้ว');
        redirect('admin-autoreply.php');
    }
}

$rules = $pdo->query('SELECT * FROM autoreplies ORDER BY sort_order, id')->fetchAll();

$matchLabels = ['contains' => 'มีคำนี้อยู่ในข้อความ', 'exact' => 'ตรงทั้งข้อความ', 'starts' => 'ขึ้นต้นด้วยคำนี้'];
$chanLabels  = ['all' => 'ทุกช่องทาง', 'line' => 'LINE', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'web' => 'เว็บไซต์'];

$admin_title  = 'ตั้งค่าแชทอัตโนมัติ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ตั้งค่าแชทอัตโนมัติ', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ตั้งค่าแชทอัตโนมัติ</h1>
    <p class="text-sm text-muted mb-0">ข้อความตอบกลับอัตโนมัติตามคำที่ลูกค้าพิมพ์เข้ามา</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-ar-modal]" data-new-ar>
    <?= icon('plus', '', 18) ?> เพิ่มกฎใหม่
  </button>
</div>

<div class="alert alert--info">
  <?= icon('robot', '', 20) ?>
  <span>
    กฎเหล่านี้ถูกเก็บไว้พร้อมใช้งานแล้ว การเชื่อมกับ LINE Official Account หรือเพจ Facebook
    ต้องใส่ Channel Token ของสตูดิโอเพิ่มในหน้าตั้งค่าระบบก่อน — ระหว่างนี้ใช้ช่องทดสอบด้านล่างดูได้ว่าข้อความไหนจะไปตรงกับกฎใด
  </span>
</div>

<form method="post" class="panel">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="toggle_all">
  <div class="panel__body row" style="gap:14px;">
    <label class="check" style="flex:1;">
      <input type="checkbox" name="enabled" <?= setting('autoreply_enabled', '1') === '1' ? 'checked' : '' ?>>
      <span><strong>เปิดใช้งานการตอบกลับอัตโนมัติ</strong><br>
        <span class="text-xs text-muted">ปิดสวิตช์นี้เพื่อหยุดการตอบอัตโนมัติทุกช่องทางชั่วคราว โดยไม่ต้องลบกฎ</span>
      </span>
    </label>
    <button class="btn btn--light btn--sm" type="submit">บันทึก</button>
  </div>
</form>

<div class="grid" style="grid-template-columns: minmax(0,1.6fr) minmax(0,1fr); gap:20px; align-items:start;">

  <div class="panel" style="margin:0;">
    <div class="panel__head">
      <h2 class="panel__title">กฎการตอบกลับ</h2>
      <span class="spacer"></span>
      <span class="text-xs text-faint">ระบบจะไล่จากบนลงล่างและใช้กฎแรกที่ตรง</span>
    </div>

    <?php if (!$rules): ?>
      <div class="panel__body">
        <div class="empty-state" style="padding:36px 10px;"><?= icon('robot', '', 52) ?><p>ยังไม่มีกฎการตอบกลับ</p></div>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:36px;"></th>
              <th style="width:170px;">คำที่ตรวจจับ</th>
              <th>ข้อความตอบกลับ</th>
              <th style="width:110px;">ช่องทาง</th>
              <th style="width:90px;"></th>
            </tr>
          </thead>
          <tbody data-sortable="<?= e(url('api-sort.php?table=autoreplies')) ?>">
          <?php foreach ($rules as $r): ?>
            <tr data-sort-id="<?= (int) $r['id'] ?>" draggable="true">
              <td class="sortable-handle"><?= icon('drag', '', 18) ?></td>
              <td>
                <div class="fw-700"><?= e($r['keyword']) ?></div>
                <div class="text-xs text-faint"><?= e($matchLabels[$r['match_type']] ?? '') ?></div>
                <?php if ($r['status'] !== 'active'): ?>
                  <span class="badge badge--muted">พัก</span>
                <?php endif; ?>
              </td>
              <td class="text-sm text-muted"><?= nl2br(e(excerpt($r['reply'], 130))) ?></td>
              <td class="text-sm"><?= e($chanLabels[$r['channel']] ?? $r['channel']) ?></td>
              <td>
                <div class="tbl__actions">
                  <button class="icon-btn" type="button" title="แก้ไข"
                          data-edit-ar="<?= e(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
                  <form method="post" data-confirm-submit="ลบกฎ &quot;<?= e($r['keyword']) ?>&quot;?">
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

  <div class="panel" style="margin:0;">
    <div class="panel__head"><h2 class="panel__title">ทดลองพิมพ์</h2></div>
    <div class="panel__body">
      <div class="field">
        <label for="ar-test">ลองพิมพ์ข้อความที่ลูกค้าอาจส่งมา</label>
        <input id="ar-test" type="text" placeholder="เช่น ราคาเท่าไหร่คะ">
      </div>
      <div id="ar-result" class="card" style="background:var(--bg-2);min-height:90px;">
        <span class="text-sm text-faint">พิมพ์ข้อความด้านบนเพื่อดูว่าระบบจะตอบว่าอะไร</span>
      </div>
    </div>
  </div>

</div>

<div class="modal" data-ar-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มกฎการตอบกลับ</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="ar-keyword">คำที่ตรวจจับ <span class="req">*</span></label>
          <input id="ar-keyword" type="text" name="keyword" data-f="keyword" required maxlength="190"
                 placeholder="เช่น ราคา">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="ar-match">วิธีเทียบ</label>
            <select id="ar-match" name="match_type" data-f="match_type">
              <?php foreach ($matchLabels as $k => $label): ?>
                <option value="<?= e($k) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="ar-chan">ช่องทาง</label>
            <select id="ar-chan" name="channel" data-f="channel">
              <?php foreach ($chanLabels as $k => $label): ?>
                <option value="<?= e($k) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label for="ar-reply">ข้อความตอบกลับ <span class="req">*</span></label>
          <textarea id="ar-reply" name="reply" data-f="reply" rows="6" required></textarea>
          <p class="hint">ขึ้นบรรทัดใหม่ได้ ระบบจะส่งตามที่พิมพ์ไว้ทุกตัวอักษร</p>
        </div>
        <div class="field mb-0" style="max-width:220px;">
          <label for="ar-status">สถานะ</label>
          <select id="ar-status" name="status" data-f="status">
            <option value="active">ใช้งาน</option>
            <option value="paused">พักไว้</option>
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
  var modal = A.$('[data-ar-modal]');
  var rules = <?= json_encode(array_map(static fn($r) => [
      'keyword' => $r['keyword'],
      'reply'   => $r['reply'],
      'match'   => $r['match_type'],
      'status'  => $r['status'],
  ], $rules), JSON_UNESCAPED_UNICODE) ?>;

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขกฎการตอบกลับ' : 'เพิ่มกฎการตอบกลับ';
  }

  A.$$('[data-edit-ar]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-ar')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-ar]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill({ id: '', status: 'active', match_type: 'contains', channel: 'all' });
    });
  });

  /* tester — mirrors the matching order the webhook will use */
  var test = A.$('#ar-test');
  var out  = A.$('#ar-result');
  if (test && out) {
    test.addEventListener('input', function () {
      var text = test.value.trim().toLowerCase();
      if (!text) {
        out.innerHTML = '<span class="text-sm text-faint">พิมพ์ข้อความด้านบนเพื่อดูว่าระบบจะตอบว่าอะไร</span>';
        return;
      }
      for (var i = 0; i < rules.length; i++) {
        var r = rules[i];
        if (r.status !== 'active') { continue; }
        var k = r.keyword.toLowerCase();
        var hit = r.match === 'exact' ? text === k
                : r.match === 'starts' ? text.indexOf(k) === 0
                : text.indexOf(k) !== -1;
        if (hit) {
          out.innerHTML =
            '<div class="text-xs text-gold fw-700 mb-8">ตรงกับกฎ "' + escapeHtml(r.keyword) + '"</div>' +
            '<div class="text-sm">' + escapeHtml(r.reply).replace(/\n/g, '<br>') + '</div>';
          return;
        }
      }
      out.innerHTML = '<span class="text-sm text-muted">ไม่ตรงกับกฎไหนเลย — ข้อความนี้จะรอให้ทีมงานตอบเอง</span>';
    });
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }
});
</script>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
