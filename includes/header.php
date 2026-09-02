<?php
/**
 * Shared page head, emergency bar, masthead and navigation.
 *
 * Pages set these before including:
 *   $pageTitle       — <title> without the hospital name
 *   $pageDescription — meta description
 *   $activeNav       — nav key to highlight
 *   $bodyClass       — optional extra body class
 *   $breadcrumb      — [[label, path], ...] trail; Home is added for you and
 *                      the last crumb passes null for its path
 *   $pageType        — schema.org type for this page, default 'WebPage'
 *   $pageSchema      — extra JSON-LD entities to add to the page's @graph
 *   $pageNoIndex     — true to keep the page out of search results
 *   $pageImage       — site-image slot for the social preview, e.g. 'hero-slide-1'
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/seo.php';

$pageTitle       = $pageTitle       ?? '';
$pageDescription = $pageDescription ?? 'Sarada Nursing Home, Kandukur — 24/7 emergency care, General Medicine, Diabetology and Obstetrics & Gynaecology, with ICU, modern laboratory and A/C rooms.';
$activeNav       = $activeNav       ?? '';
$breadcrumb      = $breadcrumb      ?? [];
$pageSchema      = $pageSchema      ?? [];
$pageNoIndex     = $pageNoIndex     ?? false;

/* The social preview. A photograph of the building persuades somebody to open
   a shared link; a logo on a square does not. Falls back to the badge only
   when the hospital has not uploaded that picture yet. */
$ogImage  = null;
$ogSlot   = $pageImage ?? 'hero-slide-1';
$ogRaw    = function_exists('site_image_url') ? site_image_url($ogSlot) : null;
if ($ogRaw !== null) {
    $ogImage = seo_url(ltrim(preg_replace('/\?.*$/', '', $ogRaw), '/'));
}
$ogIsPhoto = $ogImage !== null;
if (!$ogIsPhoto) {
    $ogImage = seo_url('assets/img/logo/badge-512.png');
}

$fullTitle = $pageTitle !== ''
    ? $pageTitle . ' | ' . HOSPITAL['name']
    : HOSPITAL['name'] . ' — ' . HOSPITAL['tagline'] . ', Kandukur';

// label, href, and the icon the mobile menu shows beside each row
$navItems = [
    'home'      => ['Home',      'index.php',           'home'],
    'about'     => ['About',     'about.php',           'building'],
    'doctors'   => ['Doctors',   'doctors.php',         'users'],
    'services'  => ['Services',  'services.php',        'stethoscope'],
    'diabetic'  => ['Diabetes',  'diabetic-centre.php', 'droplet'],
    'maternity' => ['Maternity', 'maternity.php',       'maternity'],
    'emergency' => ['Emergency', 'emergency.php',       'emergency'],
    'tariff'    => ['Tariff',    'tariff.php',          'list'],
    'contact'   => ['Contact',   'contact.php',         'phone'],
];

$announcement = setting('announcement', '');
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="google-site-verification" content="fZubF43kpPLs3F0yc5u8Tk3wjcpfhS9bH8VDxB1hZhc" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#0b2545">
<link rel="canonical" href="<?= e(canonical_url()) ?>">

<?php if ($pageNoIndex): ?>
<!-- A page that exists for one patient and nobody else. It stays crawlable on
     purpose: robots.txt can only stop a crawler reading the page, and a URL it
     is forbidden to read can still be listed from a link elsewhere. Letting it
     in and saying noindex is the only instruction that actually removes it. -->
<meta name="robots" content="noindex, follow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<?php endif; ?>

<meta property="og:type" content="website">
<meta property="og:url" content="<?= e(canonical_url()) ?>">
<meta property="og:site_name" content="<?= e(HOSPITAL['name']) ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e($pageDescription) ?>">
<meta property="og:locale" content="en_IN">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:image:alt" content="<?= e($ogIsPhoto ? site_image_alt($ogSlot) : HOSPITAL['name']) ?>">
<meta name="twitter:card" content="<?= $ogIsPhoto ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($fullTitle) ?>">
<meta name="twitter:description" content="<?= e($pageDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">

<!-- Where this hospital physically is. The same fact is in the structured data
     below; these are the older tags some local directories still read. -->
<meta name="geo.region" content="IN-AP">
<meta name="geo.placename" content="Kandukur, Prakasam District">
<meta name="geo.position" content="<?= e(HOSPITAL['map']['lat'] . ';' . HOSPITAL['map']['lng']) ?>">
<meta name="ICBM" content="<?= e(HOSPITAL['map']['lat'] . ', ' . HOSPITAL['map']['lng']) ?>">

<!-- No ?v= on these: the stylesheet references the bare filename, and a
     preload under a different URL is a second download of the same font. -->
<link rel="preload" href="assets/fonts/inter-normal-400-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/newsreader-normal-400-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<link rel="icon" href="assets/img/logo/badge-48.png" type="image/png" sizes="48x48">
<link rel="icon" href="assets/img/logo/badge-192.png" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="assets/img/logo/apple-touch-icon.png">

<!-- One connected description of this page rather than the same clinic block
     repeated on all twelve. See includes/seo.php for what goes in and why. -->
<script type="application/ld+json">
<?= seo_json_ld(canonical_url(), $fullTitle, $pageDescription) ?>
</script>
</head>
<body<?= isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>

<a class="skip-link" href="#main">Skip to main content</a>

<div class="emergency-bar">
  <div class="wrap">
    <span class="emergency-bar-label">
      <span class="pulse-dot" aria-hidden="true"></span>
      Open 24 Hours
    </span>
    <a href="tel:<?= e(HOSPITAL['mobile']) ?>">
      <?= icon('phone') ?> Emergency <?= e(HOSPITAL['mobile_display']) ?>
    </a>
    <span class="sep" aria-hidden="true">&middot;</span>
    <a class="secondary" href="tel:<?= e(HOSPITAL['landline']) ?>">
      Reception <?= e(HOSPITAL['landline_display']) ?>
    </a>
  </div>
</div>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="index.php">
      <?= logo_mark() ?>
      <span class="brand-text">
        <span class="brand-name"><?= brand_name_html() ?></span>
        <span class="brand-tag"><?= e(HOSPITAL['tagline']) ?></span>
      </span>
    </a>

    <button class="nav-toggle" type="button"
            aria-expanded="false" aria-controls="primary-nav" aria-label="Menu">
      <?= icon('menu', 'icon-open') ?><?= icon('close', 'icon-close') ?>
    </button>

    <nav class="nav" id="primary-nav" aria-label="Primary">
      <?php foreach ($navItems as $key => [$label, $href, $ico]): ?>
        <a href="<?= e($href) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>>
          <span class="nav-ico" aria-hidden="true"><?= icon($ico) ?></span><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

  </div>
</header>

<?php if (trim($announcement) !== ''): ?>
<div class="wrap" style="padding-top:1.25rem">
  <div class="notice notice-warn mb-0">
    <?= icon('info') ?>
    <p><?= e($announcement) ?></p>
  </div>
</div>
<?php endif; ?>

<main id="main">
