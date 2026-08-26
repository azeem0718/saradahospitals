<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Good Health Diabetic Centre';
$pageDescription = 'Good Health Diabetic Centre at Sarada Nursing Home, Kandukur — dedicated diabetes care led by Dr. Gundavarapu Venkatesh, MD, Diploma in Endocrinology & Diabetology.';
$activeNav       = 'diabetic';

require __DIR__ . '/includes/header.php';
page_hero(
    'Good Health Diabetic Centre',
    'Hope for Better Life — dedicated diabetes care at Sarada Nursing Home.',
    'Diabetic Centre'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Why a dedicated centre</span>
        <h2>Diabetes needs follow-up, not just a prescription</h2>
        <p>
          Diabetes is not treated in a single visit. Sugar levels shift with diet,
          weight, illness, stress and age, and the medicines that worked last year
          may not be right this year. Left unchecked, it quietly damages the eyes,
          kidneys, nerves and heart.
        </p>
        <p>
          The Good Health Diabetic Centre exists so that patients in and around
          Kandukur have somewhere close by to be reviewed regularly, rather than
          only when something goes wrong.
        </p>
        <p class="mb-0">
          Care here is led by <strong>Dr. Gundavarapu Venkatesh</strong>, who holds
          an MD in General Medicine from SRM University, Chennai, and a
          <strong>Diploma in Endocrinology &amp; Diabetology</strong>.
        </p>
      </div>

      <div class="card">
        <span class="card-icon gold"><?= icon('award') ?></span>
        <h3>Trained in current diabetes practice</h3>
        <p>
          Dr. Venkatesh has completed <em>Changing the Paradigm in Type 2 Diabetes
          Mellitus Management</em>, a multidisciplinary diabetes self-study programme
          developed by Medical Trends and based on official resources of the
          <strong>American Diabetes Association (ADA)</strong>.
        </p>
        <p class="mb-0">
          It means the treatment you receive follows current international guidance,
          not habit.
        </p>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">What we look after</span>
      <h2>Diabetes and related conditions</h2>
    </div>
    <div class="grid grid-3">
      <div class="card">
        <span class="card-icon gold"><?= icon('droplet') ?></span>
        <h3>Diabetes Management</h3>
        <p>Diagnosis, medication review, sugar control and ongoing follow-up for type 2 diabetes.</p>
      </div>
      <div class="card">
        <span class="card-icon red"><?= icon('heart') ?></span>
        <h3>Blood Pressure</h3>
        <p>Hypertension so often travels with diabetes that the two are managed together here.</p>
      </div>
      <div class="card">
        <span class="card-icon"><?= icon('shield') ?></span>
        <h3>Thyroid Disorders</h3>
        <p>Assessment and treatment of thyroid problems, which frequently overlap with diabetes.</p>
      </div>
      <div class="card">
        <span class="card-icon"><?= icon('lab') ?></span>
        <h3>Laboratory Investigations</h3>
        <p>Blood sugar and related tests done in our own laboratory, so results come back quickly.</p>
      </div>
      <div class="card">
        <span class="card-icon"><?= icon('scan') ?></span>
        <h3>2D Echo Scan</h3>
        <p>Cardiac echo scanning on site, since long-standing diabetes affects the heart.</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('stethoscope') ?></span>
        <h3>Complication Screening</h3>
        <p>Review of kidney, nerve and heart problems that can develop alongside diabetes.</p>
      </div>
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
