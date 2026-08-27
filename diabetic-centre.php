<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Good Health Diabetic Centre';
$pageDescription = 'Good Health Diabetic Centre at Sarada Nursing Home, Kandukur — dedicated diabetes care led by Dr. Gundavarapu Venkatesh, MD, Diploma in Endocrinology & Diabetology.';
$activeNav       = 'diabetic';

require __DIR__ . '/includes/header.php';
page_hero(
    text('diabetic.hero.title'),
    text('diabetic.hero.lede'),
    'Diabetic Centre',
    'diabetes'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow"><?= e(text('diabetic.why.eyebrow')) ?></span>
        <h2><?= e(text('diabetic.why.title')) ?></h2>
        <?php $whyParas = text_paragraphs('diabetic.why.body'); ?>
        <?php foreach ($whyParas as $i => $para): ?>
          <p<?= $i === count($whyParas) - 1 ? ' class="mb-0"' : '' ?>><?= $para ?></p>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <span class="card-icon gold"><?= icon('award') ?></span>
        <h3><?= e(text('diabetic.training.title')) ?></h3>
        <?php $trainParas = text_paragraphs('diabetic.training.body'); ?>
        <?php foreach ($trainParas as $i => $para): ?>
          <p<?= $i === count($trainParas) - 1 ? ' class="mb-0"' : '' ?>><?= $para ?></p>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><?= e(text('diabetic.cards.eyebrow')) ?></span>
      <h2><?= e(text('diabetic.cards.title')) ?></h2>
    </div>
    <div class="grid grid-3">
      <?php foreach (list_shaped('diabetic.conditions') as $c): ?>
        <div class="card">
          <span class="card-icon<?= $c['tone'] !== '' ? ' ' . e($c['tone']) : '' ?>"><?= icon($c['icon']) ?></span>
          <h3><?= e($c['title']) ?></h3>
          <p><?= e($c['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap wrap-narrow">
    <div class="notice notice-success">
      <?= icon('discount') ?>
      <p>
        <strong>Above 60 years?</strong>
        Patients over 60 receive a <strong>20% discount on blood tests</strong> —
        useful when diabetes needs checking several times a year.
      </p>
    </div>
    <div class="notice notice-info mb-0">
      <?= icon('calendar') ?>
      <p>
        <strong>Free OP every Friday.</strong>
        Outpatient consultations are free of charge every Friday, so a routine
        diabetes review costs you nothing but your time.
      </p>
    </div>
  </div>
</section>

<?php cta_band('Get your sugar checked', 'Book a token with Dr. Venkatesh, or call us to ask about a diabetes review.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
