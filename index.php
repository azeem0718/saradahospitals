<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$pageTitle       = '';
$pageDescription = 'Sarada Nursing Home, Kandukur — 24/7 emergency care, General Medicine, Diabetology and Obstetrics & Gynaecology. ICU, modern laboratory, 2D Echo and A/C rooms. Book an OP token online.';
$activeNav       = 'home';

$doctors = get_doctors();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap">
    <div class="hero-grid">

      <div>
        <p class="hero-eyebrow">
          <span class="dot-wrap"><span class="pulse-dot" aria-hidden="true"></span></span>
          Emergency department open right now
        </p>

        <h1>Care your family can <em>reach</em>, at any hour.</h1>
        <p class="hero-tagline"><?= e(HOSPITAL['tagline']) ?></p>

        <p class="hero-lede">
          General Medicine, Diabetology and Obstetrics &amp; Gynaecology under one
          roof in Kandukur &mdash; with an ICU, an in-house laboratory and doctors
          on call through the night.
        </p>

        <div class="hero-marks">
          <span class="hero-mark"><?= icon('emergency') ?> 24&times;7 Emergency</span>
          <span class="hero-mark"><?= icon('icu') ?> ICU Care</span>
          <span class="hero-mark"><?= icon('lab') ?> In-house Laboratory</span>
          <span class="hero-mark"><?= icon('scan') ?> 2D Echo</span>
        </div>

        <div class="btn-row">
          <a class="btn btn-lg btn-emergency" href="tel:<?= e(HOSPITAL['mobile']) ?>">
            <?= icon('phone') ?> <?= e(HOSPITAL['mobile_display']) ?>
          </a>
          <a class="btn btn-lg btn-ghost-light" href="book.php">
            <?= icon('ticket') ?> Book a Token
          </a>
        </div>
      </div>

      <div class="hero-card">
        <div class="hero-card-head">
          <h2>Book your OP token</h2>
          <p>Choose a doctor and a day. Your token number appears straight away.</p>
        </div>

        <div class="hero-card-body">
          <div class="hero-docs">
            <?php foreach ($doctors as $doc): ?>
              <a class="hero-doc" href="book.php?doctor=<?= (int) $doc['id'] ?>">
                <?= doctor_avatar() ?>
                <span class="hero-doc-text">
                  <strong><?= e($doc['name']) ?></strong>
                  <span><?= e($doc['speciality']) ?></span>
                </span>
                <?= icon('chevron-right', 'chev') ?>
              </a>
            <?php endforeach; ?>
          </div>

          <a class="btn btn-primary btn-block" href="book.php">
            <?= icon('calendar') ?> See available tokens
          </a>

          <p class="hero-card-note">
            <?= icon('alert') ?>
            <span>In an emergency please call us instead of booking online.</span>
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Fact band --------------------------------------------------------- -->
<section class="fact-band">
  <div class="wrap">
    <div class="fact-grid">
      <div class="fact">
        <span class="fact-icon"><?= icon('clock') ?></span>
        <span class="fact-text">
          <strong>Open 24 Hours</strong>
          <span>Every day, including Sundays</span>
        </span>
      </div>
      <div class="fact">
        <span class="fact-icon"><?= icon('users') ?></span>
        <span class="fact-text">
          <strong>Two Consultants</strong>
          <span>Medicine &amp; Gynaecology</span>
        </span>
      </div>
      <div class="fact">
        <span class="fact-icon"><?= icon('calendar') ?></span>
        <span class="fact-text">
          <strong>Free OP Fridays</strong>
          <span>No consultation charge</span>
        </span>
      </div>
      <div class="fact">
        <span class="fact-icon"><?= icon('location') ?></span>
        <span class="fact-text">
          <strong>Pamuru Road</strong>
          <span>Opposite ICICI Bank</span>
        </span>
      </div>
    </div>
  </div>
</section>

<?php offers_strip(); ?>

<!-- Care departments ------------------------------------------------- -->
<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Our Departments</span>
      <h2>Complete care for your family</h2>
      <p class="lede">
        From everyday fevers and long-term diabetes management to safe deliveries
        and emergency treatment, we look after the whole family.
      </p>
    </div>

    <div class="grid grid-3">
      <a class="card card-link" href="services.php">
        <span class="card-icon"><?= icon('stethoscope') ?></span>
        <h3>General Medicine</h3>
        <p>
          Diabetes and blood pressure, heart and kidney problems, all types of fever,
          dengue and malaria, thyroid disorders, asthma, TB and more.
        </p>
        <span class="card-more">Explore services <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="diabetic-centre.php">
        <span class="card-icon gold"><?= icon('droplet') ?></span>
        <h3>Good Health Diabetic Centre</h3>
        <p>
          Dedicated diabetes care led by a doctor with a Diploma in Endocrinology
          &amp; Diabetology — diagnosis, control and long-term follow-up.
        </p>
        <span class="card-more">Diabetes care <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="maternity.php">
        <span class="card-icon green"><?= icon('baby') ?></span>
        <h3>Maternity &amp; Gynaecology</h3>
        <p>
          Normal and caesarean delivery, high-risk pregnancy, maternity scans,
          PCOD, laparoscopic surgery and complete women's health care.
        </p>
        <span class="card-more">Maternity care <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="emergency.php">
        <span class="card-icon red"><?= icon('emergency') ?></span>
        <h3>Emergency &amp; ICU</h3>
        <p>
          Open 24 hours for accidents, chest pain, breathlessness, snake bite,
          scorpion sting and any sudden illness, with ICU support.
        </p>
        <span class="card-more">Emergency care <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="facilities.php">
        <span class="card-icon"><?= icon('lab') ?></span>
        <h3>Laboratory &amp; Diagnostics</h3>
        <p>
          In-house laboratory for blood investigations, plus 2D Echo scanning
          and maternity scans on site.
        </p>
        <span class="card-more">See facilities <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="tariff.php">
        <span class="card-icon"><?= icon('list') ?></span>
        <h3>Transparent Tariff</h3>
        <p>
          Consultation fees and room charges published openly, so you know what
          to expect before you arrive.
        </p>
        <span class="card-more">View tariff <?= icon('arrow-right') ?></span>
      </a>
    </div>
  </div>
</section>

<!-- Doctors ----------------------------------------------------------- -->
<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Meet Our Doctors</span>
      <h2>Qualified doctors you can reach</h2>
      <p class="lede">
        Two resident consultants covering medicine and women's health, available
        for outpatient consultation every day.
      </p>
    </div>
    <?php doctor_cards($doctors); ?>
    <p class="text-center mt-3 mb-0">
      <a class="link-arrow" href="doctors.php">Read their full profiles <?= icon('arrow-right') ?></a>
    </p>
  </div>
</section>

<!-- Facilities -------------------------------------------------------- -->
<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Facilities</span>
      <h2>Equipped to treat, admit and monitor</h2>
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

<!-- Location ---------------------------------------------------------- -->
<section class="section section-deep">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Find Us</span>
        <h2>Easy to reach in Kandukur</h2>
        <p class="lede mb-2">
          We are on Pamuru Road, directly opposite ICICI Bank and close to
          Thyagarajaswamy Temple.
        </p>
        <?php contact_details(); ?>
        <div class="btn-row mt-3">
          <a class="btn btn-primary" href="<?= e(HOSPITAL['map']['link']) ?>" target="_blank" rel="noopener">
            <?= icon('location') ?> Get Directions
          </a>
        </div>
      </div>
      <?php map_block(); ?>
    </div>
  </div>
</section>

<?php
cta_band();
require __DIR__ . '/includes/footer.php';
