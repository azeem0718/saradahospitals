<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$pageTitle       = 'Our Services';
$pageDescription = 'Medical services at Sarada Nursing Home, Kandukur — diabetes, blood pressure, heart and kidney problems, fevers, dengue, snake bite, plus complete obstetrics and gynaecology care.';
$activeNav       = 'services';

/**
 * Pair each department with the consultant who runs it, so the section can
 * finish on a booking link rather than a dead end. Matched on speciality
 * rather than row id, since reception can add doctors from the admin panel.
 */
$doctors = get_doctors();
$consultant = static function (string $needle) use ($doctors): ?array {
    foreach ($doctors as $doc) {
        if (stripos((string) $doc['speciality'], $needle) !== false) {
            return $doc;
        }
    }
    return null;
};

$departments = [
    [
        'eyebrow'  => text('services.medicine.eyebrow'),
        'title'    => text('services.medicine.title'),
        'tone'     => 'navy',
        'icon'     => 'stethoscope',
        'lede'     => text('services.medicine.lede'),
        'items'    => GENERAL_MEDICINE,
        'noun'     => text('services.medicine.noun'),
        'doctor'   => $consultant('General Medicine'),
    ],
    [
        'eyebrow'  => text('services.obg.eyebrow'),
        'title'    => text('services.obg.title'),
        'tone'     => 'green',
        'icon'     => 'maternity',
        'lede'     => text('services.obg.lede'),
        'items'    => OBG_SERVICES,
        'noun'     => text('services.obg.noun'),
        'doctor'   => $consultant('Obstetric'),
    ],
];

require __DIR__ . '/includes/header.php';
page_hero(
    text('services.hero.title'),
    text('services.hero.lede'),
    'Services',
    'services'
);
?>

<section class="section">
  <div class="wrap">
    <div class="dept-grid">
      <?php foreach ($departments as $dep): ?>
        <article class="dept dept-<?= e($dep['tone']) ?>">

          <header class="dept-head">
            <span class="card-icon <?= $dep['tone'] === 'navy' ? '' : e($dep['tone']) ?>">
              <?= icon($dep['icon']) ?>
            </span>
            <span class="eyebrow"><?= e($dep['eyebrow']) ?></span>
            <h2><?= e($dep['title']) ?></h2>
            <p class="dept-lede"><?= e($dep['lede']) ?></p>

            <?php if ($dep['doctor']): ?>
              <div class="dept-doc">
                <span class="dept-doc-portrait"><?= doctor_avatar('') ?></span>
                <span class="dept-doc-text">
                  <strong><?= e($dep['doctor']['name']) ?></strong>
                  <span><?= e($dep['doctor']['qualifications']) ?></span>
                </span>
              </div>
            <?php endif; ?>
          </header>

          <div class="dept-body">
            <p class="dept-count">
              <strong><?= count($dep['items']) ?></strong> <?= e($dep['noun']) ?>
            </p>
            <ul class="dept-list">
              <?php foreach ($dep['items'] as $item): ?>
                <li><span class="dept-mark" aria-hidden="true"></span><span><?= e($item) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>

          <?php if ($dep['doctor']): ?>
            <footer class="dept-foot">
              <a class="btn btn-primary btn-sm" href="book.php?doctor=<?= (int) $dep['doctor']['id'] ?>">
                <?= icon('ticket') ?>
                Book with <?= e(doctor_short_name($dep['doctor']['name'])) ?>
              </a>
              <span class="dept-foot-note">Morning and evening OP, every day</span>
            </footer>
          <?php endif; ?>

        </article>
      <?php endforeach; ?>
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
