<?php
/** โปรไฟล์ของฉัน — name, avatar and password for the signed-in account. */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';

$me  = require_admin();
$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile') {
        $name  = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if ($name === '') {
            flash('กรุณากรอกชื่อ', 'error');
            redirect('admin-profile.php');
        }

        $avatar = null;
        try {
            $avatar = save_simple_image($_FILES['avatar'] ?? [], 'avatars', 400);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
            redirect('admin-profile.php');
        }

        $sql  = 'UPDATE users SET name = ?, phone = ?' . ($avatar ? ', avatar = ?' : '') . ' WHERE id = ?';
        $args = [$name, $phone];
        if ($avatar) { $args[] = $avatar; }
        $args[] = $me['id'];
        $pdo->prepare($sql)->execute($args);

        flash('บันทึกโปรไฟล์แล้ว');
        redirect('admin-profile.php');
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!password_verify($current, $me['password_hash'])) {
            flash('รหัสผ่านปัจจุบันไม่ถูกต้อง', 'error');
            redirect('admin-profile.php');
        }
        if (mb_strlen($new) < 8) {
            flash('รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัวอักษร', 'error');
            redirect('admin-profile.php');
        }
        if ($new !== $confirm) {
            flash('รหัสผ่านใหม่และการยืนยันไม่ตรงกัน', 'error');
            redirect('admin-profile.php');
        }

        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        log_activity('user.password', $me['email']);
        flash('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        redirect('admin-profile.php');
    }
}

$myLog = $pdo->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY id DESC LIMIT 15');
$myLog->execute([$me['id']]);
$myLog = $myLog->fetchAll();

$admin_title  = 'โปรไฟล์ของฉัน';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['โปรไฟล์ของฉัน', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<h1 class="page-title mb-24">โปรไฟล์ของฉัน</h1>

<div class="grid grid-2" style="align-items:start;">

  <form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="profile">
    <div class="panel__head"><h2 class="panel__title">ข้อมูลส่วนตัว</h2></div>
    <div class="panel__body">
      <div class="row mb-16" style="gap:16px;">
        <img id="pf-preview" alt=""
             src="<?= e($me['avatar'] ? upload_url($me['avatar']) : url('assets/img/avatar.svg')) ?>"
             style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);">
        <div style="flex:1;">
          <label class="label" for="pf-avatar">รูปโปรไฟล์</label>
          <input id="pf-avatar" type="file" name="avatar" accept="image/*" data-preview="#pf-preview">
        </div>
      </div>
      <div class="field">
        <label for="pf-name">ชื่อ <span class="req">*</span></label>
        <input id="pf-name" type="text" name="name" required value="<?= e($me['name']) ?>" maxlength="120">
      </div>
      <div class="field">
        <label for="pf-phone">เบอร์โทร</label>
        <input id="pf-phone" type="text" name="phone" value="<?= e($me['phone']) ?>" maxlength="40">
      </div>
      <div class="field mb-0">
        <label>อีเมล (ใช้เข้าระบบ)</label>
        <input type="text" value="<?= e($me['email']) ?>" disabled>
        <p class="hint">เปลี่ยนอีเมลได้จากหน้าผู้ใช้งาน โดยผู้ดูแลระบบ</p>
      </div>
    </div>
    <div class="panel__foot">
      <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกโปรไฟล์</button>
      <span class="badge badge--<?= $me['role'] === 'owner' ? 'info' : 'muted' ?>"><?= e(role_label($me['role'])) ?></span>
    </div>
  </form>

  <div>
    <form class="panel" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <div class="panel__head"><h2 class="panel__title">เปลี่ยนรหัสผ่าน</h2></div>
      <div class="panel__body">
        <div class="field">
          <label for="pw-cur">รหัสผ่านปัจจุบัน <span class="req">*</span></label>
          <input id="pw-cur" type="password" name="current" required autocomplete="current-password">
        </div>
        <div class="field">
          <label for="pw-new">รหัสผ่านใหม่ <span class="req">*</span></label>
          <input id="pw-new" type="password" name="new" required minlength="8" autocomplete="new-password">
          <p class="hint">อย่างน้อย 8 ตัวอักษร</p>
        </div>
        <div class="field mb-0">
          <label for="pw-conf">ยืนยันรหัสผ่านใหม่ <span class="req">*</span></label>
          <input id="pw-conf" type="password" name="confirm" required minlength="8" autocomplete="new-password">
        </div>
      </div>
      <div class="panel__foot">
        <button class="btn btn--primary" type="submit"><?= icon('lock', '', 16) ?> เปลี่ยนรหัสผ่าน</button>
      </div>
    </form>

    <div class="panel">
      <div class="panel__head"><h2 class="panel__title">กิจกรรมล่าสุดของฉัน</h2></div>
      <div class="table-wrap">
        <table class="tbl">
          <tbody>
          <?php foreach ($myLog as $l): ?>
            <tr>
              <td class="text-sm"><span class="badge badge--muted"><?= e($l['action']) ?></span> <?= e($l['detail']) ?></td>
              <td class="text-right text-xs text-faint" style="width:110px;"><?= e(time_ago($l['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$myLog): ?>
            <tr><td class="text-sm text-muted" style="padding:20px;">ยังไม่มีกิจกรรม</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
