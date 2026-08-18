<?php
/** ผู้ใช้งาน — the studio's team accounts. Owner only. */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../mailer.php';

$user = require_owner();
$pdo  = db();

$roles = ['owner' => 'ผู้ดูแลระบบ', 'editor' => 'ผู้จัดการเนื้อหา', 'staff' => 'ทีมงาน'];

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $role  = isset($roles[$_POST['role'] ?? '']) ? $_POST['role'] : 'staff';
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $status= ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'disabled';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('กรุณากรอกชื่อและอีเมลให้ถูกต้อง', 'error');
            redirect('admin-users.php');
        }

        $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $dup->execute([$email, $id]);
        if ($dup->fetchColumn()) {
            flash('อีเมลนี้ถูกใช้กับบัญชีอื่นแล้ว', 'error');
            redirect('admin-users.php');
        }

        if ($id > 0) {
            // Never let the last owner demote or disable themselves out of the system.
            $owners = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner' AND status = 'active'")
                ->fetchColumn();
            $st = $pdo->prepare('SELECT role, status FROM users WHERE id = ?');
            $st->execute([$id]);
            $before = $st->fetch();
            if ($owners <= 1 && $before['role'] === 'owner' && ($role !== 'owner' || $status !== 'active')) {
                flash('ต้องมีผู้ดูแลระบบที่ใช้งานอยู่อย่างน้อยหนึ่งคน', 'error');
                redirect('admin-users.php');
            }

            $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, phone = ?, status = ? WHERE id = ?')
                ->execute([$name, $email, $role, $phone, $status, $id]);

            if (($_POST['password'] ?? '') !== '') {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $id]);
            }
            flash('บันทึกบัญชีผู้ใช้แล้ว');
        } else {
            $temp = (string) ($_POST['password'] ?? '');
            if ($temp === '') {
                $temp = 'Sbs-' . bin2hex(random_bytes(3));
            }
            $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, phone, status) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$name, $email, password_hash($temp, PASSWORD_DEFAULT), $role, $phone, $status]);

            if (isset($_POST['send_invite'])) {
                send_email($email, 'user-invite', [
                    'name'          => $name,
                    'email'         => $email,
                    'temp_password' => $temp,
                    'role_label'    => $roles[$role],
                    'login_url'     => url('admin-login.php'),
                ]);
            }
            flash('สร้างบัญชี ' . $name . ' แล้ว · รหัสผ่านชั่วคราว: ' . $temp);
        }
        redirect('admin-users.php');
    }

    if ($action === 'delete') {
        if ($id === (int) $user['id']) {
            flash('ลบบัญชีของตัวเองไม่ได้', 'error');
            redirect('admin-users.php');
        }
        $owners = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner' AND status = 'active'")
            ->fetchColumn();
        $st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([$id]);
        if ($owners <= 1 && $st->fetchColumn() === 'owner') {
            flash('ต้องมีผู้ดูแลระบบอย่างน้อยหนึ่งคน', 'error');
            redirect('admin-users.php');
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        log_activity('user.delete', 'id=' . $id);
        flash('ลบบัญชีแล้ว');
        redirect('admin-users.php');
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY role, name')->fetchAll();
$log   = $pdo->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 30')->fetchAll();

$admin_title  = 'ผู้ใช้งาน';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ผู้ใช้งาน', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ผู้ใช้งาน</h1>
    <p class="text-sm text-muted mb-0">บัญชีที่เข้าใช้ระบบหลังบ้านได้</p>
  </div>
  <button class="btn btn--primary" type="button" data-modal-open="[data-us-modal]" data-new-us>
    <?= icon('plus', '', 18) ?> เพิ่มผู้ใช้งาน
  </button>
</div>

<div class="panel">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:56px;"></th>
          <th>ชื่อ</th>
          <th style="width:230px;">อีเมล</th>
          <th style="width:150px;">สิทธิ์</th>
          <th style="width:170px;">เข้าระบบล่าสุด</th>
          <th style="width:100px;">สถานะ</th>
          <th style="width:90px;"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <img class="thumb" style="width:38px;height:38px;border-radius:50%;"
                 src="<?= e($u['avatar'] ? upload_url($u['avatar']) : url('assets/img/avatar.svg')) ?>" alt="">
          </td>
          <td>
            <div class="fw-700"><?= e($u['name']) ?></div>
            <?php if ($u['phone']): ?><div class="text-xs text-faint"><?= e($u['phone']) ?></div><?php endif; ?>
          </td>
          <td class="text-sm"><?= e($u['email']) ?></td>
          <td>
            <span class="badge badge--<?= $u['role'] === 'owner' ? 'info' : 'muted' ?>">
              <?= e($roles[$u['role']] ?? $u['role']) ?>
            </span>
          </td>
          <td class="text-sm text-muted"><?= e($u['last_login_at'] ? thai_datetime($u['last_login_at']) : 'ยังไม่เคย') ?></td>
          <td>
            <span class="badge badge--<?= $u['status'] === 'active' ? 'ok' : 'danger' ?>">
              <?= $u['status'] === 'active' ? 'ใช้งาน' : 'ปิด' ?>
            </span>
          </td>
          <td>
            <div class="tbl__actions">
              <button class="icon-btn" type="button" title="แก้ไข"
                      data-edit-us="<?= e(json_encode([
                          'id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'],
                          'role' => $u['role'], 'phone' => $u['phone'], 'status' => $u['status'],
                      ], JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit') ?></button>
              <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                <form method="post" data-confirm-submit="ลบบัญชีของ <?= e($u['name']) ?>?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">บันทึกการใช้งานล่าสุด</h2></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th style="width:180px;">เวลา</th><th style="width:180px;">ผู้ใช้</th><th>การกระทำ</th></tr></thead>
      <tbody>
      <?php foreach ($log as $l): ?>
        <tr>
          <td class="text-sm text-muted"><?= e(thai_datetime($l['created_at'])) ?></td>
          <td class="text-sm"><?= e($l['user_name']) ?></td>
          <td class="text-sm"><span class="badge badge--muted"><?= e($l['action']) ?></span> <?= e($l['detail']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$log): ?>
        <tr><td colspan="3" class="text-sm text-muted" style="padding:20px;">ยังไม่มีบันทึก</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" data-us-modal>
  <div class="modal__box">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" data-f="id" value="">

      <div class="modal__head">
        <h3 class="modal__title" data-modal-heading>เพิ่มผู้ใช้งาน</h3>
        <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
      </div>

      <div class="modal__body">
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="us-name">ชื่อ <span class="req">*</span></label>
            <input id="us-name" type="text" name="name" data-f="name" required maxlength="120">
          </div>
          <div class="field">
            <label for="us-phone">เบอร์โทร</label>
            <input id="us-phone" type="text" name="phone" data-f="phone" maxlength="40">
          </div>
        </div>
        <div class="field">
          <label for="us-email">อีเมล (ใช้เข้าระบบ) <span class="req">*</span></label>
          <input id="us-email" type="email" name="email" data-f="email" required maxlength="190">
        </div>
        <div class="grid grid-2" style="gap:0 16px;">
          <div class="field">
            <label for="us-role">สิทธิ์การใช้งาน</label>
            <select id="us-role" name="role" data-f="role">
              <?php foreach ($roles as $k => $label): ?>
                <option value="<?= e($k) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="us-status">สถานะ</label>
            <select id="us-status" name="status" data-f="status">
              <option value="active">ใช้งาน</option>
              <option value="disabled">ปิดการใช้งาน</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label for="us-pass">รหัสผ่าน</label>
          <input id="us-pass" type="text" name="password" autocomplete="new-password"
                 placeholder="เว้นว่างเพื่อให้ระบบสุ่มให้ / ไม่เปลี่ยน">
          <p class="hint">แก้ไขบัญชีเดิม: เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน</p>
        </div>
        <label class="check mb-0">
          <input type="checkbox" name="send_invite" checked>
          <span>ส่งอีเมลเชิญเข้าใช้ระบบพร้อมรหัสผ่านชั่วคราว (เฉพาะตอนสร้างบัญชีใหม่)</span>
        </label>
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
  var modal = A.$('[data-us-modal]');

  function fill(data) {
    A.$$('[data-f]', modal).forEach(function (el) {
      el.value = data[el.getAttribute('data-f')] != null ? data[el.getAttribute('data-f')] : '';
    });
    A.$('#us-pass').value = '';
    A.$('[data-modal-heading]', modal).textContent = data.id ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งาน';
  }

  A.$$('[data-edit-us]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      fill(JSON.parse(btn.getAttribute('data-edit-us')));
      A.openModal(modal);
    });
  });
  A.$$('[data-new-us]').forEach(function (btn) {
    btn.addEventListener('click', function () { fill({ id: '', role: 'staff', status: 'active' }); });
  });
});
</script>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
