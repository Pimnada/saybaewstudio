<?php
/** ข้อความจากลูกค้า — the enquiry inbox, with a reply-by-email box. */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../mailer.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', ['new', 'read', 'replied', 'closed'], true)
            ? $_POST['status'] : 'read';
        $pdo->prepare('UPDATE messages SET status = ?, read_at = COALESCE(read_at, ?) WHERE id = ?')
            ->execute([$status, date('Y-m-d H:i:s'), $id]);
        flash('อัปเดตสถานะแล้ว');
        redirect('admin-messages.php?id=' . $id);
    }

    if ($action === 'note') {
        $pdo->prepare('UPDATE messages SET admin_note = ? WHERE id = ?')
            ->execute([trim((string) ($_POST['admin_note'] ?? '')), $id]);
        flash('บันทึกโน้ตภายในแล้ว');
        redirect('admin-messages.php?id=' . $id);
    }

    if ($action === 'reply') {
        $st = $pdo->prepare('SELECT * FROM messages WHERE id = ?');
        $st->execute([$id]);
        $msg = $st->fetch();

        $body = trim((string) ($_POST['body'] ?? ''));
        if (!$msg || !$msg['email']) {
            flash('ลูกค้ารายนี้ไม่ได้ทิ้งอีเมลไว้ — ตอบกลับทางโทรศัพท์หรือ LINE แทนได้ค่ะ', 'error');
            redirect('admin-messages.php?id=' . $id);
        }
        if ($body === '') {
            flash('กรุณาพิมพ์ข้อความก่อนส่ง', 'error');
            redirect('admin-messages.php?id=' . $id);
        }

        $ok = send_email($msg['email'], 'reply', [
            'name'             => $msg['name'],
            'body'             => $body,
            'original'         => $msg['detail'],
            'staff_name'       => $user['name'] ?? 'ทีมงานสายแบ้วสตูดิโอ',
            'subject_override' => trim((string) ($_POST['subject'] ?? '')) ?: null,
        ]);

        if ($ok) {
            $pdo->prepare('UPDATE messages SET status = ? WHERE id = ?')->execute(['replied', $id]);
        }
        log_activity('message.reply', $msg['email']);
        flash($ok
            ? (MAIL_LOG_ONLY
                ? 'บันทึกอีเมลตอบกลับไว้แล้ว (โหมดทดสอบ ยังไม่ส่งออกจริง)'
                : 'ส่งอีเมลตอบกลับไปที่ ' . $msg['email'] . ' แล้ว')
            : 'ส่งอีเมลไม่สำเร็จ ตรวจสอบการตั้งค่าในหน้าตั้งค่าระบบ',
            $ok ? 'success' : 'error');
        redirect('admin-messages.php?id=' . $id);
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        flash('ลบข้อความแล้ว');
        redirect('admin-messages.php');
    }
}

$filter = (string) ($_GET['status'] ?? '');
$openId = (int) ($_GET['id'] ?? 0);

$where  = $filter !== '' ? 'WHERE status = ?' : '';
$params = $filter !== '' ? [$filter] : [];
$st = $pdo->prepare("SELECT * FROM messages $where ORDER BY id DESC LIMIT 200");
$st->execute($params);
$messages = $st->fetchAll();

$open = null;
if ($openId > 0) {
    $st = $pdo->prepare('SELECT * FROM messages WHERE id = ?');
    $st->execute([$openId]);
    $open = $st->fetch() ?: null;

    // Opening an enquiry marks it read.
    if ($open && $open['status'] === 'new') {
        $pdo->prepare('UPDATE messages SET status = ?, read_at = ? WHERE id = ?')
            ->execute(['read', date('Y-m-d H:i:s'), $openId]);
        $open['status'] = 'read';
    }
}

$counts = ['new' => 0, 'read' => 0, 'replied' => 0, 'closed' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM messages GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['n'];
}

$statusLabels = ['new' => 'ใหม่', 'read' => 'อ่านแล้ว', 'replied' => 'ตอบแล้ว', 'closed' => 'ปิดงาน'];
$statusBadge  = ['new' => 'danger', 'read' => 'warn', 'replied' => 'ok', 'closed' => 'muted'];

$admin_title  = 'ข้อความจากลูกค้า';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ข้อความจากลูกค้า', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ข้อความจากลูกค้า</h1>
    <p class="text-sm text-muted mb-0">คำขอที่ส่งเข้ามาจากแบบฟอร์มติดต่อบนเว็บไซต์</p>
  </div>
</div>

<div class="filter-bar" style="justify-content:flex-start;margin-bottom:16px;">
  <a class="filter-pill <?= $filter === '' ? 'is-active' : '' ?>" href="<?= e(url('admin-messages.php')) ?>">
    ทั้งหมด (<?= fmt_num(array_sum($counts)) ?>)
  </a>
  <?php foreach ($statusLabels as $key => $label): ?>
    <a class="filter-pill <?= $filter === $key ? 'is-active' : '' ?>"
       href="<?= e(url('admin-messages.php?status=' . $key)) ?>">
      <?= e($label) ?> (<?= fmt_num($counts[$key]) ?>)
    </a>
  <?php endforeach; ?>
</div>

<div class="grid" style="grid-template-columns: minmax(0,1fr) minmax(0,1.15fr); gap:20px; align-items:start;">

  <div class="panel" style="margin:0;">
    <div class="panel__head">
      <h2 class="panel__title">กล่องข้อความ</h2>
      <span class="spacer"></span>
      <span class="text-xs text-faint"><?= fmt_num(count($messages)) ?> รายการ</span>
    </div>
    <?php if (!$messages): ?>
      <div class="panel__body">
        <div class="empty-state" style="padding:36px 10px;"><?= icon('inbox', '', 52) ?><p>ยังไม่มีข้อความ</p></div>
      </div>
    <?php else: ?>
      <div class="table-wrap" style="max-height:640px;overflow-y:auto;">
        <table class="tbl">
          <tbody>
          <?php foreach ($messages as $m): ?>
            <tr style="<?= $openId === (int) $m['id'] ? 'background:var(--gold-100);' : '' ?>">
              <td>
                <a class="fw-700" href="<?= e(url('admin-messages.php?id=' . $m['id'])) ?>"><?= e($m['name']) ?></a>
                <div class="text-xs text-faint"><?= e(excerpt($m['detail'] ?: $m['job_type'], 60)) ?></div>
                <div class="text-xs text-faint mt-8">
                  <span class="badge badge--<?= e($statusBadge[$m['status']] ?? 'muted') ?>">
                    <?= e($statusLabels[$m['status']] ?? $m['status']) ?>
                  </span>
                  <?= e(time_ago($m['created_at'])) ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if (!$open): ?>
      <div class="panel" style="margin:0;">
        <div class="panel__body">
          <div class="empty-state" style="padding:50px 10px;">
            <?= icon('message', '', 52) ?>
            <p>เลือกข้อความทางซ้ายเพื่อดูรายละเอียดและตอบกลับ</p>
          </div>
        </div>
      </div>
    <?php else: ?>

      <div class="panel">
        <div class="panel__head">
          <h2 class="panel__title"><?= e($open['name']) ?></h2>
          <span class="badge badge--<?= e($statusBadge[$open['status']] ?? 'muted') ?>">
            <?= e($statusLabels[$open['status']] ?? $open['status']) ?>
          </span>
          <span class="spacer"></span>
          <form method="post" data-confirm-submit="ลบข้อความนี้ถาวร?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
            <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
          </form>
        </div>

        <div class="panel__body">
          <table class="tbl" style="border:1px solid var(--line);border-radius:var(--r-sm);overflow:hidden;">
            <tbody>
              <tr><th style="width:130px;">เบอร์โทร</th>
                  <td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $open['phone'])) ?>"><?= e($open['phone'] ?: '—') ?></a></td></tr>
              <tr><th>อีเมล</th>
                  <td><?= $open['email'] ? '<a href="mailto:' . e($open['email']) . '">' . e($open['email']) . '</a>' : '—' ?></td></tr>
              <tr><th>ประเภทงาน</th><td><?= e($open['job_type'] ?: '—') ?></td></tr>
              <tr><th>วันที่จัดงาน</th><td><?= e($open['event_date'] ? thai_date($open['event_date']) : '—') ?></td></tr>
              <tr><th>ส่งเมื่อ</th><td><?= e(thai_datetime($open['created_at'])) ?></td></tr>
            </tbody>
          </table>

          <div class="mt-16">
            <div class="label">รายละเอียดจากลูกค้า</div>
            <div class="card" style="background:var(--bg-2);">
              <?= nl2br(e($open['detail'] ?: '(ไม่ได้กรอกรายละเอียด)')) ?>
            </div>
          </div>

          <form method="post" class="mt-16">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="note">
            <input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
            <div class="field">
              <label for="ms-note">โน้ตภายใน (ลูกค้าไม่เห็น)</label>
              <textarea id="ms-note" name="admin_note" rows="2"><?= e($open['admin_note']) ?></textarea>
            </div>
            <button class="btn btn--light btn--sm" type="submit">บันทึกโน้ต</button>
          </form>
        </div>

        <div class="panel__foot">
          <form method="post" class="row" style="gap:8px;width:100%;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
            <span class="text-sm text-muted">เปลี่ยนสถานะ</span>
            <select name="status" style="width:auto;min-width:140px;">
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $open['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn--light btn--sm" type="submit">บันทึก</button>
          </form>
        </div>
      </div>

      <form class="panel" method="post" style="margin:0;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="id" value="<?= (int) $open['id'] ?>">

        <div class="panel__head"><h2 class="panel__title">ตอบกลับทางอีเมล</h2></div>

        <div class="panel__body">
          <?php if (!$open['email']): ?>
            <div class="alert alert--warn mb-0">
              <?= icon('help', '', 20) ?>
              <span>ลูกค้ารายนี้ไม่ได้ทิ้งอีเมลไว้ — ติดต่อกลับทางโทรศัพท์หรือ LINE แทนค่ะ</span>
            </div>
          <?php else: ?>
            <div class="field">
              <label for="rp-subject">หัวข้ออีเมล</label>
              <input id="rp-subject" type="text" name="subject"
                     placeholder="ตอบกลับจากสายแบ้วสตูดิโอ">
            </div>
            <div class="field mb-0">
              <label for="rp-body">ข้อความ <span class="req">*</span></label>
              <textarea id="rp-body" name="body" rows="7" required
                        placeholder="สวัสดีค่ะ ทีมงานเช็กคิววันที่ ... ให้แล้ว ยังว่างอยู่ค่ะ ..."></textarea>
              <p class="hint">ข้อความจะถูกจัดรูปแบบด้วยเทมเพลตอีเมลของสตูดิโอโดยอัตโนมัติ</p>
            </div>
            <?php if (MAIL_LOG_ONLY): ?>
              <div class="alert alert--warn mt-16 mb-0">
                <?= icon('help', '', 20) ?>
                <span>ตอนนี้อยู่ในโหมดทดสอบ อีเมลจะถูกบันทึกไว้แต่ยังไม่ส่งออกจริง
                      เปิดการส่งจริงได้ที่หน้าตั้งค่าระบบ</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <?php if ($open['email']): ?>
          <div class="panel__foot">
            <button class="btn btn--primary" type="submit"><?= icon('mail', '', 16) ?> ส่งอีเมลตอบกลับ</button>
            <span class="text-xs text-faint">ส่งถึง <?= e($open['email']) ?></span>
          </div>
        <?php endif; ?>
      </form>

    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
