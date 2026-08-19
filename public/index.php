<?php
/** Homepage. */

require_once __DIR__ . '/../lib.php';

$active = 'index.php';
track_visit();

$pdo = db();

$services = $pdo->query(
    "SELECT * FROM services WHERE status = 'published' ORDER BY sort_order LIMIT 8"
)->fetchAll();

$categories = $pdo->query(
    "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order"
)->fetchAll();

$albums = $pdo->query(
    "SELECT a.*, c.name AS category_name, c.slug AS category_slug
       FROM albums a
       LEFT JOIN categories c ON c.id = a.category_id
      WHERE a.status = 'published' AND a.type = 'photo'
      ORDER BY a.is_featured DESC, a.sort_order, a.event_date DESC
      LIMIT 6"
)->fetchAll();

$reviews = $pdo->query(
    "SELECT * FROM reviews WHERE status = 'published' ORDER BY sort_order LIMIT 6"
)->fetchAll();

// Four on the homepage, as in the mockup — two rows of two. The rest live on
// contact.php; the homepage is not the place to answer every question.
$faqs = $pdo->query(
    "SELECT * FROM faqs WHERE status = 'published' ORDER BY sort_order LIMIT 4"
)->fetchAll();

/**
 * Photographs for the hero mosaic. One per album first, so the seven frames
 * show seven different events rather than seven shots of the same stage; if
 * there are not enough albums, the rest are topped up from whatever exists.
 */
$mosaic = $pdo->query(
    "SELECT p.* FROM photos p
       JOIN albums a ON a.id = p.album_id
      WHERE a.status = 'published'
        AND p.id = (SELECT MIN(p2.id) FROM photos p2 WHERE p2.album_id = a.id)
      ORDER BY a.is_featured DESC, a.sort_order
      LIMIT 7"
)->fetchAll();

if (count($mosaic) < 7) {
    $seen = array_column($mosaic, 'id');
    $more = $pdo->query(
        "SELECT p.* FROM photos p JOIN albums a ON a.id = p.album_id
          WHERE a.status = 'published' ORDER BY p.id LIMIT 20"
    )->fetchAll();
    foreach ($more as $p) {
        if (count($mosaic) >= 7) {
            break;
        }
        if (!in_array($p['id'], $seen, true)) {
            $mosaic[] = $p;
        }
    }
}

$page_title = '';
$page_desc  = setting('site_description');
include __DIR__ . '/../inc/header.php';
?>

<!-- ================================================================ hero -->
<section class="hero">
  <div class="wrap hero__inner">

    <div class="hero__copy">
      <h1><?= e(setting('hero_title')) ?></h1>
      <p class="hero__text"><?= e(setting('hero_text')) ?></p>
      <div class="hero__cta">
        <a class="btn btn--primary btn--lg" href="<?= e(url(setting('hero_cta1_url', 'albums.php'))) ?>">
          <?= e(setting('hero_cta1_label', 'ดูผลงานทั้งหมด')) ?>
        </a>
        <a class="btn btn--light btn--lg" href="<?= e(url(setting('hero_cta2_url', 'contact.php'))) ?>">
          <?= e(setting('hero_cta2_label', 'สอบถามคิวงาน')) ?>
        </a>
      </div>
    </div>

    <div class="mosaic">
      <?php for ($i = 0; $i < 7; $i++): ?>
        <div class="mosaic__cell">
          <?php if (isset($mosaic[$i])): ?>
            <img src="<?= e(photo_url($mosaic[$i], 'thumb')) ?>" alt="ตัวอย่างผลงานสายแบ้วสตูดิโอ"
                 loading="<?= $i < 3 ? 'eager' : 'lazy' ?>" width="480" height="320">
          <?php else: ?>
            <img src="<?= asset('assets/img/placeholder.svg') ?>" alt="" width="480" height="320">
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>

  </div>

  <div class="hero-strip">
    <div class="wrap hero-strip__grid">
      <div class="hero-chip"><span class="hero-chip__icon"><?= icon('folder-organised') ?></span><span>จัดอัลบั้มเป็นระบบ</span></div>
      <div class="hero-chip"><span class="hero-chip__icon"><?= icon('video-frames') ?></span><span>แทรกรูปและวิดีโอ</span></div>
      <div class="hero-chip"><span class="hero-chip__icon"><?= icon('download-cloud') ?></span><span>ดาวน์โหลดไฟล์จริง</span></div>
      <div class="hero-chip"><span class="hero-chip__icon"><?= icon('mobile') ?></span><span>รองรับมือถือ</span></div>
    </div>
  </div>
</section>

<!-- =========================================================== why us -->
<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2>ทำไมหลายครอบครัวเลือกเรา</h2>
    </div>

    <div class="grid grid-4">
      <article class="card card--hover why-card" data-reveal>
        <div class="why-card__icon"><?= icon('image-sharp', '', 28) ?></div>
        <h3>ภาพคมชัด ดูดี</h3>
        <p>อุปกรณ์คุณภาพสูง ทีมงานมีประสบการณ์ จัดแสงสวย สีสันเป็นธรรมชาติ</p>
      </article>

      <article class="card card--hover why-card" data-reveal>
        <div class="why-card__icon"><?= icon('moment-star', '', 28) ?></div>
        <h3>เก็บครบทุกช่วงสำคัญ</h3>
        <p>ไม่พลาดทุกช็อตสำคัญ ทั้งภาพความทรงจำของลูกน้อยและครอบครัว</p>
      </article>

      <article class="card card--hover why-card" data-reveal>
        <div class="why-card__icon"><?= icon('share-link', '', 28) ?></div>
        <h3>แชร์ลิงก์ได้ทันที</h3>
        <p>ส่งลิงก์ให้ญาติ พร้อมให้ผู้ปกครองหรือครูงานดูได้ทันที</p>
      </article>

      <article class="card card--hover why-card" data-reveal>
        <div class="why-card__icon"><?= icon('chat-fast', '', 28) ?></div>
        <h3>ตอบเร็วผ่าน LINE / Facebook</h3>
        <p>ติดต่อสะดวก ตอบไว พร้อมให้คำแนะนำทุกขั้นตอน</p>
      </article>
    </div>
  </div>
</section>

<!-- ========================================================== services -->
<section class="section section--cream">
  <div class="wrap">
    <div class="section-head">
      <h2>ประเภทงานที่รับถ่าย</h2>
    </div>

    <div class="tiles">
      <?php foreach ($services as $i => $s): ?>
        <a class="tile" href="<?= e(url('services.php#' . $s['slug'])) ?>" data-reveal>
          <img src="<?= e($s['image'] ? upload_url($s['image']) : (isset($mosaic[$i]) ? photo_url($mosaic[$i], 'thumb') : url('assets/img/placeholder.svg'))) ?>"
               alt="<?= e($s['title']) ?>" loading="lazy" width="400" height="300">
          <span class="tile__label"><?= e($s['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================ albums -->
<section class="section section--dark">
  <div class="wrap">
    <div class="section-head">
      <h2>ตัวอย่างอัลบั้มยอดนิยม</h2>
    </div>

    <div class="album-tabs" data-album-tabs>
      <button class="album-tab is-active" type="button" data-cat="all">ทั้งหมด</button>
      <?php foreach ($categories as $c): ?>
        <button class="album-tab" type="button" data-cat="<?= e($c['slug']) ?>"><?= e($c['name']) ?></button>
      <?php endforeach; ?>
    </div>

    <?php if (!$albums): ?>
      <div class="empty-state"><?= icon('images', '', 56) ?><p>ยังไม่มีอัลบั้มที่เผยแพร่</p></div>
    <?php else: ?>
      <div class="album-grid">
        <?php foreach ($albums as $a): ?>
          <a class="album-card" href="<?= e(url('album.php?slug=' . urlencode($a['slug']))) ?>"
             data-album-cat="<?= e($a['category_slug'] ?? '') ?>" data-reveal>
            <div class="album-card__media">
              <img src="<?= e(album_cover($a)) ?>" alt="<?= e($a['title']) ?>" loading="lazy" width="420" height="262">
              <?php if ($a['category_name']): ?>
                <span class="album-card__badge"><?= e($a['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="album-card__body">
              <h3 class="album-card__title"><?= e($a['title']) ?></h3>
              <div class="album-card__date"><?= e(thai_date($a['event_date'])) ?></div>
              <div class="album-card__meta">
                <span><?= icon('image', '', 14) ?><?= fmt_num(album_photo_count((int) $a['id'])) ?> รูป</span>
                <span><?= icon('video', '', 14) ?><?= fmt_num(album_video_count((int) $a['id'])) ?> วิดีโอ</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="text-center mt-32">
      <a class="btn btn--primary" href="<?= e(url('albums.php')) ?>">ดูผลงานทั้งหมด</a>
    </div>
  </div>
</section>

<!-- =========================================================== reviews -->
<section class="section section--dark">
  <div class="wrap">
    <div class="section-head"><h2>เสียงจากลูกค้า</h2></div>

    <div class="reviews-rail" data-review-rail>
      <?php foreach ($reviews as $r): ?>
        <article class="review-card">
          <div class="review-card__head">
            <img class="review-card__avatar"
                 src="<?= e($r['avatar'] ? upload_url($r['avatar']) : url('assets/img/avatar.svg')) ?>"
                 alt="<?= e($r['name']) ?>" loading="lazy" width="44" height="44">
            <div>
              <div class="review-card__name"><?= e($r['name']) ?></div>
              <div class="review-card__role"><?= e($r['role']) ?></div>
            </div>
            <span class="spacer"></span>
            <span><?= star_row((int) $r['rating']) ?></span>
          </div>
          <p class="review-card__body"><?= e($r['body']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="dots" data-review-dots></div>
  </div>
</section>

<!-- =============================================================== faq -->
<section class="section" id="faq">
  <div class="wrap">
    <div class="section-head"><h2>คำถามที่ลูกค้าถามบ่อย</h2></div>

    <div class="faq-grid">
      <?php foreach ($faqs as $f): ?>
        <div class="faq-item">
          <button class="faq-q" type="button" aria-expanded="false">
            <span><?= e($f['question']) ?></span>
            <span class="faq-q__plus" aria-hidden="true"></span>
          </button>
          <div class="faq-a"><div><p><?= nl2br(e($f['answer'])) ?></p></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========================================================== contact -->
<?php include __DIR__ . '/../inc/contact-band.php'; ?>

<?php include __DIR__ . '/../inc/footer.php'; ?>
