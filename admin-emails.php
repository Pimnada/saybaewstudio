<?php
/**
 * อีเมลที่ส่งออก — every letter the site produced, and a preview of each
 * template. In MAIL_LOG_ONLY mode this is where the studio reads what *would*
 * have been sent.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'clear') {
        $pdo->prepare('DELETE FROM email_log WHERE created_at < ?')
            ->execute([date('Y-m-d H:i:s', strtotime('-90 days'))]);
        flash('ล้างประวัติอีเมลที่เก่ากว่า 90 วันแล้ว');
        redirect('admin-emails.php');
    }
}

// Preview a template with believable sample values.
$previewName = (string) ($_GET['preview'] ?? '');
if ($previewName !== '' && isset(email_templates()[$previewName])) {
    $samples = [
        'name'        => 'คุณแม่เมย์',
        'phone'       => '081-234-5678',
        'email'       => 'customer@example.com',
        'job_type'    => 'งานเวทีการแสดง',
        'event_date'  => date('Y-m-d', strtotime('+18 days')),
        'detail'      => "อยากได้ช่างภาพงานแสดงของโรงเรียนอนุบาลค่ะ\nงานเริ่ม 09:00 จบประมาณเที่ยง มีเด็ก 120 คน",
        'message_id'  => 1,
        'album_title' => 'งานคอนเสิร์ต โรงเรียนดนตรี 2568',
        'album_url'   => url('album.php?slug=stage-kindergarten'),
        'photo_count' => 158,
        'video_count' => 2,
        'access_code' => 'A7F2C9',
        'note'        => 'รูปหมู่รายห้องอยู่ในโฟลเดอร์ "พิธีมอบรางวัล" นะคะ',
        'days_left'   => 14,
        'body'        => "สวัสดีค่ะ ทีมงานเช็กคิววันที่ 5 กันยายนให้แล้ว ยังว่างอยู่ค่ะ\nรบกวนยืนยันกลับมาได้เลยนะคะ",
        'original'    => 'อยากได้ช่างภาพงานแสดงของโรงเรียนอนุบาลค่ะ',
        'staff_name'  => $user['name'] ?? 'ทีมงานสายแบ้วสตูดิโอ',
        'role_label'  => 'ผู้จัดการเนื้อหา',
        'temp_password' => 'Sbs-8241xk',
        'login_url'   => url('admin-login.php'),
    ];
    try {
        $mail = render_email($previewName, $samples);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('เรนเดอร์เทมเพลตไม่สำเร็จ: ' . e($e->getMessage()));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $mail['html'];
    exit;
}

// Read one stored letter.
$viewId = (int) ($_GET['view'] ?? 0);
if ($viewId > 0) {
    $st = $pdo->prepare('SELECT * FROM email_log WHERE id = ?');
    $st->execute([$viewId]);
    $row = $st->fetch();
    if ($row) {
        header('Content-Type: text/html; charset=utf-8');
        echo $row['body'];
        exit;
    }
}

$logs = $pdo->query('SELECT * FROM email_log ORDER BY id DESC LIMIT 300')->fetchAll();

$statusBadge = ['sent' => 'ok', 'logged' => 'warn', 'failed' => 'danger', 'queued' => 'muted'];
$statusLabel = ['sent' => 'ส่งแล้ว', 'logged' => 'บันทึกไว้ (โหมดทดสอบ)', 'failed' => 'ไม่สำเร็จ', 'queued' => 'รอส่ง'];

$admin_title  = 'อีเมลที่ส่งออก';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['อีเมลที่ส่งออก', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">อีเมลที่ส่งออก</h1>
    <p class="text-sm text-muted mb-0">ประวัติจดหมายทุกฉบับที่ระบบสร้างขึ้น พร้อมตัวอย่างเทมเพลต</p>
  </div>
  <form method="post" data-confirm-submit="ล้างประวัติอีเมลที่เก่ากว่า 90 วัน?">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="clear">
    <button class="btn btn--light" type="submit"><?= icon('trash', '', 16) ?> ล้างประวัติเก่า</button>
  </form>
</div>

<?php if (MAIL_LOG_ONLY): ?>
  <div class="alert alert--warn">
    <?= icon('help', '', 20) ?>
    <span>
      ระบบอยู่ในโหมดทดสอบ (<code>MAIL_LOG_ONLY</code>) — จดหมายทุกฉบับถูกสร้างและบันทึกไว้ที่นี่
      แต่ยังไม่ถูกส่งออกจริง เปลี่ยนได้ใน <code>config.php</code> บนเซิร์ฟเวอร์
    </span>
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">เทมเพลตอีเมลทั้งหมด</h2></div>
  <div class="panel__body">
    <div class="grid grid-3">
      <?php foreach (email_templates() as $key => $label): ?>
        <div class="card">
          <div class="row mb-8" style="gap:10px;">
            <span class="why-card__icon" style="width:38px;height:38px;margin:0;"><?= icon('mail', '', 20) ?></span>
            <div style="min-width:0;">
              <div class="fw-700 text-sm"><?= e($label) ?></div>
              <div class="text-xs text-faint"><?= e($key) ?></div>
            </div>
          </div>
          <a class="btn btn--light btn--sm btn--block" target="_blank" rel="noopener"
             href="<?= e(url('admin-emails.php?preview=' . urlencode($key))) ?>">
            <?= icon('eye', '', 15) ?> ดูตัวอย่าง
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2 class="panel__title">ประวัติการส่ง</h2>
    <span class="spacer"></span>
    <div class="toolbar__search" style="margin:0;">
      <?= icon('search') ?>
      <input type="search" placeholder="ค้นหาอีเมลหรือหัวข้อ..." data-filter-table="#em-table">
    </div>
  </div>

  <?php if (!$logs): ?>
    <div class="panel__body">
      <div class="empty-state" style="padding:40px 10px;"><?= icon('mail', '', 52) ?><p>ยังไม่มีอีเมลที่ส่งออก</p></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl" id="em-table">
        <thead>
          <tr>
            <th style="width:160px;">เวลา</th>
            <th style="width:210px;">ผู้รับ</th>
            <th>หัวข้อ</th>
            <th style="width:180px;">สถานะ</th>
            <th style="width:60px;"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td class="text-sm text-muted"><?= e(thai_datetime($l['created_at'])) ?></td>
            <td class="text-sm"><?= e($l['to_email']) ?></td>
            <td>
              <div class="fw-700 text-sm"><?= e($l['subject']) ?></div>
              <div class="text-xs text-faint"><?= e($l['template']) ?><?= $l['error'] ? ' · ' . e($l['error']) : '' ?></div>
            </td>
            <td>
              <span class="badge badge--<?= e($statusBadge[$l['status']] ?? 'muted') ?>">
                <?= e($statusLabel[$l['status']] ?? $l['status']) ?>
              </span>
            </td>
            <td>
              <a class="icon-btn" href="<?= e(url('admin-emails.php?view=' . $l['id'])) ?>"
                 target="_blank" title="เปิดอ่านจดหมายฉบับนี้"><?= icon('eye') ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
