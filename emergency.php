<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = '24/7 Emergency & ICU';
$pageDescription = '24-hour emergency treatment at Sarada Nursing Home, Kandukur — accidents, chest pain, breathlessness, snake bite, scorpion sting and sudden illness, with ICU care.';

/* BREADCRUMB-SEO */
$breadcrumb      = [['24/7 Emergency & ICU', null]];
$pageType        = 'MedicalWebPage';
$activeNav       = 'emergency';
$bodyClass       = 'page-emergency';

require __DIR__ . '/includes/header.php';
?>

<?php
// This page keeps its own hero markup only for the call buttons; the artwork
// behind it follows the same photograph-over-drawing rule as every banner.
[$emClass, $emStyle] = hero_art_attrs('emergency', 'emergency');
?>
<section class="page-hero page-hero-red<?= $emClass ?>"<?= $emStyle ?>>
  <div class="wrap">
    <p class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep" aria-hidden="true">/</span>
      <span>Emergency</span>
    </p>
    <h1><?= e(text('emergency.hero.title')) ?></h1>
    <p><?= e(text('emergency.hero.lede')) ?></p>
    <div class="btn-row mt-2">
      <a class="btn btn-lg btn-on-red" href="tel:<?= e(HOSPITAL['mobile']) ?>">
        <?= icon('phone') ?> Call <?= e(HOSPITAL['mobile_display']) ?>
      </a>
      <a class="btn btn-lg btn-ghost-light" href="tel:<?= e(HOSPITAL['landline']) ?>">
        <?= icon('phone') ?> <?= e(HOSPITAL['landline_display']) ?>
      </a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="notice notice-emergency">
      <?= icon('alert') ?>
      <p><?= text_rich('emergency.notice') ?></p>
    </div>

    <div class="section-head">
      <span class="eyebrow"><?= e(text('emergency.when.eyebrow')) ?></span>
      <h2><?= e(text('emergency.when.title')) ?></h2>
      <p class="lede"><?= e(text('emergency.when.lede')) ?></p>
    </div>

    <div class="grid grid-3">
      <?php foreach (list_shaped('emergency.signs') as $c): ?>
        <div class="card">
          <span class="card-icon<?= $c['tone'] !== '' ? ' ' . e($c['tone']) : '' ?>"><?= icon($c['icon']) ?></span>
          <h3><?= e($c['title']) ?></h3>
          <p><?= e($c['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow"><?= e(text('emergency.icu.eyebrow')) ?></span>
        <h2><?= e(text('emergency.icu.title')) ?></h2>
        <?php $icuParas = text_paragraphs('emergency.icu.body'); ?>
        <?php foreach ($icuParas as $i => $para): ?>
          <p<?= $i === count($icuParas) - 1 ? ' class="mb-0"' : '' ?>><?= $para ?></p>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <span class="card-icon"><?= icon('location') ?></span>
        <h3><?= e(text('emergency.finding.title')) ?></h3>
        <p>
          <?= e(HOSPITAL['address']['line1']) ?>,<br>
          <?= e(HOSPITAL['address']['line2']) ?>,<br>
          <?= e(HOSPITAL['address']['district']) ?>
        </p>
        <p class="mb-0"><?= e(text('emergency.finding.body')) ?></p>
        <a class="btn btn-primary btn-block mt-2" href="<?= e(HOSPITAL['map']['link']) ?>" target="_blank" rel="noopener">
          <?= icon('location') ?> Open in Google Maps
        </a>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap"><?php map_block(); ?></div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
