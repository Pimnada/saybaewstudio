<?php
/**
 * Install designed SVG icons into inc/icons.php.
 *
 *   php tools/install-icons.php DIR [DIR ...]           write the changes
 *   php tools/install-icons.php --dry-run DIR [DIR ...] show what would change
 *
 * Files are matched to icons by filename: camera.svg replaces the 'camera'
 * entry. A file whose name is not already an icon is reported and skipped —
 * icon names are referenced all over the site, so inventing new ones here would
 * silently do nothing, and renaming an existing one would turn it into the
 * fallback dot on every page that uses it.
 *
 * What gets cleaned up on the way in, and why each one matters:
 *
 *   style="color:#xxxxxx"  Design tools add this so the icon previews dark on a
 *                          white artboard. Left in, it pins the colour: every
 *                          currentColor inside resolves to that value, the icon
 *                          stops inheriting from its surroundings, and it turns
 *                          invisible in dark mode.
 *   width= / height=       The size belongs to the call site. icon('camera', '', 18)
 *                          must be able to ask for 18px.
 *   xmlns=                 Only needed on a standalone file. Inline in HTML it is
 *                          noise repeated on every icon on every page.
 *   repeated stroke attrs  The wrapper already carries fill/stroke/width/linecap,
 *                          so children that only restate the same values are
 *                          trimmed. Anything that differs — a filled dot inside
 *                          an outlined icon — is kept exactly as drawn.
 *
 * CLI only.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('เครื่องมือนี้รันได้จากบรรทัดคำสั่งเท่านั้น');
}

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../inc/icons.php';

$args   = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$dirs   = array_values(array_filter($args, static fn($a) => $a !== '--dry-run'));

if (!$dirs) {
    exit("ใช้: php tools/install-icons.php [--dry-run] โฟลเดอร์ [โฟลเดอร์ ...]\n");
}

/** The attribute values the <svg> wrapper already supplies for outline icons. */
const WRAPPER_DEFAULTS = [
    'fill'             => 'none',
    'stroke'           => 'currentColor',
    'stroke-width'     => '1.8',
    'stroke-linecap'   => 'round',
    'stroke-linejoin'  => 'round',
];

/**
 * Pull the drawing out of a standalone SVG file and normalise it.
 * Returns [body, mode] where mode is 'stroke' or 'fill'.
 */
function extract_icon(string $svg): array
{
    // Everything between the outer <svg ...> and </svg>.
    if (!preg_match('#<svg\b[^>]*>(.*)</svg>#is', $svg, $m)) {
        throw new RuntimeException('ไม่พบแท็ก <svg>');
    }
    $body = trim($m[1]);

    // Strip comments and any stray XML declaration that survived.
    $body = preg_replace('/<!--.*?-->/s', '', $body);
    $body = preg_replace('/<\?xml.*?\?>/s', '', $body);

    // Drop attributes that must not travel with the drawing.
    //
    // width and height are NOT in this list on purpose. They belong to the <svg>
    // wrapper, which has already been discarded above — but <rect> uses the same
    // two attribute names to define its shape. Stripping them globally turned
    // every rectangle into a zero-width nothing and quietly gutted camera,
    // video-frames, mobile and image-sharp.
    $body = preg_replace('/\s(?:xmlns(?::\w+)?|class|id)="[^"]*"/i', '', $body);
    $body = preg_replace('/\sstyle="[^"]*"/i', '', $body);

    // Decide the mode BEFORE trimming. Trimming removes the very attributes the
    // decision is based on: stroke="currentColor" matches a wrapper default and
    // disappears, so counting afterwards saw zero strokes and called every
    // outlined icon with a single filled dot in it — camera, chat-fast, mobile —
    // a solid icon.
    $filled  = preg_match_all('/fill="currentColor"/i', $body);
    $strokes = preg_match_all('/stroke="currentColor"/i', $body);
    $mode    = ($strokes === 0 && $filled > 0) ? 'fill' : 'stroke';

    // Trim child attributes that only restate what the wrapper already sets.
    foreach (WRAPPER_DEFAULTS as $attr => $value) {
        $body = str_replace(' ' . $attr . '="' . $value . '"', '', $body);
    }

    // With the fill wrapper, per-shape fill="currentColor" is redundant too.
    if ($mode === 'fill') {
        $body = str_replace(' fill="currentColor"', '', $body);
        $body = str_replace(' stroke="none"', '', $body);
    }

    $body = preg_replace('/>\s+</', '><', $body);
    $body = preg_replace('/\s{2,}/', ' ', $body);

    return [trim($body), $mode];
}

$known    = icon_paths();
$iconFile = __DIR__ . '/../inc/icons.php';
$source   = file_get_contents($iconFile);

$applied = [];
$skipped = [];
$failed  = [];

foreach ($dirs as $dir) {
    $dir = rtrim($dir, '/');
    if (!is_dir($dir)) {
        fwrite(STDERR, "ไม่พบโฟลเดอร์: $dir\n");
        continue;
    }

    echo "อ่านจาก $dir\n";

    foreach (glob($dir . '/*.svg') ?: [] as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);

        if (!isset($known[$name])) {
            $skipped[] = $name;
            continue;
        }

        try {
            [$body, $mode] = extract_icon((string) file_get_contents($file));
        } catch (Throwable $e) {
            $failed[$name] = $e->getMessage();
            continue;
        }

        if ($body === '') {
            $failed[$name] = 'ไฟล์ว่างหลังทำความสะอาด';
            continue;
        }

        // Replace this one entry, leaving the rest of the file untouched.
        $pattern = "/('" . preg_quote($name, '/') . "'\s*=>\s*\[)'(?:\\\\.|[^'\\\\])*'(\s*,\s*)'(?:stroke|fill)'(\])/";
        $replacement = '${1}' . "'" . str_replace(['\\', '$'], ['\\\\', '\\$'], addcslashes($body, "'")) . "'"
                     . '${2}' . "'" . $mode . "'" . '${3}';

        $updated = preg_replace($pattern, $replacement, $source, 1, $count);
        if ($count !== 1 || $updated === null) {
            $failed[$name] = 'หาตำแหน่งในไฟล์ icons.php ไม่เจอ';
            continue;
        }

        $source = $updated;
        $applied[$name] = $mode;
        printf("  %-20s %s · %d ตัวอักษร\n", $name, $mode, strlen($body));
    }
}

echo "\n";

if ($failed) {
    echo "ติดตั้งไม่ได้:\n";
    foreach ($failed as $name => $why) {
        printf("  %-20s %s\n", $name, $why);
    }
    echo "\n";
}

if ($skipped) {
    sort($skipped);
    echo "ข้ามเพราะไม่มีชื่อนี้ในระบบ (" . count($skipped) . "): " . implode(', ', $skipped) . "\n";
    echo "ถ้าตั้งใจจะเพิ่มไอคอนใหม่จริง ต้องเพิ่มชื่อเข้า icon_paths() ก่อน\n\n";
}

if (!$applied) {
    exit("ไม่มีอะไรเปลี่ยน\n");
}

if ($dryRun) {
    printf("(dry run) จะเปลี่ยน %d ไอคอน — ยังไม่ได้เขียนไฟล์\n", count($applied));
    exit;
}

// Never write a file that will not parse.
$tmp = tempnam(sys_get_temp_dir(), 'icons');
file_put_contents($tmp, $source);
exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
if ($code !== 0) {
    @unlink($tmp);
    fwrite(STDERR, "หยุด: ผลลัพธ์ไม่ผ่าน syntax check\n" . implode("\n", $out) . "\n");
    exit(1);
}
@unlink($tmp);

file_put_contents($iconFile, $source);
printf("เขียน inc/icons.php แล้ว — เปลี่ยน %d ไอคอน\n", count($applied));
echo "ดูผลได้ที่ tools/icon-sheet.php\n";
