<?php
/**
 * Bulk operations on selected photos, called from admin-album.php.
 * Everything except the ZIP download answers JSON.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../image.php';

require_admin();

/** Read an id list from either a POST array or a comma-separated GET value. */
function requested_ids(): array
{
    $raw = $_POST['ids'] ?? $_GET['ids'] ?? [];
    if (is_string($raw)) {
        $raw = explode(',', $raw);
    }
    $ids = array_values(array_filter(array_map('intval', (array) $raw)));
    return array_slice($ids, 0, 2000);
}

$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');

// ---------------------------------------------------------------- ZIP (GET) --

if ($action === 'zip') {
    $ids = requested_ids();
    if (!$ids) {
        http_response_code(400);
        exit('ยังไม่ได้เลือกรูป');
    }
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('เซิร์ฟเวอร์นี้ยังไม่รองรับการดาวน์โหลดแบบ ZIP');
    }

    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM photos WHERE id IN ($in) ORDER BY sort_order, id");
    $st->execute($ids);
    $photos = $st->fetchAll();

    if (!$photos) {
        http_response_code(404);
        exit('ไม่พบรูปที่เลือก');
    }

    @set_time_limit(0);
    $tmpDir = upload_path('tmp');
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0775, true);
    }
    $tmpZip = $tmpDir . '/selected-' . bin2hex(random_bytes(6)) . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        exit('สร้างไฟล์ ZIP ไม่สำเร็จ');
    }
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

    register_shutdown_function(static function () use ($tmpZip) {
        @unlink($tmpZip);
    });

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($tmpZip));
    header('Content-Disposition: attachment; filename="saybaewstudio-photos.zip"');
    $fp = fopen($tmpZip, 'rb');
    while ($fp && !feof($fp)) {
        echo fread($fp, 262144);
        flush();
    }
    if ($fp) {
        fclose($fp);
    }
    exit;
}

// -------------------------------------------------------------- JSON (POST) --

if (!is_post()) {
    json_out(['ok' => false, 'error' => 'ต้องส่งด้วยวิธี POST'], 405);
}
csrf_check();

$ids = requested_ids();

if ($action === 'delete') {
    if (!$ids) {
        json_out(['ok' => false, 'error' => 'ยังไม่ได้เลือกรูป'], 400);
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM photos WHERE id IN ($in)");
    $st->execute($ids);
    $photos = $st->fetchAll();

    foreach ($photos as $p) {
        delete_photo_files($p);
    }
    db()->prepare("DELETE FROM photos WHERE id IN ($in)")->execute($ids);

    // A deleted cover must not leave the album pointing at nothing.
    db()->prepare("UPDATE albums SET cover_photo_id = NULL
                    WHERE cover_photo_id IS NOT NULL
                      AND cover_photo_id NOT IN (SELECT id FROM photos)")->execute();

    set_setting('storage_bytes_at', '0');
    log_activity('photos.delete', count($photos) . ' รูป');
    json_out(['ok' => true, 'deleted' => count($photos)]);
}

if ($action === 'move') {
    if (!$ids) {
        json_out(['ok' => false, 'error' => 'ยังไม่ได้เลือกรูป'], 400);
    }
    $albumId  = (int) ($_POST['album_id'] ?? 0);
    $folderId = ($_POST['folder_id'] ?? '') !== '' ? (int) $_POST['folder_id'] : null;

    if ($folderId !== null) {
        $fs = db()->prepare('SELECT id FROM folders WHERE id = ? AND album_id = ?');
        $fs->execute([$folderId, $albumId]);
        if (!$fs->fetchColumn()) {
            json_out(['ok' => false, 'error' => 'ไม่พบโฟลเดอร์ปลายทางในอัลบั้มนี้'], 404);
        }
    }

    $in = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare("UPDATE photos SET folder_id = ? WHERE id IN ($in) AND album_id = ?")
        ->execute(array_merge([$folderId], $ids, [$albumId]));

    log_activity('photos.move', count($ids) . ' รูป → folder ' . ($folderId ?? 'root'));
    json_out(['ok' => true, 'moved' => count($ids)]);
}

if ($action === 'cover') {
    $albumId = (int) ($_POST['album_id'] ?? 0);
    $photoId = (int) ($ids[0] ?? 0);

    $st = db()->prepare('SELECT id FROM photos WHERE id = ? AND album_id = ?');
    $st->execute([$photoId, $albumId]);
    if (!$st->fetchColumn()) {
        json_out(['ok' => false, 'error' => 'ไม่พบรูปนี้ในอัลบั้ม'], 404);
    }

    db()->prepare('UPDATE albums SET cover_photo_id = ?, updated_at = ? WHERE id = ?')
        ->execute([$photoId, date('Y-m-d H:i:s'), $albumId]);

    json_out(['ok' => true]);
}

if ($action === 'reorder') {
    foreach ($ids as $i => $id) {
        db()->prepare('UPDATE photos SET sort_order = ? WHERE id = ?')->execute([$i + 1, $id]);
    }
    json_out(['ok' => true]);
}

json_out(['ok' => false, 'error' => 'ไม่รู้จักคำสั่งนี้'], 400);
