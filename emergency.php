<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = '24/7 Emergency & ICU';
$pageDescription = '24-hour emergency treatment at Sarada Nursing Home, Kandukur — accidents, chest pain, breathlessness, snake bite, scorpion sting and sudden illness, with ICU care.';
$activeNav       = 'emergency';
$bodyClass       = 'page-emergency';

require __DIR__ . '/includes/header.php';
?>

<?php
$emArt = asset_url('assets/img/hero/emergency.svg');
?>
<section class="page-hero page-hero-red has-art" style="--hero-art:url('<?= e($emArt) ?>')">
  <div class="wrap">
    <p class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep" aria-hidden="true">/</span>
      <span>Emergency</span>
    </p>
    <h1>24/7 Emergency Care</h1>
    <p>
      Our emergency department is open every hour of every day, including Sundays
      and festival days.
    </p>
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
      <p>
        <strong>Do not book a token for an emergency.</strong>
        Online booking is only for routine outpatient consultations. In an
        emergency, come straight to the hospital or call us on the way.
      </p>
    </div>

    <div class="section-head">
      <span class="eyebrow">Come in immediately</span>
      <h2>When to treat it as an emergency</h2>
      <p class="lede">
        If any of the following is happening, do not wait for the morning
        consultation. Bring the patient in at once.
      </p>
    </div>

    <div class="grid grid-3">
      <div class="card">
        <span class="card-icon red"><?= icon('droplet') ?></span>
        <h3>Snake Bite &amp; Scorpion Sting</h3>
        <p>Bring the patient in immediately. Keep them still and calm, and do not cut, suck or tie the wound tightly.</p>
      </div>
      <div class="card">
        <span class="card-icon red"><?= icon('heart') ?></span>
        <h3>Chest Pain</h3>
        <p>Sudden chest pain, pain spreading to the arm or jaw, or heavy sweating with it.</p>
      </div>
      <div class="card">
        <span class="card-icon red"><?= icon('icu') ?></span>
        <h3>Breathlessness</h3>
        <p>Serious difficulty breathing, a severe asthma attack, or the lips turning blue.</p>
      </div>
      <div class="card">
        <span class="card-icon red"><?= icon('alert') ?></span>
        <h3>Paralysis or Stroke</h3>
        <p>Sudden weakness on one side, a drooping face, slurred speech or loss of consciousness.</p>
      </div>
      <div class="card">
        <span class="card-icon red"><?= icon('emergency') ?></span>
        <h3>High Fever &amp; Fits</h3>
        <p>Very high fever, fits, severe dengue or malaria symptoms, or a child who has become drowsy.</p>
      </div>
      <div class="card">
        <span class="card-icon red"><?= icon('maternity') ?></span>
        <h3>Obstetric Emergency</h3>
        <p>Labour starting, bleeding in pregnancy, or severe abdominal pain.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Intensive Care</span>
        <h2>ICU support in the building</h2>
        <p>
          Patients who need continuous monitoring can be admitted to our ICU
          without being moved to another hospital. Oxygen and infusion support are
          available, and our in-house laboratory means urgent blood investigations
          are done on site.
        </p>
        <p class="mb-0">
          ICU and oxygen charges are published openly on our
          <a href="tariff.php">tariff page</a>.
        </p>
      </div>

      <div class="card">
        <span class="card-icon"><?= icon('location') ?></span>
        <h3>Finding us in a hurry</h3>
        <p>
          <?= e(HOSPITAL['address']['line1']) ?>,<br>
          <?= e(HOSPITAL['address']['line2']) ?>,<br>
          <?= e(HOSPITAL['address']['district']) ?>
        </p>
        <p class="mb-0">We are on Pamuru Road, directly opposite ICICI Bank, near Thyagarajaswamy Temple.</p>
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
