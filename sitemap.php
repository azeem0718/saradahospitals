<?php
/**
 * XML sitemap. Generated so pages never drift out of sync with a static file.
 * .htaccess maps /sitemap.xml to this script.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=utf-8');

$pages = [
    ['index.php',           '1.0', 'weekly'],
    ['book.php',            '0.9', 'daily'],
    ['services.php',        '0.8', 'monthly'],
    ['doctors.php',         '0.8', 'monthly'],
    ['diabetic-centre.php', '0.8', 'monthly'],
    ['maternity.php',       '0.8', 'monthly'],
    ['emergency.php',       '0.8', 'monthly'],
    ['facilities.php',      '0.7', 'monthly'],
    ['tariff.php',          '0.7', 'monthly'],
    ['about.php',           '0.6', 'monthly'],
    ['contact.php',         '0.7', 'monthly'],
    ['gallery.php',         '0.5', 'monthly'],
];

$today = date('Y-m-d');
$base  = rtrim(SITE_URL, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as [$path, $priority, $freq]): ?>
  <url>
    <loc><?= htmlspecialchars($base . '/' . $path, ENT_XML1) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq><?= $freq ?></changefreq>
    <priority><?= $priority ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
