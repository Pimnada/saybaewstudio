<?php
/**
 * Image processing for the album uploader.
 *
 * This is a photographer's site, so the file the camera produced is stored
 * untouched in orig/ and is what the customer downloads. Two derivatives are
 * generated for the browser: a 600px thumbnail for grids and a 2048px preview
 * for the lightbox, both at a quality high enough that a parent zooming in on
 * a phone sees no mush.
 *
 * Imagick is preferred (better resampling, keeps the colour profile); GD is the
 * fallback. Nothing here shells out — exec() is disabled on the target host.
 */

require_once __DIR__ . '/lib.php';

const THUMB_MAX   = 600;
const PREVIEW_MAX = 2048;
const THUMB_Q     = 82;
const PREVIEW_Q   = 90;

const ALLOWED_IMAGE_MIME = [
    'image/jpeg' => 'jpg',
    'image/pjpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

function has_imagick(): bool
{
    return extension_loaded('imagick') && class_exists('Imagick');
}

/**
 * Give big camera files room to decompress. A 45 MP raw-ish JPEG needs roughly
 * width * height * 4 bytes in GD; Imagick is far leaner but still not free.
 */
function raise_limits_for_images(): void
{
    @ini_set('memory_limit', '768M');
    @set_time_limit(120);
}

function detect_mime(string $path): string
{
    $f = new finfo(FILEINFO_MIME_TYPE);
    return (string) $f->file($path);
}

/**
 * Store one uploaded photo: validate, move the original, build derivatives and
 * return the row data ready to insert. Throws RuntimeException on a bad file.
 */
function ingest_photo(int $albumId, string $tmpPath, string $originalName): array
{
    raise_limits_for_images();

    $mime = detect_mime($tmpPath);
    if (!isset(ALLOWED_IMAGE_MIME[$mime])) {
        throw new RuntimeException('ไฟล์ ' . $originalName . ' ไม่ใช่รูปภาพที่รองรับ (รองรับ JPG, PNG, WebP)');
    }
    $ext = ALLOWED_IMAGE_MIME[$mime];

    $size = @getimagesize($tmpPath);
    if (!$size) {
        throw new RuntimeException('ไฟล์ ' . $originalName . ' เสียหาย อ่านขนาดภาพไม่ได้');
    }
    [$width, $height] = $size;

    $base     = pathinfo($originalName, PATHINFO_FILENAME);
    $filename = photo_filename($albumId, $base, $ext);

    $origDir    = album_dir($albumId, 'orig');
    $thumbDir   = album_dir($albumId, 'thumb');
    $previewDir = album_dir($albumId, 'preview');

    $origPath = $origDir . '/' . $filename;
    if (is_uploaded_file($tmpPath)) {
        if (!move_uploaded_file($tmpPath, $origPath)) {
            throw new RuntimeException('บันทึกไฟล์ ' . $originalName . ' ไม่สำเร็จ');
        }
    } elseif (!@copy($tmpPath, $origPath)) {
        throw new RuntimeException('บันทึกไฟล์ ' . $originalName . ' ไม่สำเร็จ');
    }
    @chmod($origPath, 0664);

    // EXIF may say the camera was rotated; the derivatives are baked upright.
    $orientation = 1;
    $exif        = [];
    if ($ext === 'jpg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($origPath) ?: [];
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if (in_array($orientation, [5, 6, 7, 8], true)) {
            [$width, $height] = [$height, $width];
        }
    }

    make_derivative($origPath, $previewDir . '/' . $filename, PREVIEW_MAX, PREVIEW_Q, $orientation);
    make_derivative($origPath, $thumbDir . '/' . $filename, THUMB_MAX, THUMB_Q, $orientation);

    $takenAt = null;
    if (!empty($exif['DateTimeOriginal'])) {
        $ts = strtotime(str_replace(':', '-', substr($exif['DateTimeOriginal'], 0, 10))
                        . substr($exif['DateTimeOriginal'], 10));
        if ($ts) {
            $takenAt = date('Y-m-d H:i:s', $ts);
        }
    }

    return [
        'filename'  => $filename,
        'orig_name' => mb_substr($originalName, 0, 255),
        'ext'       => $ext,
        'mime'      => $mime,
        'bytes'     => (int) filesize($origPath),
        'width'     => (int) $width,
        'height'    => (int) $height,
        'taken_at'  => $takenAt,
    ];
}

/** Unique, safe, sortable filename that still hints at the original. */
function photo_filename(int $albumId, string $base, string $ext): string
{
    $base = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $base) ?: 'photo';
    $base = trim(mb_substr($base, 0, 60), '_');
    $name = $base . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;

    while (is_file(album_dir($albumId, 'orig') . '/' . $name)) {
        $name = $base . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    }
    return $name;
}

function make_derivative(string $src, string $dest, int $maxSide, int $quality, int $orientation = 1): bool
{
    return has_imagick()
        ? derivative_imagick($src, $dest, $maxSide, $quality)
        : derivative_gd($src, $dest, $maxSide, $quality, $orientation);
}

function derivative_imagick(string $src, string $dest, int $maxSide, int $quality): bool
{
    try {
        $im = new Imagick();
        // Hint the JPEG decoder to read at a reduced size — much less memory.
        $im->setOption('jpeg:size', ($maxSide * 2) . 'x' . ($maxSide * 2));
        $im->readImage($src);
        $im->autoOrient();

        $w = $im->getImageWidth();
        $h = $im->getImageHeight();
        if (max($w, $h) > $maxSide) {
            $im->resizeImage(
                $w >= $h ? $maxSide : 0,
                $w >= $h ? 0 : $maxSide,
                Imagick::FILTER_LANCZOS,
                1
            );
        }

        $im->setImageCompressionQuality($quality);
        if (strtolower($im->getImageFormat()) === 'jpeg') {
            $im->setInterlaceScheme(Imagick::INTERLACE_PLANE); // progressive
            $im->setImageFormat('jpeg');
        }
        $im->setImageResolution(72, 72);
        $im->stripImage();                       // drop bulky EXIF from derivatives
        $im->writeImage($dest);
        $im->clear();
        $im->destroy();
        @chmod($dest, 0664);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function derivative_gd(string $src, string $dest, int $maxSide, int $quality, int $orientation = 1): bool
{
    $info = @getimagesize($src);
    if (!$info) {
        return false;
    }

    $img = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => @imagecreatefromwebp($src),
        default        => null,
    };
    if (!$img) {
        return false;
    }

    $img = gd_apply_orientation($img, $orientation);

    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_WEBP) {
        imagealphablending($out, false);
        imagesavealpha($out, true);
    }
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $ok = match ($info[2]) {
        IMAGETYPE_PNG  => imagepng($out, $dest, 6),
        IMAGETYPE_WEBP => imagewebp($out, $dest, $quality),
        default        => (imageinterlace($out, true) !== false)
                          && imagejpeg($out, $dest, $quality),
    };

    imagedestroy($img);
    imagedestroy($out);
    @chmod($dest, 0664);
    return (bool) $ok;
}

function gd_apply_orientation(GdImage $img, int $orientation): GdImage
{
    return match ($orientation) {
        3       => imagerotate($img, 180, 0),
        6       => imagerotate($img, -90, 0),
        8       => imagerotate($img, 90, 0),
        default => $img,
    };
}

/** Remove every file belonging to one photo row. */
function delete_photo_files(array $photo): void
{
    foreach (['orig', 'preview', 'thumb'] as $size) {
        $path = upload_path('albums/' . $photo['album_id'] . '/' . $size . '/' . $photo['filename']);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Remove an album's whole directory tree from disk.
 *
 * delete_photo_files() clears the files a photo row knows about, which leaves
 * two things behind: the empty orig/ preview/ thumb/ folders (rmdir only ever
 * removes an empty directory, and the album folder still held those three), and
 * any file whose database row went missing — an upload that landed on disk
 * before its INSERT failed, for instance. Deleting an album should leave nothing
 * of it, so the whole tree goes.
 *
 * Guarded deliberately: the id must be a positive integer and the resolved path
 * must sit inside uploads/albums/. A recursive delete driven by anything less
 * strict than that is one bad variable away from erasing the wrong directory.
 */
function delete_album_dir(int $albumId): int
{
    if ($albumId <= 0) {
        return 0;
    }

    $base = realpath(upload_path('albums'));
    $dir  = realpath(upload_path('albums/' . $albumId));
    if ($base === false || $dir === false) {
        return 0;
    }
    if ($dir === $base || !str_starts_with($dir, $base . DIRECTORY_SEPARATOR)) {
        return 0;                       // นอกขอบเขตที่อนุญาต — ไม่แตะ
    }

    $removed = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } elseif (@unlink($item->getPathname())) {
            $removed++;
        }
    }
    @rmdir($dir);

    return $removed;
}

/**
 * Save a simple one-off image (banner, avatar, article cover) into uploads/$dir
 * and return the path relative to uploads/. Returns null when nothing was sent.
 */
function save_simple_image(array $file, string $dir, int $maxSide = 1600): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ (รหัส ' . $file['error'] . ')');
    }

    raise_limits_for_images();
    $mime = detect_mime($file['tmp_name']);
    if (!isset(ALLOWED_IMAGE_MIME[$mime])) {
        throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG และ WebP');
    }

    $ext  = ALLOWED_IMAGE_MIME[$mime];
    $name = date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $abs  = upload_path($dir);
    if (!is_dir($abs)) {
        @mkdir($abs, 0775, true);
    }

    $dest = $abs . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('บันทึกรูปไม่สำเร็จ');
    }
    make_derivative($dest, $dest, $maxSide, 88);
    @chmod($dest, 0664);

    return $dir . '/' . $name;
}
