<?php
/**
 * Admin panel chrome. Pages set $adminTitle, $adminSubtitle and $adminNav
 * before including this.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/booking.php';

$user = $user ?? require_login();

$adminTitle    = $adminTitle    ?? 'Reception';
$adminSubtitle = $adminSubtitle ?? '';
$adminNav      = $adminNav      ?? '';

/*
 * The menu in sections rather than one row.
 *
 * Reception sees only the desk, which is six entries and fits. An admin sees
 * everything, which was thirteen and had begun to scroll sideways — the last
 * few items were reachable only by dragging a bar most people never realise is
 * draggable. Grouping puts a short row of sections on top and only that
 * section's entries beneath, so nothing is ever hidden off the edge.
 *
 * The sections are the three different jobs this panel does: running today's
 * clinic, saying what the website says, and administering the place. Somebody
 * on the desk all day never leaves the first.
 */
$adminSections = [
    'desk' => ['label' => 'Reception', 'icon' => 'list', 'items' => [
        'today'     => ['Today',     'index.php',     'list'],
        'bookings'  => ['Bookings',  'bookings.php',  'calendar'],
        'new'       => ['New Token', 'new.php',       'plus'],
        'schedule'  => ['Schedule',  'schedule.php',  'clock'],
        'leave'     => ['Leave',     'leave.php',     'close'],
        'analytics' => ['Analytics', 'analytics.php', 'icu'],
    ]],
];

if ($user['role'] === 'admin') {
    $adminSections['website'] = ['label' => 'Website', 'icon' => 'edit', 'items' => [
        'content'  => ['Overview',  'content.php',  'info'],
        'pages'    => ['Page Text', 'pages.php',    'edit'],
        'hospital' => ['Hospital',  'hospital.php', 'building'],
        'tariff'   => ['Tariff',    'tariff.php',   'discount'],
        'services' => ['Services',  'services.php', 'heart'],
        'doctors'  => ['Doctors',   'doctors.php',  'stethoscope'],
        'images'   => ['Pictures',  'images.php',   'image'],
    ]];
    $adminSections['system'] = ['label' => 'Admin', 'icon' => 'settings', 'items' => [
        'users'    => ['Staff',    'users.php',    'users'],
        'backup'   => ['Backups',  'backup.php',   'shield'],
        'settings' => ['Settings', 'settings.php', 'settings'],
    ]];
}

/* Which section the current page sits in. A page that does not name itself —
   the password screen, say — falls back to the first, so the bar still renders
   something sensible rather than an empty second row. */
$adminSection = array_key_first($adminSections);
foreach ($adminSections as $sectionKey => $section) {
    if (isset($section['items'][$adminNav])) {
        $adminSection = $sectionKey;
        break;
    }
}

$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($adminTitle) ?> | Sarada Nursing Home</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css', '../')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css', '../')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css', '../')) ?>">
<link rel="icon" href="../assets/img/logo/badge-48.png" type="image/png" sizes="48x48">
<link rel="apple-touch-icon" href="../assets/img/logo/apple-touch-icon.png">
</head>
<body class="admin">

<div class="admin-bar">
  <div class="wrap admin-bar-inner">
    <a class="brand" href="index.php">
      <?= logo_mark('brand-mark', '../') ?>
      <span class="brand-text">
        <span class="brand-name"><?= brand_name_html() ?></span>
        <span class="brand-tag">Reception Panel</span>
      </span>
    </a>
    <div class="admin-user">
      <span><strong><?= e($user['full_name']) ?></strong></span>
      <span class="role"><?= e($user['role']) ?></span>
      <a class="btn btn-sm btn-ghost-light" href="password.php" title="Change password"><?= icon('lock') ?></a>
      <a class="btn btn-sm btn-ghost-light" href="logout.php"><?= icon('logout') ?> Sign out</a>
    </div>
  </div>
</div>

<?php if (count($adminSections) > 1): ?>
<nav class="admin-sections" aria-label="Sections">
  <div class="wrap">
    <?php foreach ($adminSections as $key => $section): ?>
      <?php $first = $section['items'][array_key_first($section['items'])]; ?>
      <a href="<?= e($first[1]) ?>"<?= $adminSection === $key ? ' aria-current="true"' : '' ?>>
        <?= icon($section['icon']) ?><?= e($section['label']) ?>
      </a>
    <?php endforeach; ?>
    <a class="admin-sections-out" href="../index.php" target="_blank" rel="noopener">
      <?= icon('arrow-right') ?>View Site
    </a>
  </div>
</nav>
<?php endif; ?>

<nav class="admin-nav" aria-label="<?= e($adminSections[$adminSection]['label']) ?>">
  <div class="wrap">
    <?php foreach ($adminSections[$adminSection]['items'] as $key => [$label, $href, $ico]): ?>
      <a href="<?= e($href) ?>"<?= $adminNav === $key ? ' aria-current="page"' : '' ?>>
        <?= icon($ico) ?><?= e($label) ?>
      </a>
    <?php endforeach; ?>
    <?php if (count($adminSections) === 1): ?>
      <a href="../index.php" target="_blank" rel="noopener"><?= icon('arrow-right') ?>View Site</a>
    <?php endif; ?>
  </div>
</nav>

<main class="admin-main">
  <div class="wrap">

    <?php if ($flash): ?>
      <div class="notice notice-<?= e($flash['type'] === 'error' ? 'emergency' : $flash['type']) ?>">
        <?= icon($flash['type'] === 'error' ? 'alert' : 'check-circle') ?>
        <p><?= e($flash['message']) ?></p>
      </div>
    <?php endif; ?>

    <div class="admin-head">
      <div>
        <h1><?= e($adminTitle) ?></h1>
        <?php if ($adminSubtitle !== ''): ?><p><?= e($adminSubtitle) ?></p><?php endif; ?>
      </div>
      <?php if (!empty($adminActions)): ?><div class="btn-row"><?= $adminActions ?></div><?php endif; ?>
    </div>
