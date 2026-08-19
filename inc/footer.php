<?php
/** Public site footer, floating LINE button and the shared scripts. */

$footerMenu = db()->query(
    "SELECT * FROM menus WHERE location = 'footer_menu' AND status = 'active' ORDER BY sort_order"
)->fetchAll();

$footerServices = db()->query(
    "SELECT title, slug FROM services WHERE status = 'published' ORDER BY sort_order LIMIT 8"
)->fetchAll();

$socials = array_filter([
    'facebook'  => setting('contact_facebook_url'),
    'instagram' => setting('contact_instagram_url'),
    'youtube'   => setting('contact_youtube_url'),
    'tiktok'    => setting('contact_tiktok_url'),
    'line'      => setting('contact_line_url'),
]);
?>
</main>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">

      <div class="footer-about">
        <a class="brand" href="<?= e(url('index.php')) ?>">
          <span class="brand__mark"><?= icon('camera') ?></span>
          <span>
            <span class="brand__name"><?= e(setting('site_name')) ?></span><br>
            <span class="brand__sub"><?= e(setting('site_name_en')) ?></span>
          </span>
        </a>
        <p><?= e(excerpt(setting('site_description'), 170)) ?></p>
        <div class="socials">
          <?php foreach ($socials as $network => $link): ?>
            <a href="<?= e($link) ?>" target="_blank" rel="noopener"
               aria-label="<?= e($network) ?>"><?= social_icon($network) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="footer-col">
        <h4>เมนู</h4>
        <ul>
          <?php foreach ($footerMenu as $item): ?>
            <li><a href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>บริการของเรา</h4>
        <ul>
          <?php foreach ($footerServices as $s): ?>
            <li><a href="<?= e(url('services.php#' . $s['slug'])) ?>">· <?= e($s['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>ติดต่อเรา</h4>
        <ul>
          <li><span>LINE <?= e(setting('contact_line')) ?></span></li>
          <li><span>โทรศัพท์ <?= e(setting('contact_phone')) ?></span></li>
          <li><a href="mailto:<?= e(setting('contact_email')) ?>">อีเมล <?= e(setting('contact_email')) ?></a></li>
          <li><span>ที่อยู่ <?= e(setting('contact_address')) ?></span></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <span>© <?= date('Y') + 543 ?> <?= e(setting('site_name')) ?> <?= e(setting('site_name_en')) ?>. All rights reserved.</span>
      <nav>
        <a href="<?= e(url('page.php?slug=privacy')) ?>">นโยบายความเป็นส่วนตัว</a>
        <a href="<?= e(url('page.php?slug=terms')) ?>">ข้อกำหนดการใช้งาน</a>
      </nav>
    </div>
  </div>
</footer>

<?php include __DIR__ . '/chat-widget.php'; ?>

<?php if (setting('floating_line', '1') === '1'): ?>
<a class="float-line" href="<?= e(setting('contact_line_url', '#')) ?>" target="_blank" rel="noopener">
  <?= icon('line', '', 20) ?><span>LINE</span>
</a>
<?php endif; ?>

<div class="lightbox" data-lightbox hidden>
  <button class="lightbox__close" type="button" data-lb-close aria-label="ปิด"><?= icon('close') ?></button>
  <button class="lightbox__prev" type="button" data-lb-prev aria-label="ก่อนหน้า"><?= icon('chevron-left') ?></button>
  <img src="" alt="" data-lb-image>
  <button class="lightbox__next" type="button" data-lb-next aria-label="ถัดไป"><?= icon('chevron-right') ?></button>
  <div class="lightbox__bar">
    <span data-lb-caption></span>
    <span data-lb-count></span>
  </div>
</div>

<script src="<?= asset('assets/js/site.js') ?>" defer></script>
<?php if (setting('chat_enabled', '0') === '1' && trim(setting('chat_site_key', '')) !== ''): ?>
<script src="<?= asset('assets/js/chat.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>
