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

$adminMenu = [
    'today'    => ['Today',     'index.php',    'list'],
    'bookings' => ['Bookings',  'bookings.php', 'calendar'],
    'new'      => ['New Token', 'new.php',      'plus'],
    'schedule' => ['Schedule',  'schedule.php', 'clock'],
    'leave'    => ['Leave',     'leave.php',    'close'],
    'analytics' => ['Analytics', 'analytics.php', 'icu'],
];
if ($user['role'] === 'admin') {
    $adminMenu['doctors']  = ['Doctors',  'doctors.php',  'stethoscope'];
    $adminMenu['images']   = ['Pictures', 'images.php',   'image'];
    $adminMenu['users']    = ['Staff',    'users.php',    'users'];
    $adminMenu['backup']   = ['Backups',  'backup.php',   'shield'];
    $adminMenu['settings'] = ['Settings', 'settings.php', 'settings'];
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

<nav class="admin-nav" aria-label="Admin">
  <div class="wrap">
    <?php foreach ($adminMenu as $key => [$label, $href, $ico]): ?>
      <a href="<?= e($href) ?>"<?= $adminNav === $key ? ' aria-current="page"' : '' ?>>
        <?= icon($ico) ?><?= e($label) ?>
      </a>
    <?php endforeach; ?>
    <a href="../index.php" target="_blank" rel="noopener"><?= icon('arrow-right') ?>View Site</a>
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
