<?php
/**
 * Chat widget, powered by tobwai.com.
 *
 * The studio is a tobwai customer: this site owns the bubble and the panel, and
 * tobwai owns the conversation, the AI and the inbox the studio replies from.
 * Nothing about the conversation is stored here — that is the whole point of
 * paying for tobwai rather than keeping a second half-built inbox.
 *
 * Included from inc/footer.php, and only when the admin has switched it on and
 * filled in a site key. Without both, this file renders nothing at all rather
 * than a bubble that opens onto an error.
 */

$chatOn       = setting('chat_enabled', '0') === '1';
$chatEndpoint = trim(setting('chat_endpoint', 'https://tobwai.com/chat-api.php'));
$chatKey      = trim(setting('chat_site_key', ''));

if (!$chatOn || $chatKey === '' || $chatEndpoint === '') {
    return;
}
?>
<div class="tw-chat" data-tw-chat
     data-endpoint="<?= e($chatEndpoint) ?>"
     data-key="<?= e($chatKey) ?>">

  <button class="tw-chat__bubble" type="button" data-tw-open aria-label="เปิดกล่องแชท">
    <?= icon('chat-fast', '', 24) ?>
    <span><?= e(setting('chat_bubble_label', 'แชทกับเรา')) ?></span>
  </button>

  <section class="tw-chat__panel" data-tw-panel hidden aria-label="กล่องแชท">
    <header class="tw-chat__head">
      <span class="tw-chat__mark"><?= icon('camera', '', 20) ?></span>
      <span class="tw-chat__title">
        <strong><?= e(setting('site_name')) ?></strong>
        <small><?= e(setting('chat_status_text', 'ตอบกลับอัตโนมัติ · ทีมงานเข้ามาตอบเองในเวลาทำการ')) ?></small>
      </span>
      <button class="tw-chat__close" type="button" data-tw-close aria-label="ปิด"><?= icon('close', '', 18) ?></button>
    </header>

    <div class="tw-chat__log" data-tw-log>
      <div class="tw-msg tw-msg--in">
        <?= nl2br(e(setting('chat_welcome', "สวัสดีค่ะ สายแบ้วสตูดิโอยินดีให้บริการค่ะ\nสนใจถ่ายงานประเภทไหน วันที่เท่าไหร่ดีคะ"))) ?>
      </div>
    </div>

    <form class="tw-chat__form" data-tw-form>
      <input type="text" data-tw-input maxlength="500" autocomplete="off"
             placeholder="พิมพ์ข้อความ..." aria-label="ข้อความ">
      <button type="submit" data-tw-send aria-label="ส่ง"><?= icon('arrow-right', '', 18) ?></button>
    </form>

    <p class="tw-chat__foot">ขับเคลื่อนโดย <a href="https://tobwai.com" target="_blank" rel="noopener">tobwai.com</a></p>
  </section>
</div>
