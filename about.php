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
    'About Sarada Nursing Home',
    'A neighbourhood nursing home on Pamuru Road, Kandukur, caring for families across Prakasam district.',
    'About'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Who We Are</span>
        <h2>Your health is our responsibility</h2>
        <p>
          Sarada Nursing Home is a full-service nursing home in Kandukur, Prakasam
          district. We bring together General Medicine, Diabetology and Obstetrics
          &amp; Gynaecology in one place, so a family does not have to travel to a
          larger city for routine and urgent care.
        </p>
        <p>
          Our emergency department stays open twenty-four hours a day, every day of
          the year. Patients who need close monitoring can be admitted to our ICU,
          and our in-house laboratory means blood investigations are done on site
          rather than sent away and waited on.
        </p>
        <p>
          Alongside general practice, we run the <strong>Good Health Diabetic
          Centre</strong> for people living with diabetes — a condition that needs
          steady, long-term follow-up rather than one-off visits.
        </p>
        <p class="mb-0">
          We publish our consultation fees and room charges openly, because knowing
          the cost before you arrive is part of being treated with respect.
        </p>
      </div>

      <div>
        <div class="card">
          <span class="card-icon red"><?= icon('heart') ?></span>
          <h3>What guides us</h3>
          <ul class="service-list mt-2" style="margin-bottom:0">
            <li><?= icon('check') ?><span>Always open — emergencies do not keep office hours</span></li>
            <li><?= icon('check') ?><span>Clear, published pricing with no surprises</span></li>
            <li><?= icon('check') ?><span>Qualified consultants you can actually see</span></li>
            <li><?= icon('check') ?><span>Care close to home, in your own town</span></li>
            <li><?= icon('check') ?><span>Free OP every Friday, and senior discounts on tests</span></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Our Team</span>
      <h2>The doctors who will see you</h2>
    </div>
    <?php doctor_cards($doctors); ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">On Site</span>
      <h2>What we have here</h2>
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
