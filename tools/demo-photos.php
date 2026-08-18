<?php
/**
 * Generates placeholder photographs so a fresh install has something to show.
 *
 *   php tools/demo-photos.php            fill every album that is still empty
 *   php tools/demo-photos.php --force    wipe the generated ones and start over
 *
 * These are drawn abstract images, not photographs of children — real work
 * replaces them the first time the studio uploads an album. CLI only.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('เครื่องมือนี้รันได้จากบรรทัดคำสั่งเท่านั้น');
}

require_once __DIR__ . '/../image.php';

$force = in_array('--force', $argv, true);
$pdo   = db();

/** Warm palettes, one per album, so each album reads as its own event. */
const PALETTES = [
    [[241, 198, 208], [140, 96, 150], [252, 232, 216]],   // stage lights
    [[252, 226, 200], [232, 160, 140], [255, 246, 232]],   // birthday
    [[214, 226, 240], [120, 148, 190], [246, 250, 255]],   // certificates
    [[236, 232, 222], [176, 128, 58],  [252, 249, 242]],   // studio profile
    [[206, 232, 206], [96,  150, 110], [244, 252, 244]],   // sports day
    [[232, 216, 240], [148, 116, 176], [250, 244, 254]],   // fashion
];

function draw_demo(string $path, array $palette, int $seed, string $label, int $w = 1600, int $h = 1067): void
{
    mt_srand($seed);

    $img = imagecreatetruecolor($w, $h);
    imageantialias($img, true);

    [$a, $b, $c] = $palette;

    // Vertical gradient base.
    for ($y = 0; $y < $h; $y++) {
        $t = $y / $h;
        $col = imagecolorallocate(
            $img,
            (int) ($c[0] + ($a[0] - $c[0]) * $t),
            (int) ($c[1] + ($a[1] - $c[1]) * $t),
            (int) ($c[2] + ($a[2] - $c[2]) * $t)
        );
        imageline($img, 0, $y, $w, $y, $col);
    }

    // Soft bokeh circles, the way a stage looks at f/1.8.
    for ($i = 0; $i < 26; $i++) {
        $r  = mt_rand(40, 260);
        $x  = mt_rand(-100, $w + 100);
        $y  = mt_rand(-100, $h + 100);
        $mix = mt_rand(0, 100) / 100;
        $col = imagecolorallocatealpha(
            $img,
            (int) ($b[0] + ($c[0] - $b[0]) * $mix),
            (int) ($b[1] + ($c[1] - $b[1]) * $mix),
            (int) ($b[2] + ($c[2] - $b[2]) * $mix),
            mt_rand(80, 112)
        );
        imagefilledellipse($img, $x, $y, $r, $r, $col);
    }

    // A darker vignette so the frame reads as a photograph, not a swatch.
    $shadow = imagecolorallocatealpha($img, 30, 22, 14, 108);
    imagefilledrectangle($img, 0, (int) ($h * 0.78), $w, $h, $shadow);

    // Label plate, bottom left.
    $plate = imagecolorallocatealpha($img, 20, 17, 13, 44);
    imagefilledrectangle($img, 48, $h - 132, 48 + 20 + strlen($label) * 11, $h - 76, $plate);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 5, 62, $h - 118, $label, $white);

    $gold = imagecolorallocate($img, 208, 165, 94);
    imagestring($img, 3, 62, $h - 168, 'saybaewstudio', $gold);

    imageinterlace($img, true);
    imagejpeg($img, $path, 92);
    imagedestroy($img);
}

$albums = $pdo->query('SELECT * FROM albums ORDER BY id')->fetchAll();
if (!$albums) {
    exit("ยังไม่มีอัลบั้มในฐานข้อมูล — เปิดหน้าเว็บหนึ่งครั้งเพื่อให้ระบบสร้างข้อมูลตั้งต้นก่อน\n");
}

// How many photos each seeded album gets. Album 1 is deliberately the big one.
$counts = [1 => 24, 2 => 16, 3 => 14, 4 => 10, 5 => 18, 6 => 12];

$tmpDir = sys_get_temp_dir() . '/sbs-demo';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0775, true);
}

$made = 0;

foreach ($albums as $index => $album) {
    $albumId = (int) $album['id'];

    $st = $pdo->prepare('SELECT COUNT(*) FROM photos WHERE album_id = ?');
    $st->execute([$albumId]);
    $existing = (int) $st->fetchColumn();

    if ($existing > 0 && !$force) {
        echo "อัลบั้ม #$albumId มีรูปอยู่แล้ว $existing รูป — ข้าม\n";
        continue;
    }

    if ($force && $existing > 0) {
        $st = $pdo->prepare('SELECT * FROM photos WHERE album_id = ?');
        $st->execute([$albumId]);
        foreach ($st->fetchAll() as $p) {
            delete_photo_files($p);
        }
        $pdo->prepare('DELETE FROM photos WHERE album_id = ?')->execute([$albumId]);
        echo "ล้างรูปเดิมของอัลบั้ม #$albumId แล้ว\n";
    }

    $palette = PALETTES[$index % count(PALETTES)];
    $target  = $counts[$albumId] ?? 12;

    // Spread the photos across whatever folders the album has.
    $fs = $pdo->prepare('SELECT id FROM folders WHERE album_id = ? ORDER BY sort_order');
    $fs->execute([$albumId]);
    $folderIds = $fs->fetchAll(PDO::FETCH_COLUMN);

    echo "สร้างรูป $target รูปให้อัลบั้ม #$albumId ({$album['title']})...\n";

    for ($i = 1; $i <= $target; $i++) {
        $tmp = $tmpDir . '/gen.jpg';
        draw_demo($tmp, $palette, $albumId * 1000 + $i, sprintf('IMG_%05d', 123 + $i));

        try {
            $data = ingest_photo($albumId, $tmp, sprintf('IMG_%05d.jpg', 123 + ($albumId * 40) + $i));
        } catch (Throwable $e) {
            echo "  ! " . $e->getMessage() . "\n";
            continue;
        }

        $folderId = $folderIds ? (int) $folderIds[($i - 1) % count($folderIds)] : null;

        $pdo->prepare(
            'INSERT INTO photos (album_id, folder_id, filename, orig_name, ext, mime, bytes,
                                 width, height, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $albumId, $folderId, $data['filename'], $data['orig_name'], $data['ext'], $data['mime'],
            $data['bytes'], $data['width'], $data['height'], $i,
        ]);
        $made++;
    }
}

// Service tiles reuse one photo each so the homepage grid is not all placeholder.
$services = $pdo->query("SELECT * FROM services WHERE image IS NULL ORDER BY sort_order")->fetchAll();
if ($services) {
    $dir = upload_path('services');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    foreach ($services as $i => $s) {
        $name = 'service-' . $s['id'] . '.jpg';
        draw_demo($dir . '/' . $name, PALETTES[$i % count(PALETTES)], 7000 + $i, strtoupper(substr($s['slug'], 0, 12)), 1200, 900);
        $pdo->prepare('UPDATE services SET image = ? WHERE id = ?')->execute(['services/' . $name, $s['id']]);
        echo "สร้างรูปประกอบให้ประเภทงาน: {$s['title']}\n";
    }
}

@unlink($tmpDir . '/gen.jpg');
set_setting('storage_bytes_at', '0');

echo "\nเสร็จแล้ว — สร้างรูปทั้งหมด $made รูป\n";
echo "พื้นที่ที่ใช้: " . fmt_bytes(dir_size(upload_path())) . "\n";
