<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$pageTitle       = 'About Us';
$pageDescription = 'About Sarada Nursing Home, Kandukur — a 24-hour nursing home offering General Medicine, Diabetology and Obstetrics & Gynaecology with ICU and laboratory facilities.';
$activeNav       = 'about';

$doctors = get_doctors();

require __DIR__ . '/includes/header.php';
page_hero(
    text('about.hero.title'),
    text('about.hero.lede'),
    'About',
    'about'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow"><?= e(text('about.who.eyebrow')) ?></span>
        <h2><?= e(text('about.who.title')) ?></h2>
        <?php $paras = text_paragraphs('about.who.body'); ?>
        <?php foreach ($paras as $i => $para): ?>
          <p<?= $i === count($paras) - 1 ? ' class="mb-0"' : '' ?>><?= $para ?></p>
        <?php endforeach; ?>
      </div>

      <div>
        <div class="card">
          <span class="card-icon red"><?= icon('heart') ?></span>
          <h3><?= e(text('about.values.title')) ?></h3>
          <ul class="service-list mt-2" style="margin-bottom:0">
            <?php foreach (list_shaped('about.values') as $value): ?>
              <li><?= icon('check') ?><span><?= e($value) ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><?= e(text('about.team.eyebrow')) ?></span>
      <h2><?= e(text('about.team.title')) ?></h2>
    </div>
    <?php doctor_cards($doctors); ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><?= e(text('about.onsite.eyebrow')) ?></span>
      <h2><?= e(text('about.onsite.title')) ?></h2>
    </div>
    <div class="grid grid-3">
      <?php foreach (FACILITIES as $f): ?>
        <div class="card">
          <span class="card-icon"><?= icon($f['icon']) ?></span>
          <h3><?= e($f['title']) ?></h3>
          <p><?= e($f['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php offers_strip(); ?>
<?php cta_band(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
