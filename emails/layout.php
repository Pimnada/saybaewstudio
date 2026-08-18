<?php
/**
 * Shared shell for every letter. Table-based and inline-styled because email
 * clients still are what they are. The wordmark is drawn with type rather than
 * an image so it survives "images off" in Outlook and Gmail.
 *
 * Expects: $subject, $preheader, $content (already-rendered HTML body).
 */
$gold   = '#B0803A';
$dark   = '#1A1611';
$cream  = '#F6F2EA';
$text   = '#2A241C';
$muted  = '#7C7267';
$border = '#E5DCCB';
$site   = rtrim(SITE_URL, '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title><?= e($subject) ?></title>
<style>
  @media only screen and (max-width:620px) {
    .sbs-wrap { width:100% !important; }
    .sbs-pad  { padding-left:22px !important; padding-right:22px !important; }
    .sbs-h1   { font-size:22px !important; }
    .sbs-col  { display:block !important; width:100% !important; padding-right:0 !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:<?= $cream ?>;">

<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;">
  <?= e($preheader ?: excerpt(strip_tags($content), 120)) ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?= $cream ?>;">
<tr><td align="center" style="padding:28px 12px;">

  <table role="presentation" class="sbs-wrap" width="600" cellpadding="0" cellspacing="0" border="0"
         style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;
                border:1px solid <?= $border ?>;font-family:'Helvetica Neue',Helvetica,Arial,'Noto Sans Thai',sans-serif;">

    <!-- header -->
    <tr>
      <td class="sbs-pad" style="background:<?= $dark ?>;padding:24px 34px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="padding-right:12px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="width:42px;height:42px;border:2px solid <?= $gold ?>;border-radius:50%;">
                <tr><td align="center" valign="middle"
                        style="width:42px;height:42px;color:<?= $gold ?>;font-size:17px;
                               font-weight:700;letter-spacing:.5px;line-height:1;">S</td></tr>
              </table>
            </td>
            <td>
              <div style="color:#ffffff;font-size:17px;font-weight:700;line-height:1.3;">สายแบ้วสตูดิโอ</div>
              <div style="color:<?= $gold ?>;font-size:11px;letter-spacing:2.4px;text-transform:uppercase;">saybaewstudio</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- body -->
    <tr>
      <td class="sbs-pad" style="padding:34px;color:<?= $text ?>;font-size:15px;line-height:1.85;">
        <?= $content ?>
      </td>
    </tr>

    <!-- footer -->
    <tr>
      <td class="sbs-pad" style="background:#FBF8F2;border-top:1px solid <?= $border ?>;padding:24px 34px;
                                 color:<?= $muted ?>;font-size:12.5px;line-height:1.8;">
        <div style="color:<?= $text ?>;font-weight:700;font-size:13px;margin-bottom:6px;">สายแบ้วสตูดิโอ</div>
        <div>รับถ่ายภาพเด็กและกิจกรรมทุกช่วงวัย — กรุงเทพฯ และปริมณฑล</div>
        <div style="margin-top:8px;">
          โทร. <?= e(setting('contact_phone', '081-234-5678')) ?> &nbsp;·&nbsp;
          LINE <?= e(setting('contact_line', '@saybaewstudio')) ?> &nbsp;·&nbsp;
          <a href="<?= e($site) ?>" style="color:<?= $gold ?>;text-decoration:none;">saybaewstudio.com</a>
        </div>
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid <?= $border ?>;font-size:11.5px;color:#9A9186;">
          จดหมายฉบับนี้ส่งจากระบบอัตโนมัติของสายแบ้วสตูดิโอ หากคุณไม่ได้เป็นผู้ติดต่อเข้ามา
          สามารถละเว้นอีเมลฉบับนี้ได้เลย
        </div>
      </td>
    </tr>

  </table>

  <div style="max-width:600px;margin:16px auto 0;color:#9A9186;font-size:11.5px;
              font-family:'Helvetica Neue',Helvetica,Arial,'Noto Sans Thai',sans-serif;text-align:center;">
    © <?= date('Y') + 543 ?> สายแบ้วสตูดิโอ saybaewstudio
  </div>

</td></tr>
</table>
</body>
</html>
