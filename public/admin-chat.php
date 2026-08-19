<?php
/**
 * ระบบแชท — the switch and the connection details for the tobwai chat widget.
 *
 * This site does not run a chatbot of its own; it is a tobwai customer. Everything
 * on this page is about pointing the widget at the studio's tobwai account and
 * being honest on screen about whether that connection actually works.
 */

require_once __DIR__ . '/../auth.php';

$pdo = db();

$keys = [
    'chat_endpoint', 'chat_site_key', 'chat_welcome',
    'chat_bubble_label', 'chat_status_text',
];

if (is_post()) {
    csrf_check();

    if (($_POST['action'] ?? '') === 'save') {
        foreach ($keys as $k) {
            if (array_key_exists($k, $_POST)) {
                set_setting($k, trim((string) $_POST[$k]));
            }
        }
        set_setting('chat_enabled', isset($_POST['chat_enabled']) ? '1' : '0');
        log_activity('settings.chat', isset($_POST['chat_enabled']) ? 'เปิดระบบแชท' : 'ปิดระบบแชท');
        flash('บันทึกการตั้งค่าระบบแชทแล้ว');
        redirect('admin-chat.php');
    }

    if (($_POST['action'] ?? '') === 'test') {
        $endpoint = trim(setting('chat_endpoint', ''));
        $key      = trim(setting('chat_site_key', ''));

        if ($endpoint === '' || $key === '') {
            flash('ต้องกรอกทั้งที่อยู่ API และรหัสร้านก่อนจึงจะทดสอบได้', 'error');
            redirect('admin-chat.php');
        }

        // A real round trip, with the same shape the browser widget sends.
        $payload = json_encode([
            'action' => 'send',
            'vid'    => str_repeat('0', 31) . '1',   // valid shape, obviously a test
            'text'   => 'ทดสอบการเชื่อมต่อจากหลังบ้าน saybaewstudio',
            'key'    => $key,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        set_setting('chat_test_at', date('Y-m-d H:i:s'));

        if ($err !== '') {
            set_setting('chat_test_result', 'ต่อไม่ติด: ' . $err);
            flash('เชื่อมต่อไม่ได้: ' . $err, 'error');
        } else {
            $json = json_decode((string) $body, true);
            if (is_array($json) && ($json['ok'] ?? false)) {
                set_setting('chat_test_result', 'สำเร็จ (HTTP ' . $status . ')');
                flash('เชื่อมต่อสำเร็จ — ระบบตอบกลับมาแล้ว');
            } else {
                $why = is_array($json) ? (string) ($json['error'] ?? 'ไม่ทราบสาเหตุ') : 'ตอบกลับไม่ใช่ JSON';
                set_setting('chat_test_result', 'ไม่สำเร็จ (HTTP ' . $status . '): ' . $why);
                flash('ปลายทางตอบกลับว่า: ' . $why . ' (HTTP ' . $status . ')', 'error');
            }
        }
        redirect('admin-chat.php');
    }
}

$enabled  = setting('chat_enabled', '0') === '1';
$key      = trim(setting('chat_site_key', ''));
$endpoint = trim(setting('chat_endpoint', 'https://tobwai.com/chat-api.php'));
$ready    = $enabled && $key !== '' && $endpoint !== '';

$admin_title  = 'ระบบแชท';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['ระบบแชท', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">ระบบแชท</h1>
    <p class="text-sm text-muted mb-0">กล่องแชทหน้าเว็บ ทำงานด้วยระบบตอบไวของ tobwai.com</p>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="test">
    <button class="btn btn--light" type="submit"><?= icon('refresh', '', 16) ?> ทดสอบการเชื่อมต่อ</button>
  </form>
</div>

<?php if ($ready): ?>
  <div class="alert alert--success">
    <?= icon('check-circle', '', 20) ?>
    <span>ระบบแชท<strong>เปิดอยู่</strong> — กล่องแชทแสดงอยู่บนหน้าเว็บทุกหน้า</span>
  </div>
<?php elseif ($enabled): ?>
  <div class="alert alert--warn">
    <?= icon('help', '', 20) ?>
    <span>สวิตช์เปิดอยู่ แต่ยังไม่ได้กรอกรหัสร้าน — กล่องแชท<strong>ยังไม่แสดง</strong>บนหน้าเว็บ
      เพราะยอมให้ไม่มีกล่องเลย ดีกว่ามีกล่องที่กดแล้วขึ้น error ใส่หน้าลูกค้า</span>
  </div>
<?php else: ?>
  <div class="alert alert--info">
    <?= icon('message', '', 20) ?>
    <span>ระบบแชทปิดอยู่ ลูกค้ายังติดต่อได้ทางปุ่ม LINE และแบบฟอร์มหน้าติดต่อตามปกติ</span>
  </div>
<?php endif; ?>

<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">

  <div class="grid grid-2" style="align-items:start;">

    <div class="panel">
      <div class="panel__head"><h2 class="panel__title">การเชื่อมต่อกับ tobwai</h2></div>
      <div class="panel__body">
        <label class="check mb-16">
          <input type="checkbox" name="chat_enabled" <?= $enabled ? 'checked' : '' ?>>
          <span><strong>เปิดกล่องแชทบนหน้าเว็บ</strong><br>
            <span class="text-xs text-muted">ปิดเมื่อไหร่ก็ได้ กล่องจะหายไปทันทีโดยไม่ต้องแก้โค้ด</span></span>
        </label>

        <div class="field">
          <label for="ch-key">รหัสร้าน (site key) <span class="req">*</span></label>
          <input id="ch-key" type="text" name="chat_site_key" value="<?= e($key) ?>"
                 placeholder="รับจากหลังบ้าน tobwai.com">
          <p class="hint">
            รหัสนี้บอก tobwai ว่าข้อความมาจากร้านไหน ได้จากหน้าตั้งค่าในบัญชี tobwai ของสตูดิโอ
          </p>
        </div>

        <div class="field mb-0">
          <label for="ch-endpoint">ที่อยู่ API</label>
          <input id="ch-endpoint" type="url" name="chat_endpoint" value="<?= e($endpoint) ?>">
          <p class="hint">ปกติไม่ต้องแก้ ใช้ค่าเริ่มต้น <code>https://tobwai.com/chat-api.php</code></p>
        </div>
      </div>

      <?php if (setting('chat_test_at') !== ''): ?>
        <div class="panel__foot">
          <span class="text-sm text-muted">
            ทดสอบล่าสุด <?= e(thai_datetime(setting('chat_test_at'))) ?> —
            <strong><?= e(setting('chat_test_result')) ?></strong>
          </span>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel__head"><h2 class="panel__title">ข้อความและหน้าตา</h2></div>
      <div class="panel__body">
        <div class="field">
          <label for="ch-bubble">ข้อความบนปุ่มลอย</label>
          <input id="ch-bubble" type="text" name="chat_bubble_label"
                 value="<?= e(setting('chat_bubble_label', 'แชทกับเรา')) ?>" maxlength="40">
        </div>
        <div class="field">
          <label for="ch-status">ข้อความใต้ชื่อร้านในกล่องแชท</label>
          <input id="ch-status" type="text" name="chat_status_text"
                 value="<?= e(setting('chat_status_text', 'ตอบกลับอัตโนมัติ · ทีมงานเข้ามาตอบเองในเวลาทำการ')) ?>"
                 maxlength="120">
        </div>
        <div class="field mb-0">
          <label for="ch-welcome">ข้อความทักทายแรก</label>
          <textarea id="ch-welcome" name="chat_welcome" rows="4"><?= e(setting('chat_welcome', "สวัสดีค่ะ สายแบ้วสตูดิโอยินดีให้บริการค่ะ\nสนใจถ่ายงานประเภทไหน วันที่เท่าไหร่ดีคะ")) ?></textarea>
          <p class="hint">ข้อความนี้แสดงจากฝั่งเว็บทันทีที่เปิดกล่อง ยังไม่ได้เรียก tobwai</p>
        </div>
      </div>
      <div class="panel__foot">
        <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกการตั้งค่า</button>
      </div>
    </div>

  </div>
</form>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">ทำไมแชทถึงไม่ได้อยู่ในเว็บนี้</h2></div>
  <div class="panel__body">
    <p class="text-sm text-muted mb-0">
      บทสนทนา ประวัติแชท และการตอบอัตโนมัติทั้งหมดอยู่ที่ <strong>tobwai.com</strong> ไม่ได้เก็บไว้ที่เว็บนี้
      เว็บนี้เป็นเจ้าของแค่ปุ่มกับกล่องที่ลูกค้าเห็นเท่านั้น — ทีมงานเข้าไปอ่านและตอบเองได้ที่หลังบ้าน tobwai
      ซึ่งเป็นที่เดียวกับที่รับข้อความจาก LINE และ Facebook อยู่แล้ว จึงไม่ต้องเปิดสองที่
    </p>
  </div>
</div>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
