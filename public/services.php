<?php
/** บริการ — every job type the studio takes, with an anchor per service. */

require_once __DIR__ . '/../lib.php';

$active = 'services.php';
track_visit();

$services = db()->query(
    "SELECT * FROM services WHERE status = 'published' ORDER BY sort_order"
)->fetchAll();

$photos = db()->query(
    "SELECT p.* FROM photos p JOIN albums a ON a.id = p.album_id
      WHERE a.status = 'published' ORDER BY p.album_id, p.sort_order LIMIT 12"
)->fetchAll();

$steps = [
    ['ทักมาคุยก่อน', 'บอกวันที่ สถานที่ และประเภทงาน ทีมงานเช็กคิวและเสนอราคาให้ภายในวันเดียว'],
    ['ยืนยันคิว',     'วางมัดจำเพื่อล็อกคิว จากนั้นนัดคุยรายละเอียดหน้างานอีกครั้งก่อนวันจริง'],
    ['วันถ่ายจริง',   'ทีมงานถึงก่อนเวลา สำรวจแสงและมุม แล้วเก็บภาพตลอดงานโดยไม่รบกวนผู้ร่วมงาน'],
    ['ส่งอัลบั้ม',     'คัดและแต่งภาพเสร็จภายใน 5–7 วันทำการ ส่งเป็นลิงก์อัลบั้มที่ดาวน์โหลดไฟล์เต็มได้'],
];

$page_title = 'บริการ';
$page_desc  = 'ประเภทงานที่สายแบ้วสตูดิโอรับถ่าย ตั้งแต่งานเวทีโรงเรียน วันเกิด โปรไฟล์สตูดิโอ ไปจนถึงงานแฟชั่นเสื้อผ้าเด็ก';
include __DIR__ . '/../inc/header.php';
?>

<section class="page-head">
  <div class="wrap">
    <div class="crumbs"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a> &nbsp;›&nbsp; บริการ</div>
    <h1>ประเภทงานที่รับถ่าย</h1>
    <p>ทุกงานได้อัลบั้มออนไลน์ ไฟล์ขนาดเต็ม และสิทธิ์ใช้ภาพเต็มที่</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="grid grid-2">
      <?php foreach ($services as $i => $s): ?>
        <article class="card card--hover" id="<?= e($s['slug']) ?>" data-reveal
                 style="display:grid;grid-template-columns:150px 1fr;gap:18px;padding:0;overflow:hidden;">
          <div class="ratio ratio--1x1" style="border-radius:0;">
            <img src="<?= e($s['image'] ? upload_url($s['image']) : (isset($photos[$i]) ? photo_url($photos[$i], 'thumb') : url('assets/img/placeholder.svg'))) ?>"
                 alt="<?= e($s['title']) ?>" loading="lazy">
          </div>
          <div style="padding:20px 20px 20px 0;">
            <h3 class="card-title"><?= e($s['title']) ?></h3>
            <p class="card-text"><?= e($s['description']) ?></p>
            <a class="btn btn--ghost btn--sm mt-16" href="<?= e(url('contact.php?job=' . urlencode($s['title']))) ?>">
              สอบถามงานนี้
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="wrap">
    <div class="section-head">
      <h2>ขั้นตอนการทำงาน</h2>
      <p>ตั้งแต่ทักเข้ามาจนได้รับอัลบั้ม ใช้เวลารวมประมาณหนึ่งถึงสองสัปดาห์</p>
    </div>

    <div class="grid grid-4">
      <?php foreach ($steps as $i => $step): ?>
        <div class="card" data-reveal>
          <div style="width:38px;height:38px;border-radius:50%;background:var(--gold);color:#fff;
                      display:grid;place-items:center;font-weight:700;margin-bottom:12px;"><?= $i + 1 ?></div>
          <h3 class="card-title"><?= e($step[0]) ?></h3>
          <p class="card-text"><?= e($step[1]) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../inc/contact-band.php'; ?>
<?php include __DIR__ . '/../inc/footer.php'; ?>
