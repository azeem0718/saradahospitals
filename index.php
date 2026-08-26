<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';
require_once __DIR__ . '/includes/illustration.php';

$pageTitle       = '';
$pageDescription = 'Sarada Nursing Home, Kandukur — 24/7 emergency care, General Medicine, Diabetology and Obstetrics & Gynaecology. ICU, modern laboratory, 2D Echo and A/C rooms. Book an OP token online.';
$activeNav       = 'home';

$doctors  = get_doctors();
$nextSlot = next_available();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap">
    <div class="hero-grid">

      <div class="hero-copy">
        <p class="hero-place">
          <?= icon('location') ?>
          Pamuru Road, Kandukur &middot; Prakasam District
        </p>

        <h1>Let&rsquo;s find you<br>a doctor.</h1>

        <p class="hero-lede">
          General Medicine, Diabetology and Obstetrics &amp; Gynaecology under one
          roof &mdash; open every hour of every day.
        </p>

        <!-- Segmented booking bar. Doctor, day and session rather than the
             location/department pairing a multi-site chain needs: there is one
             hospital and two consultants here. Submits to book.php with
             everything already chosen. -->
        <form class="findbar" method="get" action="book.php">
          <div class="findbar-field">
            <label for="fb-doctor"><?= icon('stethoscope') ?> Doctor</label>
            <select id="fb-doctor" name="doctor">
              <?php foreach ($doctors as $doc): ?>
                <option value="<?= (int) $doc['id'] ?>"><?= e(doctor_short_name($doc['name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <span class="findbar-sep" aria-hidden="true"></span>

          <div class="findbar-field">
            <label for="fb-date"><?= icon('calendar') ?> Day</label>
            <select id="fb-date" name="date">
              <?php foreach (bookable_dates() as $i => $d):
                $dt = new DateTimeImmutable($d);
                $lbl = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $dt->format('D, j M'));
              ?>
                <option value="<?= e($d) ?>"<?= ($nextSlot && $nextSlot['date'] === $d) ? ' selected' : '' ?>>
                  <?= e($lbl) ?><?= is_free_op_day($d) ? ' — Free OP' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <span class="findbar-sep" aria-hidden="true"></span>

          <div class="findbar-field">
            <label for="fb-session"><?= icon('clock') ?> Session</label>
            <select id="fb-session" name="session">
              <option value="morning"<?= ($nextSlot && $nextSlot['session'] === 'morning') ? ' selected' : '' ?>>Morning</option>
              <option value="evening"<?= ($nextSlot && $nextSlot['session'] === 'evening') ? ' selected' : '' ?>>Evening</option>
            </select>
          </div>

          <button class="findbar-go" type="submit">
            <?= icon('search') ?><span>Search</span>
          </button>
        </form>

        <?php if ($nextSlot !== null): ?>
          <p class="hero-live">
            <span class="live-dot" aria-hidden="true"></span>
            <strong><?= e($nextSlot['when']) ?> &middot; <?= e($nextSlot['label']) ?></strong>
            session open &mdash; <?= $nextSlot['remaining'] ?> token<?= $nextSlot['remaining'] === 1 ? '' : 's' ?> left
          </p>
        <?php endif; ?>
      </div>

      <div class="hero-art" aria-hidden="false">
        <span class="hero-art-disc" aria-hidden="true"></span>
        <?= hospital_illustration() ?>
      </div>

    </div>
  </div>
</section>

<!-- Quick links ------------------------------------------------------- -->
<section class="quicklinks">
  <div class="wrap">
    <div class="quick-rail">
      <a class="quick" href="book.php">
        <span class="quick-icon"><?= icon('ticket') ?></span>
        <span>Book a Token</span>
      </a>
      <a class="quick" href="doctors.php">
        <span class="quick-icon"><?= icon('users') ?></span>
        <span>Our Doctors</span>
      </a>
      <a class="quick" href="services.php">
        <span class="quick-icon"><?= icon('stethoscope') ?></span>
        <span>Services</span>
      </a>
      <a class="quick" href="diabetic-centre.php">
        <span class="quick-icon"><?= icon('droplet') ?></span>
        <span>Diabetic Centre</span>
      </a>
      <a class="quick" href="maternity.php">
        <span class="quick-icon"><?= icon('maternity') ?></span>
        <span>Maternity</span>
      </a>
      <a class="quick" href="tariff.php">
        <span class="quick-icon"><?= icon('list') ?></span>
        <span>Tariff</span>
      </a>
      <a class="quick" href="emergency.php">
        <span class="quick-icon"><?= icon('emergency') ?></span>
        <span>Emergency</span>
      </a>
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
          <span>Including Sundays</span>
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
