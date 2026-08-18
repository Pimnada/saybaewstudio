<?php
/** Admin sign-in. */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../inc/icons.php';

if (current_user()) {
    redirect('admin.php');
}

$next  = (string) ($_GET['next'] ?? 'admin.php');
$error = '';

if (is_post()) {
    csrf_check();

    $email    = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    // Five failures from one address in fifteen minutes is a robot, not a typist.
    boot_session();
    $bucket = $_SESSION['login_fails'] ?? ['n' => 0, 'at' => 0];
    if ($bucket['n'] >= 5 && time() - $bucket['at'] < 900) {
        $error = 'พยายามเข้าสู่ระบบผิดหลายครั้งเกินไป กรุณารอ 15 นาทีแล้วลองใหม่';
    } elseif (attempt_login($email, $password)) {
        unset($_SESSION['login_fails']);
        $to = basename($next);
        redirect(preg_match('/^admin[a-z0-9\-]*\.php$/', $to) ? $to : 'admin.php');
    } else {
        $_SESSION['login_fails'] = [
            'n'  => (time() - $bucket['at'] < 900 ? $bucket['n'] : 0) + 1,
            'at' => time(),
        ];
        $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>เข้าสู่ระบบ — <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<script>
  (function () {
    try {
      var t = localStorage.getItem('sbs-theme');
      if (!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', t);
    } catch (e) {}
  })();
</script>
</head>
<body class="login-page">

<div class="login-card">
  <div class="text-center mb-24">
    <span class="brand__mark" style="margin:0 auto 14px;width:56px;height:56px;color:var(--gold);border-color:var(--gold);">
      <?= icon('camera', '', 28) ?>
    </span>
    <h1 style="font-size:21px;margin-bottom:2px;"><?= e(setting('site_name')) ?></h1>
    <p class="text-sm text-muted mb-0">ระบบจัดการเว็บไซต์และอัลบั้มภาพ</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert--error"><?= icon('help', '', 20) ?><span><?= e($error) ?></span></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">อีเมล</label>
      <input id="email" type="email" name="email" required autofocus autocomplete="username"
             placeholder="admin@saybaewstudio.com" value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password">รหัสผ่าน</label>
      <input id="password" type="password" name="password" required autocomplete="current-password"
             placeholder="••••••••">
    </div>
    <button class="btn btn--primary btn--block btn--lg" type="submit">เข้าสู่ระบบ</button>
  </form>

  <p class="text-xs text-faint text-center mt-24 mb-0">
    ลืมรหัสผ่าน? ติดต่อผู้ดูแลระบบที่ <?= e(setting('contact_email')) ?>
  </p>
</div>

</body>
</html>
