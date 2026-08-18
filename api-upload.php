<?php
/**
 * Receives ONE photo per request from assets/js/uploader.js.
 *
 * One file per request on purpose: nginx cuts a request body at roughly
 * 128 MiB, so a single multipart POST carrying 300 camera JPEGs would be
 * refused outright. Three of these run concurrently from the browser.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';

require_admin();

if (!is_post()) {
    json_out(['ok' => false, 'error' => 'ต้องส่งด้วยวิธี POST'], 405);
}

/**
 * A body bigger than post_max_size arrives with $_POST and $_FILES already
 * emptied by PHP. csrf_check() would then fail and report "เซสชันหมดอายุ",
 * which sends whoever is debugging in completely the wrong direction — so the
 * size case is caught first and named for what it is.
 */
$sent    = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMax = ini_bytes((string) ini_get('post_max_size'));
if ($postMax > 0 && $sent > $postMax && !$_POST && !$_FILES) {
    json_out([
        'ok'    => false,
        'error' => 'ไฟล์ใหญ่ ' . fmt_bytes($sent) . ' เกินกว่าที่เซิร์ฟเวอร์รับได้ ('
                 . fmt_bytes(max_upload_bytes()) . ' ต่อไฟล์) '
                 . 'ต้องเพิ่ม upload_max_filesize และ post_max_size ใน PHP ก่อน',
    ], 413);
}

csrf_check();

$albumId = (int) ($_POST['album_id'] ?? 0);
$st = db()->prepare('SELECT id FROM albums WHERE id = ?');
$st->execute([$albumId]);
if (!$st->fetchColumn()) {
    json_out(['ok' => false, 'error' => 'ไม่พบอัลบั้มปลายทาง'], 404);
}

$folderId = ($_POST['folder_id'] ?? '') !== '' ? (int) $_POST['folder_id'] : null;
if ($folderId !== null) {
    $fs = db()->prepare('SELECT id FROM folders WHERE id = ? AND album_id = ?');
    $fs->execute([$folderId, $albumId]);
    if (!$fs->fetchColumn()) {
        $folderId = null;
    }
}

$file = $_FILES['photo'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    json_out(['ok' => false, 'error' => match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
            'ไฟล์ใหญ่เกินกว่าที่เซิร์ฟเวอร์รับได้ — ขณะนี้รับได้ไม่เกิน '
            . fmt_bytes(max_upload_bytes()) . ' ต่อไฟล์ '
            . '(upload_max_filesize = ' . ini_get('upload_max_filesize')
            . ', post_max_size = ' . ini_get('post_max_size') . ')',
        UPLOAD_ERR_PARTIAL   => 'ไฟล์อัปโหลดไม่ครบ กรุณาลองใหม่',
        UPLOAD_ERR_NO_FILE   => 'ไม่พบไฟล์ที่ส่งมา',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'เซิร์ฟเวอร์เขียนไฟล์ชั่วคราวไม่ได้',
        default              => 'อัปโหลดไม่สำเร็จ (รหัส ' . $code . ')',
    }], 400);
}

try {
    $data = ingest_photo($albumId, $file['tmp_name'], $file['name']);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 422);
}

$max = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM photos WHERE album_id = ?');
$max->execute([$albumId]);
$sortOrder = (int) $max->fetchColumn() + 1;

db()->prepare(
    'INSERT INTO photos (album_id, folder_id, filename, orig_name, ext, mime, bytes,
                         width, height, taken_at, sort_order)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([
    $albumId, $folderId, $data['filename'], $data['orig_name'], $data['ext'], $data['mime'],
    $data['bytes'], $data['width'], $data['height'], $data['taken_at'], $sortOrder,
]);
$photoId = (int) db()->lastInsertId();

db()->prepare('UPDATE albums SET updated_at = ? WHERE id = ?')
    ->execute([date('Y-m-d H:i:s'), $albumId]);

// The cached storage figure in the sidebar is now stale.
set_setting('storage_bytes_at', '0');

json_out([
    'ok'    => true,
    'photo' => [
        'id'    => $photoId,
        'name'  => $data['orig_name'],
        'thumb' => upload_url('albums/' . $albumId . '/thumb/' . $data['filename']),
        'full'  => upload_url('albums/' . $albumId . '/preview/' . $data['filename']),
        'size'  => fmt_bytes($data['bytes']),
        'date'  => thai_date(date('Y-m-d')),
        'w'     => $data['width'],
        'h'     => $data['height'],
    ],
]);
