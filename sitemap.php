<?php
/**
 * XML sitemap. Generated rather than kept as a file so the list of pages
 * cannot drift away from the pages that exist. .htaccess maps /sitemap.xml
 * here.
 *
 * Two rules govern what goes in.
 *
 * Only pages that may be indexed are listed. A sitemap saying "please index
 * this" about a page whose own head says noindex is a contradiction a crawler
 * has to resolve, and it resolves it by trusting the page and trusting the
 * sitemap a little less. So the patient-specific pages — a token slip, a
 * queue position, a cancellation — are absent here exactly as they are absent
 * from the index.
 *
 * And lastmod tells the truth. It used to say date('Y-m-d'): every page,
 * modified today, every day, for ever. Google's guidance is explicit that a
 * lastmod it cannot believe is a lastmod it ignores, and a site that claims
 * twelve pages changed daily is a site making that claim about all of them.
 * The real modification time of the file that renders each page is a fact, and
 * a page whose content lives in the database takes the later of the template
 * and the last content edit.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/booking.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/site-images.php';

header('Content-Type: application/xml; charset=utf-8');

/* Priority is the least useful field in the format — Google has said for years
   that it ignores it — but changefreq and priority cost nothing and other
   crawlers still read them. The ordering below is the honest one: what a
   patient most needs first. */
$pages = [
    ['index.php',           '1.0', 'weekly'],
    ['book.php',            '0.9', 'daily'],
    ['emergency.php',       '0.9', 'monthly'],
    ['doctors.php',         '0.8', 'monthly'],
    ['services.php',        '0.8', 'monthly'],
    ['diabetic-centre.php', '0.8', 'monthly'],
    ['maternity.php',       '0.8', 'monthly'],
    ['contact.php',         '0.8', 'monthly'],
    ['tariff.php',          '0.7', 'monthly'],
    ['facilities.php',      '0.7', 'monthly'],
    ['about.php',           '0.6', 'monthly'],
    ['gallery.php',         '0.5', 'monthly'],
    ['credits.php',         '0.3', 'yearly'],
];

/* Each doctor has a profile page of their own, and for a hospital this size
   those are among the most searched pages on the site — people look for the
   doctor by name far more often than for the clinic. */
foreach (get_doctors() as $doc) {
    $pages[] = ['doctor.php?slug=' . rawurlencode($doc['slug']), '0.8', 'monthly'];
}

/**
 * When this page last actually changed: the template's own mtime, or the last
 * content edit if the hospital has edited text more recently than the file was
 * deployed. Falls back to the deployment time rather than inventing today.
 */
$contentTouched = null;
try {
    $row = db()->query('SELECT MAX(updated_at) FROM content')->fetchColumn();
    if ($row) {
        $contentTouched = strtotime((string) $row) ?: null;
    }
} catch (Throwable $e) {
    // No content table yet, or no edits. The file mtime alone is then correct.
}

function sitemap_lastmod(string $path, ?int $contentTouched): string
{
    $file = __DIR__ . '/' . preg_replace('/\?.*$/', '', $path);
    $mtime = is_file($file) ? (int) filemtime($file) : time();
    if ($contentTouched !== null && $contentTouched > $mtime) {
        $mtime = $contentTouched;
    }
    return date('Y-m-d', $mtime);
}

$base = rtrim(SITE_URL, '/');

/* The pictures, declared against the page they appear on. An image sitemap is
   how a photograph of this building becomes findable as a photograph of this
   building, which for a hospital being chosen off a search page matters more
   than it sounds. */
$homeImages = [];
for ($i = 1; $i <= 5; $i++) {
    $url = site_image_url('hero-slide-' . $i);
    if ($url !== null) {
        $homeImages[] = $base . '/' . ltrim(preg_replace('/\?.*$/', '', $url), '/');
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($pages as [$path, $priority, $freq]): ?>
  <url>
    <loc><?= htmlspecialchars($base . '/' . $path, ENT_XML1) ?></loc>
    <lastmod><?= sitemap_lastmod($path, $contentTouched) ?></lastmod>
    <changefreq><?= $freq ?></changefreq>
    <priority><?= $priority ?></priority>
<?php if ($path === 'index.php'): foreach ($homeImages as $img): ?>
    <image:image><image:loc><?= htmlspecialchars($img, ENT_XML1) ?></image:loc></image:image>
<?php endforeach; endif; ?>
  </url>
<?php endforeach; ?>
</urlset>
