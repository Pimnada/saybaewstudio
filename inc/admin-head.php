<?php
/**
 * Admin shell: head, sidebar and topbar.
 *
 * A page sets $admin_title (and optionally $admin_crumbs) before including it,
 * and includes inc/admin-foot.php at the end.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/icons.php';

$user = require_admin();
$here = current_path();

/**
 * Walking uploads/ costs real time once an album holds a few thousand files,
 * so the total is cached for ten minutes in settings.
 */
function storage_used_bytes(): int
{
    $cached = (int) setting('storage_bytes', '0');
    $at     = (int) setting('storage_bytes_at', '0');
    if ($cached > 0 && time() - $at < 600) {
        return $cached;
    }
    $bytes = dir_size(upload_path());
    set_setting('storage_bytes', (string) $bytes);
    set_setting('storage_bytes_at', (string) time());
    return $bytes;
}

$quotaBytes = (int) (STORAGE_QUOTA_GB * 1024 * 1024 * 1024);
$usedBytes  = storage_used_bytes();
$usedPct    = $quotaBytes > 0 ? min(100, round($usedBytes / $quotaBytes * 100, 1)) : 0;

$newMessages = (int) db()->query("SELECT COUNT(*) FROM messages WHERE status = 'new'")->fetchColumn();

$adminNav = [
    'ภาพรวม' => [
        ['admin.php',        'แดชบอร์ด',        'home'],
        ['admin-stats.php',  'สถิติการเข้าชม',   'chart'],
    ],
    'ผลงาน' => [
        ['admin-albums.php', 'อัลบั้มภาพ',       'images'],
        ['admin-videos.php', 'อัลบั้มวิดีโอ',     'video'],
    ],
    'เนื้อหาเว็บไซต์' => [
        ['admin-pages.php',    'จัดการหน้าเว็บ',   'pages'],
        ['admin-articles.php', 'บทความ',          'article'],
        ['admin-reviews.php',  'รีวิวจากลูกค้า',    'star'],
        ['admin-faq.php',      'FAQ',             'help'],
        ['admin-banners.php',  'แบนเนอร์',        'banner'],
        ['admin-menus.php',    'เมนู & การนำทาง',  'nav-menu'],
        ['admin-services.php', 'ประเภทงาน',       'folder'],
    ],
    'ลูกค้า' => [
        ['admin-messages.php',  'ข้อความจากลูกค้า',   'inbox',   'messages'],
        ['admin-emails.php',    'อีเมลที่ส่งออก',      'mail'],
    ],
    'ตั้งค่า' => [
        ['admin-settings.php', 'ตั้งค่าเว็บไซต์',   'settings'],
        ['admin-contact.php',  'ข้อมูลการติดต่อ',  'phone'],
        ['admin-users.php',    'ผู้ใช้งาน',        'users'],
        ['admin-system.php',   'ตั้งค่าระบบ',      'server'],
    ],
];
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e(($admin_title ?? 'หลังบ้าน') . ' — ' . setting('site_name')) ?></title>

<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<script>
  // Light by default, same reasoning as the public site (see inc/header.php):
  // the operating system's setting is not consulted, so the admin opens in the
  // palette it was designed in. The toggle remembers a deliberate choice.
  (function () {
    try {
      document.documentElement.setAttribute(
        'data-theme', localStorage.getItem('sbs-theme') || 'light'
      );
    } catch (e) {}
  })();
  window.SBS = { csrf: <?= ejs(csrf_token()) ?>, base: <?= ejs(rtrim(SITE_URL, '/')) ?> };
</script>
</head>
<body class="admin">

<aside class="side" data-side>
  <div class="side__brand">
    <span class="brand__mark"><?= icon('camera') ?></span>
    <span>
      <span class="brand__name"><?= e(setting('site_name')) ?></span><br>
      <span class="brand__sub"><?= e(setting('site_name_en')) ?></span>
    </span>
  </div>

  <nav class="side__nav">
    <?php foreach ($adminNav as $group => $links): ?>
      <div class="side__group"><?= e($group) ?></div>
      <?php foreach ($links as $link): ?>
        <a class="side__link <?= $here === $link[0] ? 'is-active' : '' ?>" href="<?= e(url($link[0])) ?>">
          <?= icon($link[2]) ?>
          <span><?= e($link[1]) ?></span>
          <?php if (($link[3] ?? '') === 'messages' && $newMessages > 0): ?>
            <span class="side__badge"><?= $newMessages ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="side__foot">
    <div class="storage__row">
      <span>พื้นที่ใช้งาน</span><span><?= e((string) $usedPct) ?>%</span>
    </div>
    <div class="storage__row">
      <strong><?= e(fmt_bytes($usedBytes)) ?></strong>
      <span>/ <?= (int) STORAGE_QUOTA_GB ?> GB</span>
    </div>
    <div class="storage__bar"><div class="storage__fill" style="width: <?= e((string) $usedPct) ?>%"></div></div>
    <div class="side__copy">
      © <?= date('Y') + 543 ?> <?= e(setting('site_name')) ?><br>เวอร์ชั่น 1.0.0
    </div>
  </div>
</aside>

<div class="side-backdrop" data-side-backdrop></div>

<div class="shell">
  <header class="topbar">
    <button class="icon-btn" type="button" data-side-toggle aria-label="เปิด/ปิดเมนู"><?= icon('menu') ?></button>

    <span class="spacer"></span>

    <a class="btn btn--light btn--sm" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">
      <?= icon('external', '', 16) ?> ดูเว็บไซต์
    </a>

    <button class="icon-btn" type="button" data-theme-toggle aria-label="สลับโหมดกลางวัน/กลางคืน">
      <?= icon('sun', 'icon-sun') ?><?= icon('moon', 'icon-moon') ?>
    </button>

    <a class="icon-btn" href="<?= e(url('admin-messages.php')) ?>" aria-label="การแจ้งเตือน">
      <?= icon('bell') ?>
      <?php if ($newMessages > 0): ?><span class="icon-btn__dot"><?= $newMessages ?></span><?php endif; ?>
    </a>

    <div class="dropdown" data-dropdown>
      <button class="who" type="button" data-dropdown-toggle>
        <img class="who__avatar"
             src="<?= e($user['avatar'] ? upload_url($user['avatar']) : url('assets/img/avatar.svg')) ?>"
             alt="" width="36" height="36">
        <span>
          <span class="who__name"><?= e($user['name']) ?></span><br>
          <span class="who__role"><?= e(role_label($user['role'])) ?></span>
        </span>
        <?= icon('chevron-down', '', 16) ?>
      </button>
      <div class="dropdown__menu">
        <a class="dropdown__item" href="<?= e(url('admin-profile.php')) ?>"><?= icon('user') ?> โปรไฟล์ของฉัน</a>
        <a class="dropdown__item" href="<?= e(url('admin-settings.php')) ?>"><?= icon('settings') ?> ตั้งค่าเว็บไซต์</a>
        <div class="dropdown__sep"></div>
        <a class="dropdown__item dropdown__item--danger" href="<?= e(url('admin-logout.php')) ?>">
          <?= icon('logout') ?> ออกจากระบบ
        </a>
      </div>
    </div>
  </header>

  <div class="content">
    <?php foreach (take_flash() as $f): ?>
      <div class="alert alert--<?= e($f['type'] === 'success' ? 'success' : ($f['type'] === 'warn' ? 'warn' : 'error')) ?>">
        <?= icon($f['type'] === 'success' ? 'check-circle' : 'help', '', 20) ?>
        <span><?= e($f['message']) ?></span>
      </div>
    <?php endforeach; ?>

    <?php if (!empty($admin_crumbs)): ?>
      <nav class="breadcrumb">
        <?php foreach ($admin_crumbs as $i => $crumb): ?>
          <?php if ($i > 0): ?><span class="sep">›</span><?php endif; ?>
          <?php if (!empty($crumb[1])): ?>
            <a href="<?= e(url($crumb[1])) ?>"><?= e($crumb[0]) ?></a>
          <?php else: ?>
            <span><?= e($crumb[0]) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
