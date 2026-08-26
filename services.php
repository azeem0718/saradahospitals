<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Our Services';
$pageDescription = 'Medical services at Sarada Nursing Home, Kandukur — diabetes, blood pressure, heart and kidney problems, fevers, dengue, snake bite, plus complete obstetrics and gynaecology care.';
$activeNav       = 'services';

require __DIR__ . '/includes/header.php';
page_hero(
    'Our Services',
    'General medicine and women\'s health, delivered by resident consultants with laboratory and ICU support on site.',
    'Services',
    'services'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">

      <div>
        <span class="card-icon"><?= icon('stethoscope') ?></span>
        <h2>General Medicine</h2>
        <p class="lede mb-2">
          Consultation, diagnosis and treatment for everyday illness and long-term
          conditions, under Dr. Gundavarapu Venkatesh.
        </p>
        <ul class="service-list">
          <?php foreach (GENERAL_MEDICINE as $s): ?>
            <li><?= icon('check') ?><span><?= e($s) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <span class="card-icon green"><?= icon('baby') ?></span>
        <h2>Obstetrics &amp; Gynaecology</h2>
        <p class="lede mb-2">
          Pregnancy, delivery and complete women's health care, under
          Dr. Maddipudi Brahmani.
        </p>
        <ul class="service-list">
          <?php foreach (OBG_SERVICES as $s): ?>
            <li><?= icon('check') ?><span><?= e($s) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Specialised Care</span>
      <h2>Focused programmes</h2>
    </div>
    <div class="grid grid-3">
      <a class="card card-link" href="diabetic-centre.php">
        <span class="card-icon gold"><?= icon('droplet') ?></span>
        <h3>Good Health Diabetic Centre</h3>
        <p>Diagnosis, control and long-term follow-up for diabetes, with dedicated endocrinology training behind it.</p>
        <span class="card-more">Diabetes care <?= icon('arrow-right') ?></span>
      </a>
      <a class="card card-link" href="maternity.php">
        <span class="card-icon green"><?= icon('maternity') ?></span>
        <h3>Maternity Care</h3>
        <p>Antenatal check-ups, maternity scans, normal and caesarean delivery, and high-risk pregnancy support.</p>
        <span class="card-more">Maternity care <?= icon('arrow-right') ?></span>
      </a>
      <a class="card card-link" href="emergency.php">
        <span class="card-icon red"><?= icon('emergency') ?></span>
        <h3>24/7 Emergency</h3>
        <p>Accidents, chest pain, breathlessness, snake bite, scorpion sting and sudden illness, at any hour.</p>
        <span class="card-more">Emergency care <?= icon('arrow-right') ?></span>
      </a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap wrap-narrow">
    <div class="notice notice-info mb-0">
      <?= icon('info') ?>
      <p>
        <strong>Not sure which doctor to see?</strong>
        Call <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>
        and our reception will guide you to the right consultation.
      </p>
    </div>
  </div>
</section>

<?php offers_strip(); ?>
<?php cta_band(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
