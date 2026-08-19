<?php
/**
 * Public site head + masthead.
 *
 * A page sets these before including it:
 *   $page_title, $page_desc, $og_image, $active (basename of the nav item)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/icons.php';

/**
 * The session has to be open before a single byte of HTML leaves, or
 * session_start() lands after the headers and silently fails — which takes the
 * CSRF token and every flash message down with it. This is the last safe point.
 */
boot_session();

/**
 * Never let a browser reuse a cached copy of one of these pages.
 *
 * Every page carries a CSRF token and the inline script that decides the
 * theme, so a stale copy hands out a token the session no longer accepts and
 * keeps running whatever theme logic shipped that day — which is exactly how a
 * theme fix can look like it did not work at all on the one machine that has
 * the old page cached. The markup is generated per request anyway; there is
 * nothing here worth a browser holding on to.
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$siteName  = setting('site_name', 'สายแบ้วสตูดิโอ');
$pageTitle = isset($page_title) && $page_title !== ''
    ? $page_title . ' — ' . $siteName
    : $siteName . ' — ' . setting('site_tagline');
$pageDesc  = $page_desc ?? setting('site_description');
$active    = $active ?? current_path();
$lineUrl   = setting('contact_line_url', '#');

$headerMenu = db()->query(
    "SELECT * FROM menus WHERE location = 'header' AND status = 'active' ORDER BY sort_order"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="keywords" content="<?= e(setting('meta_keywords')) ?>">
<meta name="theme-color" content="#17140F">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:url" content="<?= e(url(current_path())) ?>">
<?php if (!empty($og_image)): ?>
<meta property="og:image" content="<?= e($og_image) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">

<link rel="canonical" href="<?= e(url(current_path())) ?>">
<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= asset('assets/img/apple-touch-icon.png') ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">

<script>
  // Apply the saved theme before first paint so the page never flashes.
  //
  // Light is the default, and prefers-color-scheme is deliberately NOT consulted.
  // This is a light design — the photographs are presented on cream, the way the
  // studio's work is meant to be seen. Following the operating system meant every
  // visitor on a dark-mode phone got the night palette and never saw it, which is
  // not a preference we should infer on their behalf. Dark is one click away and
  // is remembered once chosen.
  //
  // ?theme=light|dark forces one for this page view only, without touching the
  // visitor's saved choice.
  (function () {
    try {
      var forced = new URLSearchParams(location.search).get('theme');
      var t = (forced === 'light' || forced === 'dark')
        ? forced
        : (localStorage.getItem('sbs-theme') || 'light');
      document.documentElement.setAttribute('data-theme', t);
    } catch (e) {}
  })();
</script>
<?php if (setting('ga_id') !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(setting('ga_id')) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date()); gtag('config', '<?= e(setting('ga_id')) ?>');
</script>
<?php endif; ?>
</head>
<body>

<a href="#main" class="sr-only">ข้ามไปยังเนื้อหาหลัก</a>

<header class="site-header">
  <div class="wrap site-header__inner">

    <a class="brand" href="<?= e(url('index.php')) ?>">
      <span class="brand__mark"><?= icon('camera') ?></span>
      <span>
        <span class="brand__name"><?= e($siteName) ?></span><br>
        <span class="brand__sub"><?= e(setting('site_name_en', 'saybaewstudio')) ?></span>
      </span>
    </a>

    <nav class="nav">
      <?php foreach ($headerMenu as $item): ?>
        <a href="<?= e(url($item['url'])) ?>"
           class="<?= basename($item['url']) === $active ? 'is-active' : '' ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <button class="theme-toggle" type="button" data-theme-toggle aria-label="สลับโหมดกลางวัน/กลางคืน">
        <?= icon('sun', 'icon-sun') ?><?= icon('moon', 'icon-moon') ?>
      </button>
      <a class="btn btn--ghost btn--sm" href="<?= e(url('albums.php')) ?>">ดูอัลบั้ม</a>
      <?php /* Gold, not LINE green — the mockup keeps the masthead in the brand
               palette and saves the green for the floating button in the corner,
               so the header reads as one gold pair rather than a traffic light. */ ?>
      <a class="btn btn--primary btn--sm" href="<?= e($lineUrl) ?>" target="_blank" rel="noopener">
        <?= icon('line', '', 18) ?> คุยทาง LINE
      </a>
      <button class="burger" type="button" data-burger aria-label="เปิดเมนู" aria-expanded="false">
        <?= icon('menu') ?>
      </button>
    </div>

  </div>

  <div class="mobile-nav" data-mobile-nav>
    <?php foreach ($headerMenu as $item): ?>
      <a href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a>
    <?php endforeach; ?>
    <a class="btn btn--line btn--block" href="<?= e($lineUrl) ?>" target="_blank" rel="noopener">
      <?= icon('line', '', 18) ?> คุยทาง LINE
    </a>
  </div>
</header>

<main id="main">
