<?php
/** XML sitemap, generated from whatever is published right now. */

require_once __DIR__ . '/../lib.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['index.php',    '1.0', 'weekly'],
    ['albums.php',   '0.9', 'weekly'],
    ['services.php', '0.8', 'monthly'],
    ['about.php',    '0.7', 'monthly'],
    ['reviews.php',  '0.7', 'monthly'],
    ['blog.php',     '0.7', 'weekly'],
    ['contact.php',  '0.8', 'monthly'],
];

foreach (db()->query("SELECT slug, updated_at FROM albums WHERE status = 'published' AND access = 'public'") as $r) {
    $urls[] = ['album.php?slug=' . urlencode($r['slug']), '0.8', 'monthly', $r['updated_at']];
}
foreach (db()->query("SELECT slug, updated_at FROM articles WHERE status = 'published'") as $r) {
    $urls[] = ['article.php?slug=' . urlencode($r['slug']), '0.6', 'monthly', $r['updated_at']];
}
foreach (db()->query("SELECT slug, updated_at FROM pages WHERE status = 'published'") as $r) {
    $urls[] = ['page.php?slug=' . urlencode($r['slug']), '0.4', 'yearly', $r['updated_at']];
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . e(url($u[0])) . "</loc>\n";
    if (!empty($u[3])) {
        echo '    <lastmod>' . e(date('Y-m-d', strtotime($u[3]))) . "</lastmod>\n";
    }
    echo '    <changefreq>' . e($u[2]) . "</changefreq>\n";
    echo '    <priority>' . e($u[1]) . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>', "\n";
