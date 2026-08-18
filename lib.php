<?php
/**
 * Shared helpers. Everything here is safe to include on both the public site
 * and the admin panel.
 */

require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Bangkok');

if (!defined('APP_DEBUG') || !APP_DEBUG) {
    ini_set('display_errors', '0');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ---------------------------------------------------------------- escaping ---

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Embed a PHP value as a JavaScript literal inside a <script> block.
 *
 * HTML escaping is wrong here: the browser does not decode entities inside a
 * script element, so htmlspecialchars() would put a literal &quot; into the
 * source and throw a SyntaxError. The JSON_HEX_* flags do the right job —
 * they neutralise <, >, & and quotes as \uXXXX escapes, which are valid JS and
 * make it impossible to break out of the script tag.
 *
 * For a JSON value inside an HTML *attribute*, use e(json_encode(...)) instead.
 */
function ejs($v): string
{
    return json_encode(
        $v,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}

// ----------------------------------------------------------------- routing ---

function url(string $path = ''): string
{
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = __DIR__ . '/' . ltrim($path, '/');
    $v    = is_file($file) ? filemtime($file) : time();
    return url($path) . '?v=' . $v;
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_path(): string
{
    return basename(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
}

/** JSON response for the admin XHR endpoints. */
function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------- settings ---

/**
 * Settings are cached in a global for the life of the request. A global rather
 * than a function static so set_setting() can drop it.
 */
function settings_all(): array
{
    if (!isset($GLOBALS['__settings'])) {
        $GLOBALS['__settings'] = [];
        foreach (db()->query('SELECT k, v FROM settings') as $row) {
            $GLOBALS['__settings'][$row['k']] = $row['v'];
        }
    }
    return $GLOBALS['__settings'];
}

function setting(string $key, $default = ''): string
{
    $all = settings_all();
    return isset($all[$key]) && $all[$key] !== '' ? (string) $all[$key] : (string) $default;
}

function set_setting(string $key, ?string $value): void
{
    $sql = db_is_sqlite()
        ? 'INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v'
        : 'INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)';
    db()->prepare($sql)->execute([$key, $value]);
    unset($GLOBALS['__settings']);
}

// ------------------------------------------------------------------ format ---

const THAI_MONTHS_SHORT = [
    1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
    'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.',
];

const THAI_MONTHS_FULL = [
    1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
];

/** "12 พ.ค. 2567" — Buddhist year, as every Thai customer expects to read it. */
function thai_date(?string $date, bool $full = false): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '-';
    }
    $m = (int) date('n', $ts);
    $months = $full ? THAI_MONTHS_FULL : THAI_MONTHS_SHORT;
    return (int) date('j', $ts) . ' ' . $months[$m] . ' ' . ((int) date('Y', $ts) + 543);
}

function thai_datetime(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? thai_date($date) . ' ' . date('H:i', $ts) . ' น.' : '-';
}

function time_ago(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $diff = time() - strtotime($date);
    if ($diff < 60)     return 'เมื่อสักครู่';
    if ($diff < 3600)   return floor($diff / 60) . ' นาทีที่แล้ว';
    if ($diff < 86400)  return floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
    if ($diff < 604800) return floor($diff / 86400) . ' วันที่แล้ว';
    return thai_date($date);
}

function fmt_bytes(int $bytes, int $decimals = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return number_format($n, $i < 2 ? 0 : $decimals) . ' ' . $units[$i];
}

function fmt_num(int $n): string
{
    return number_format($n);
}

function excerpt(?string $text, int $len = 140): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
    if (mb_strlen($text, 'UTF-8') <= $len) {
        return $text;
    }
    return mb_substr($text, 0, $len, 'UTF-8') . '…';
}

/**
 * URL slug that keeps Thai letters. Thai vowels and tone marks are Unicode
 * Marks — stripping them turns คุยกับครู into คยกบคร, so \p{M} stays in.
 */
function slugify(string $text): string
{
    $text = trim(mb_strtolower($text, 'UTF-8'));
    $text = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'item';
}

function unique_slug(string $table, string $text, ?int $ignoreId = null): string
{
    $base = slugify($text);
    $slug = $base;
    $i    = 2;
    $sql  = "SELECT COUNT(*) FROM `$table` WHERE slug = ?" . ($ignoreId ? ' AND id <> ?' : '');
    while (true) {
        $st = db()->prepare($sql);
        $st->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if ((int) $st->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

// ------------------------------------------------------------------ uploads --

function upload_path(string $rel = ''): string
{
    return __DIR__ . '/uploads/' . ltrim($rel, '/');
}

function upload_url(?string $rel): string
{
    if (!$rel) {
        return url('assets/img/placeholder.svg');
    }
    if (preg_match('#^https?://#', $rel)) {
        return $rel;
    }
    return url('uploads/' . ltrim($rel, '/'));
}

/** Directory layout for one album: orig/ keeps the untouched camera file. */
function album_dir(int $albumId, string $size = ''): string
{
    $dir = upload_path('albums/' . $albumId . ($size ? '/' . $size : ''));
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function photo_url(array $photo, string $size = 'thumb'): string
{
    $rel = 'albums/' . $photo['album_id'] . '/' . $size . '/' . $photo['filename'];
    if ($size !== 'orig' && !is_file(upload_path($rel))) {
        $rel = 'albums/' . $photo['album_id'] . '/orig/' . $photo['filename'];
    }
    return upload_url($rel);
}

/** Cover image for an album: the chosen one, else the first photo, else a placeholder. */
function album_cover(array $album, string $size = 'thumb'): string
{
    if (!empty($album['cover_image'])) {
        return upload_url($album['cover_image']);
    }

    $photo = null;
    if (!empty($album['cover_photo_id'])) {
        $st = db()->prepare('SELECT * FROM photos WHERE id = ?');
        $st->execute([$album['cover_photo_id']]);
        $photo = $st->fetch() ?: null;
    }
    if (!$photo) {
        $st = db()->prepare('SELECT * FROM photos WHERE album_id = ? ORDER BY sort_order, id LIMIT 1');
        $st->execute([$album['id']]);
        $photo = $st->fetch() ?: null;
    }

    return $photo ? photo_url($photo, $size) : url('assets/img/placeholder.svg');
}

function album_photo_count(int $albumId): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM photos WHERE album_id = ?');
    $st->execute([$albumId]);
    return (int) $st->fetchColumn();
}

function album_video_count(int $albumId): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM videos WHERE album_id = ? AND status = 'published'");
    $st->execute([$albumId]);
    return (int) $st->fetchColumn();
}

function dir_size(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }
    $total = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        $total += $file->getSize();
    }
    return $total;
}

// ------------------------------------------------------------------- flash ---

function flash(string $message, string $type = 'success'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flash(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $out = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $out;
}

// -------------------------------------------------------------------- misc ---

function client_ip(): string
{
    return (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
}

function log_activity(string $action, string $detail = ''): void
{
    $user = function_exists('current_user') ? current_user() : null;
    db()->prepare(
        'INSERT INTO activity_log (user_id, user_name, action, detail) VALUES (?, ?, ?, ?)'
    )->execute([$user['id'] ?? null, $user['name'] ?? 'system', $action, mb_substr($detail, 0, 400)]);
}

/** Record a public page view, one row per request, rolled up on the stats page. */
function track_visit(?int $albumId = null): void
{
    if (!empty($_SERVER['HTTP_X_MOZ']) || php_sapi_name() === 'cli') {
        return;
    }
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit/i', $ua)) {
        return;
    }
    try {
        db()->prepare(
            'INSERT INTO visits (path, album_id, ip_hash, referer, ua, day)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            mb_substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 300),
            $albumId,
            substr(hash('sha256', client_ip() . date('Y-m-d')), 0, 32),
            mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 400),
            mb_substr($ua, 0, 300),
            date('Y-m-d'),
        ]);
    } catch (Throwable $e) {
        // Statistics must never take a page down.
    }
}

function star_row(int $rating): string
{
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="star' . ($i <= $rating ? '' : ' star--off') . '">★</span>';
    }
    return $out;
}
