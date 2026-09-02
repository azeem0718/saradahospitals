<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Maternity & Women\'s Health';
$pageDescription = 'Maternity care at Sarada Nursing Home, Kandukur — normal delivery, caesarean, high-risk pregnancy, maternity scans, PCOD, laparoscopic surgery and infertility treatment.';

/* BREADCRUMB-SEO */
$breadcrumb      = [["Maternity & Women's Health", null]];
$pageType        = 'MedicalWebPage';
$activeNav       = 'maternity';

require __DIR__ . '/includes/header.php';
page_hero(
    text('maternity.hero.title'),
    text('maternity.hero.lede'),
    'Maternity',
    'maternity'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow"><?= e(text('maternity.delivery.eyebrow')) ?></span>
        <h2><?= e(text('maternity.delivery.title')) ?></h2>
        <?php $dParas = text_paragraphs('maternity.delivery.body'); ?>
        <?php foreach ($dParas as $i => $para): ?>
          <p<?= $i === count($dParas) - 1 ? ' class="mb-0"' : '' ?>><?= $para ?></p>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <span class="card-icon green"><?= icon('maternity') ?></span>
        <h3><?= e(text('maternity.journey.title')) ?></h3>
        <ul class="service-list mt-2" style="margin-bottom:0">
          <?php foreach (list_shaped('maternity.journey') as $item): ?>
            <li><?= icon('check') ?><span><?= e($item) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><?= e(text('maternity.gynae.eyebrow')) ?></span>
      <h2><?= e(text('maternity.gynae.title')) ?></h2>
      <p class="lede"><?= e(text('maternity.gynae.lede')) ?></p>
    </div>

    <div class="grid grid-3">
      <?php foreach (list_shaped('maternity.gynae') as $c): ?>
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
    <div class="notice notice-emergency mb-0">
      <?= icon('alert') ?>
      <p><?= text_rich('maternity.urgent') ?></p>
    </div>
  </div>
</section>

<?php cta_band('Book with Dr. Brahmani', 'Antenatal check-ups and gynaecology consultations can be booked online.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
