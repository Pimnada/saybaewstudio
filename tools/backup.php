<?php
/**
 * Backup: the database as a compressed dump, and a manifest of the photographs.
 *
 *   php tools/backup.php              write a new backup, prune old ones
 *   php tools/backup.php --list       show what is stored
 *   php tools/backup.php --verify     re-read the newest dump and check it
 *
 * Where backups go: ../backups/ — deliberately OUTSIDE the repository and
 * outside the web root. They are not committed to git, and here is why that is
 * not an oversight:
 *
 *   A dump holds every customer's name, phone number and email, plus the admin
 *   password hashes. Git keeps everything forever — deleting a file in a later
 *   commit does not remove it from history, and anyone who ever clones the repo
 *   gets the lot. The same goes for the photographs: they are pictures of other
 *   people's children, they are gigabytes, and git is a terrible place for both.
 *
 * So: code in git, data in backups, and the two never mix.
 *
 * CLI only. Run from cron — see docs/BACKUP.md.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('เครื่องมือนี้รันได้จากบรรทัดคำสั่งเท่านั้น');
}

require_once __DIR__ . '/../lib.php';

/** Keep a month of daily backups, then let them go. */
const KEEP_BACKUPS = 30;

$backupDir = dirname(APP_ROOT) . '/backups/' . basename(APP_ROOT);
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0700, true);
}
if (!is_dir($backupDir) || !is_writable($backupDir)) {
    fwrite(STDERR, "เขียนโฟลเดอร์สำรองไม่ได้: $backupDir\n");
    exit(1);
}

$arg = $argv[1] ?? '';

// ------------------------------------------------------------------ list ----

if ($arg === '--list') {
    $files = glob($backupDir . '/*.sql.gz') ?: [];
    rsort($files);
    if (!$files) {
        exit("ยังไม่มีไฟล์สำรองใน $backupDir\n");
    }
    echo "ไฟล์สำรองใน $backupDir\n\n";
    foreach ($files as $f) {
        printf("  %-42s %10s  %s\n", basename($f), fmt_bytes(filesize($f)),
            date('Y-m-d H:i', filemtime($f)));
    }
    printf("\nรวม %d ไฟล์ · %s\n", count($files), fmt_bytes(array_sum(array_map('filesize', $files))));
    exit;
}

// ---------------------------------------------------------------- verify ----

if ($arg === '--verify') {
    $files = glob($backupDir . '/*.sql.gz') ?: [];
    rsort($files);
    if (!$files) {
        fwrite(STDERR, "ไม่มีไฟล์สำรองให้ตรวจ\n");
        exit(1);
    }
    $newest = $files[0];
    echo "ตรวจ " . basename($newest) . "\n";

    // A dump that cannot be read back is not a backup, it is a file.
    $fh = gzopen($newest, 'rb');
    if (!$fh) {
        fwrite(STDERR, "  เปิดไฟล์ไม่ได้ — ไฟล์นี้ใช้กู้ไม่ได้\n");
        exit(1);
    }
    $inserts = 0;
    $tables  = 0;
    while (($line = gzgets($fh)) !== false) {
        if (str_starts_with($line, 'INSERT INTO')) { $inserts++; }
        if (str_starts_with($line, 'CREATE TABLE')) { $tables++; }
    }
    gzclose($fh);

    printf("  อ่านได้ · %d ตาราง · %d แถวข้อมูล · %s\n", $tables, $inserts, fmt_bytes(filesize($newest)));
    if ($tables === 0) {
        fwrite(STDERR, "  ไม่พบ CREATE TABLE เลย — ไฟล์นี้น่าจะเสีย\n");
        exit(1);
    }
    echo "  ผ่าน\n";
    exit;
}

// ----------------------------------------------------------------- dump -----

$stamp = date('Y-m-d_His');
$dest  = $backupDir . '/db_' . $stamp . '.sql.gz';

echo "สำรองฐานข้อมูล...\n";

$pdo    = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

$tables = [];
$sql = $driver === 'sqlite'
    ? "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
    : 'SHOW TABLES';
foreach ($pdo->query($sql) as $row) {
    $tables[] = array_values($row)[0];
}
sort($tables);

$gz = gzopen($dest, 'wb9');
if (!$gz) {
    fwrite(STDERR, "สร้างไฟล์สำรองไม่ได้\n");
    exit(1);
}

gzwrite($gz, "-- saybaewstudio backup\n");
gzwrite($gz, '-- ' . date('Y-m-d H:i:s') . " (" . $driver . ")\n");
gzwrite($gz, "-- กู้คืน: gunzip < ไฟล์นี้ | mysql -u USER -p DBNAME\n\n");

$rowTotal = 0;

foreach ($tables as $table) {
    // Schema first, so a restore works even into an empty database.
    if ($driver === 'sqlite') {
        $st = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?");
        $st->execute([$table]);
        $create = (string) $st->fetchColumn();
    } else {
        $create = (string) $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    }
    gzwrite($gz, "DROP TABLE IF EXISTS `$table`;\n" . $create . ";\n");

    $rows = 0;
    // Stream the rows — an album table can hold hundreds of thousands of them
    // and pulling the lot into an array would blow the memory limit.
    $q = $pdo->query("SELECT * FROM `$table`");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $cols = implode(', ', array_map(static fn($c) => "`$c`", array_keys($row)));
        $vals = implode(', ', array_map(static function ($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote((string) $v);
        }, $row));
        gzwrite($gz, "INSERT INTO `$table` ($cols) VALUES ($vals);\n");
        $rows++;
    }
    gzwrite($gz, "\n");

    printf("  %-16s %s แถว\n", $table, number_format($rows));
    $rowTotal += $rows;
}

gzclose($gz);
@chmod($dest, 0600);

printf("\nเขียนแล้ว: %s (%s · %s แถว)\n", basename($dest), fmt_bytes(filesize($dest)), number_format($rowTotal));

// ------------------------------------------------------- photo manifest -----

// Not the photographs themselves — those are synced separately, because a
// month of daily copies of 100 GB of albums is not a backup strategy. What is
// recorded here is enough to tell, after a restore, exactly which files should
// exist and whether any are missing.
$manifest = $backupDir . '/files_' . $stamp . '.txt.gz';
$mz = gzopen($manifest, 'wb9');
$count = 0;
$bytes = 0;

if ($mz) {
    gzwrite($mz, "-- ไฟล์รูปทั้งหมด ณ " . date('Y-m-d H:i:s') . "\n");
    $base = upload_path();
    if (is_dir($base)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $rel = substr($file->getPathname(), strlen($base));
            gzwrite($mz, sprintf("%d\t%s\t%s\n", $file->getSize(), md5_file($file->getPathname()), $rel));
            $count++;
            $bytes += $file->getSize();
        }
    }
    gzclose($mz);
    @chmod($manifest, 0600);
    printf("รายการไฟล์รูป: %s (%s ไฟล์ · %s)\n", basename($manifest), number_format($count), fmt_bytes($bytes));
}

// ----------------------------------------------------------------- prune ----

foreach (['db_*.sql.gz', 'files_*.txt.gz'] as $pattern) {
    $old = glob($backupDir . '/' . $pattern) ?: [];
    rsort($old);
    foreach (array_slice($old, KEEP_BACKUPS) as $stale) {
        @unlink($stale);
        echo "ลบไฟล์เก่า: " . basename($stale) . "\n";
    }
}

echo "\nเสร็จ · เก็บไว้ที่ $backupDir\n";
echo "ตรวจว่าไฟล์ใช้กู้ได้จริง: php tools/backup.php --verify\n";
