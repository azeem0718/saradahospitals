<?php
/**
 * Shared page head, emergency bar, masthead and navigation.
 *
 * Pages set these before including:
 *   $pageTitle       — <title> without the hospital name
 *   $pageDescription — meta description
 *   $activeNav       — nav key to highlight
 *   $bodyClass       — optional extra body class
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';

$pageTitle       = $pageTitle       ?? '';
$pageDescription = $pageDescription ?? 'Sarada Nursing Home, Kandukur — 24/7 emergency care, General Medicine, Diabetology and Obstetrics & Gynaecology, with ICU, modern laboratory and A/C rooms.';
$activeNav       = $activeNav       ?? '';

$fullTitle = $pageTitle !== ''
    ? $pageTitle . ' | ' . HOSPITAL['name']
    : HOSPITAL['name'] . ' — ' . HOSPITAL['tagline'] . ', Kandukur';

$navItems = [
    'home'      => ['Home',           'index.php'],
    'about'     => ['About',          'about.php'],
    'doctors'   => ['Doctors',        'doctors.php'],
    'services'  => ['Services',       'services.php'],
    'diabetic'  => ['Diabetes',       'diabetic-centre.php'],
    'maternity' => ['Maternity',      'maternity.php'],
    'emergency' => ['Emergency',      'emergency.php'],
    'tariff'    => ['Tariff',         'tariff.php'],
    'contact'   => ['Contact',        'contact.php'],
];

$announcement = setting('announcement', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#0b2545">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(HOSPITAL['name']) ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e($pageDescription) ?>">
<meta property="og:locale" content="en_IN">

<link rel="preload" href="<?= e(asset('assets/fonts/inter-normal-400-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('assets/fonts/newsreader-normal-400-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'MedicalClinic',
    'name'     => HOSPITAL['name'],
    'slogan'   => HOSPITAL['tagline'],
    'url'      => SITE_URL,
    'telephone'=> [HOSPITAL['mobile'], HOSPITAL['landline']],
    'address'  => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => HOSPITAL['address']['line1'] . ', ' . HOSPITAL['address']['line2'],
        'addressLocality' => 'Kandukur',
        'addressRegion'   => 'Andhra Pradesh',
        'addressCountry'  => 'IN',
    ],
    'geo' => [
        '@type'     => 'GeoCoordinates',
        'latitude'  => HOSPITAL['map']['lat'],
        'longitude' => HOSPITAL['map']['lng'],
    ],
    'openingHoursSpecification' => [[
        '@type'     => 'OpeningHoursSpecification',
        'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
        'opens'     => '00:00',
        'closes'    => '23:59',
        'description' => '24/7 emergency services',
    ]],
    'availableService' => array_map(
        static fn(string $s): array => ['@type' => 'MedicalProcedure', 'name' => $s],
        array_merge(GENERAL_MEDICINE, OBG_SERVICES)
    ),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
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
        <span class="brand-name"><?= e(HOSPITAL['name']) ?></span>
        <span class="brand-tag"><?= e(HOSPITAL['tagline']) ?></span>
      </span>
    </a>

    <button class="nav-toggle" type="button"
            aria-expanded="false" aria-controls="primary-nav" aria-label="Menu">
      <?= icon('menu', 'icon-open') ?><?= icon('close', 'icon-close') ?>
    </button>

    <nav class="nav" id="primary-nav" aria-label="Primary">
      <?php foreach ($navItems as $key => [$label, $href]): ?>
        <a href="<?= e($href) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-cta">
      <a class="btn btn-emergency btn-sm" href="tel:<?= e(HOSPITAL['mobile']) ?>">
        <?= icon('phone') ?><span>Call</span>
      </a>
      <a class="btn btn-primary btn-sm" href="book.php">
        <?= icon('ticket') ?><span>Book Token</span>
      </a>
    </div>
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
