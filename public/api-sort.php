<?php
/**
 * Saves a new drag-and-drop order. The table name arrives from the browser, so
 * it is matched against a whitelist rather than interpolated as given.
 */

require_once __DIR__ . '/../auth.php';

require_admin();

if (!is_post()) {
    json_out(['ok' => false, 'error' => 'ต้องส่งด้วยวิธี POST'], 405);
}
csrf_check();

const SORTABLE_TABLES = [
    'reviews', 'faqs', 'banners', 'menus', 'services',
    'categories', 'photos', 'videos', 'folders', 'autoreplies',
];

$table = (string) ($_GET['table'] ?? $_POST['table'] ?? '');
if (!in_array($table, SORTABLE_TABLES, true)) {
    json_out(['ok' => false, 'error' => 'ตารางนี้จัดลำดับไม่ได้'], 400);
}

$ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
if (!$ids) {
    json_out(['ok' => false, 'error' => 'ไม่ได้ส่งลำดับมา'], 400);
}

$st = db()->prepare("UPDATE `$table` SET sort_order = ? WHERE id = ?");
foreach ($ids as $i => $id) {
    $st->execute([$i + 1, $id]);
}

json_out(['ok' => true, 'count' => count($ids)]);
