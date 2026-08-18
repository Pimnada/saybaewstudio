<?php
/**
 * Download handler: one original file, or a whole album/folder as a ZIP.
 *
 * Files are streamed in chunks rather than with readfile()/fpassthru() — the
 * Cloudways FPM pool has fpassthru disabled, and a 40 MB original must not be
 * pulled into memory to be sent.
 */

require_once __DIR__ . '/../auth.php';

/** How much a single ZIP may contain before we ask the customer to split it. */
const ZIP_MAX_FILES = 400;
const ZIP_MAX_BYTES = 3 * 1024 * 1024 * 1024;   // 3 GB

function stream_file(string $path, string $downloadName, string $mime = 'application/octet-stream'): never
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('ไม่พบไฟล์');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $size = filesize($path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: attachment; filename="' . preg_replace('/["\r\n]/', '', $downloadName) . '"; '
         . "filename*=UTF-8''" . rawurlencode($downloadName));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');

    $fp = fopen($path, 'rb');
    if (!$fp) {
        http_response_code(500);
        exit('เปิดไฟล์ไม่ได้');
    }
    while (!feof($fp)) {
        echo fread($fp, 262144);
        flush();
    }
    fclose($fp);
    exit;
}

/** The album must be published, downloadable and — if coded — already unlocked. */
function assert_album_downloadable(array $album): void
{
    if ($album['status'] !== 'published' || !$album['allow_download']
        || setting('download_enabled', '1') !== '1') {
        http_response_code(403);
        exit('อัลบั้มนี้ไม่เปิดให้ดาวน์โหลด');
    }
    if ($album['access'] === 'code' && $album['access_code'] !== '') {
        boot_session();
        if (($_SESSION['album_ok'][(int) $album['id']] ?? false) !== true) {
            http_response_code(403);
            exit('กรุณาใส่รหัสเข้าชมอัลบั้มก่อนดาวน์โหลด');
        }
    }
}

// ------------------------------------------------------------- one photo ---

if (isset($_GET['photo'])) {
    $st = db()->prepare('SELECT * FROM photos WHERE id = ?');
    $st->execute([(int) $_GET['photo']]);
    $photo = $st->fetch();
    if (!$photo) {
        http_response_code(404);
        exit('ไม่พบรูปนี้');
    }

    $st = db()->prepare('SELECT * FROM albums WHERE id = ?');
    $st->execute([$photo['album_id']]);
    $album = $st->fetch();
    if (!$album) {
        http_response_code(404);
        exit('ไม่พบอัลบั้ม');
    }
    assert_album_downloadable($album);

    db()->prepare('UPDATE photos SET downloads = downloads + 1 WHERE id = ?')->execute([$photo['id']]);
    db()->prepare('UPDATE albums SET downloads = downloads + 1 WHERE id = ?')->execute([$album['id']]);

    stream_file(
        upload_path('albums/' . $photo['album_id'] . '/orig/' . $photo['filename']),
        $photo['orig_name'],
        $photo['mime']
    );
}

// -------------------------------------------------------------- whole zip ---

if (isset($_GET['album'])) {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('เซิร์ฟเวอร์นี้ยังไม่รองรับการดาวน์โหลดแบบ ZIP กรุณาดาวน์โหลดทีละรูป');
    }

    $st = db()->prepare('SELECT * FROM albums WHERE id = ?');
    $st->execute([(int) $_GET['album']]);
    $album = $st->fetch();
    if (!$album) {
        http_response_code(404);
        exit('ไม่พบอัลบั้ม');
    }
    assert_album_downloadable($album);

    $folderId = isset($_GET['folder']) ? (int) $_GET['folder'] : 0;
    $sql      = 'SELECT * FROM photos WHERE album_id = ?';
    $params   = [$album['id']];
    if ($folderId > 0) {
        $sql     .= ' AND folder_id = ?';
        $params[] = $folderId;
    }
    $sql .= ' ORDER BY sort_order, id';

    $ps = db()->prepare($sql);
    $ps->execute($params);
    $photos = $ps->fetchAll();

    if (!$photos) {
        http_response_code(404);
        exit('อัลบั้มนี้ยังไม่มีรูป');
    }

    $bytes = array_sum(array_column($photos, 'bytes'));
    if (count($photos) > ZIP_MAX_FILES || $bytes > ZIP_MAX_BYTES) {
        http_response_code(413);
        exit('อัลบั้มนี้ใหญ่เกินกว่าจะรวมเป็นไฟล์เดียว ('
            . fmt_num(count($photos)) . ' รูป, ' . fmt_bytes((int) $bytes) . ') '
            . 'กรุณาเลือกดาวน์โหลดทีละโฟลเดอร์ หรือทีละรูปแทนค่ะ');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $tmpDir = upload_path('tmp');
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0775, true);
    }
    $tmpZip = $tmpDir . '/album-' . $album['id'] . '-' . bin2hex(random_bytes(6)) . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        exit('สร้างไฟล์ ZIP ไม่สำเร็จ');
    }

    // Photos are already JPEG — storing them uncompressed is far faster and the
    // resulting file is essentially the same size.
    foreach ($photos as $i => $p) {
        $src = upload_path('albums/' . $p['album_id'] . '/orig/' . $p['filename']);
        if (!is_file($src)) {
            continue;
        }
        $entry = str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT) . '_' . $p['orig_name'];
        $zip->addFile($src, $entry);
        if (method_exists($zip, 'setCompressionName')) {
            $zip->setCompressionName($entry, ZipArchive::CM_STORE);
        }
    }
    $zip->close();

    db()->prepare('UPDATE albums SET downloads = downloads + 1 WHERE id = ?')->execute([$album['id']]);

    $name = slugify($album['title']) . '.zip';

    // Delete the temp file once the response has been flushed to the client.
    register_shutdown_function(static function () use ($tmpZip) {
        @unlink($tmpZip);
    });

    stream_file($tmpZip, $name, 'application/zip');
}

http_response_code(400);
exit('คำขอไม่ถูกต้อง');
