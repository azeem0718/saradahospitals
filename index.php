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
          <?= e(text('home.hero.place')) ?>
        </p>

        <h1><?= text_html('home.hero.title') ?></h1>

        <p class="hero-lede"><?= e(text('home.hero.lede')) ?></p>

        <!-- Segmented booking bar. Doctor, day and session rather than the
             location/department pairing a multi-site chain needs: there is one
             hospital and two consultants here. Submits to book.php with
             everything already chosen. -->
        <form class="findbar" method="get" action="book.php">
          <div class="findbar-field cs" data-cs>
            <label class="cs-label" for="fb-doctor"><?= icon('stethoscope') ?> Doctor</label>
            <select id="fb-doctor" name="doctor">
              <?php foreach ($doctors as $doc): ?>
                <option value="<?= (int) $doc['id'] ?>"><?= e(doctor_short_name($doc['name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <span class="findbar-sep" aria-hidden="true"></span>

          <div class="findbar-field cs" data-cs>
            <label class="cs-label" for="fb-date"><?= icon('calendar') ?> Day</label>
            <select id="fb-date" name="date">
              <?php foreach (bookable_dates() as $i => $d):
                $dt  = new DateTimeImmutable($d);
                $lbl = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $dt->format('D, j M'));
                if (is_free_op_day($d)) { $lbl .= ' · Free OP'; }
              ?>
                <option value="<?= e($d) ?>"<?= ($nextSlot && $nextSlot['date'] === $d) ? ' selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <span class="findbar-sep" aria-hidden="true"></span>

          <div class="findbar-field cs" data-cs>
            <label class="cs-label" for="fb-session"><?= icon('clock') ?> Session</label>
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
      <span class="eyebrow"><?= e(text('home.departments.eyebrow')) ?></span>
      <h2><?= e(text('home.departments.title')) ?></h2>
      <p class="lede">
        From everyday fevers and long-term diabetes management to safe deliveries
        and emergency treatment, we look after the whole family.
      </p>
    </div>

<?php
/**
 * A department card's opener: the photograph reception uploaded for this slot,
 * or the icon it has always had. Rendering both would be noise, so it is one or
 * the other.
 */
$cardOpener = static function (string $slot, string $iconName, string $tone = ''): string {
    $url = site_image_url($slot);
    if ($url !== null) {
        return '<span class="card-photo"><img src="' . e($url) . '"'
             . site_image_srcset($slot, '(max-width: 520px) 94vw, (max-width: 940px) 46vw, 400px')
             . ' alt="' . e(site_image_alt($slot))
             . '" loading="lazy" decoding="async" width="640" height="360"></span>';
    }
    return '<span class="card-icon' . ($tone !== '' ? ' ' . e($tone) : '') . '">'
         . icon($iconName) . '</span>';
};
?>
    <div class="grid grid-3">
      <a class="card card-link" href="services.php">
        <?= $cardOpener('card-medicine', 'stethoscope') ?>
        <h3><?= e(text('home.card.medicine.title')) ?></h3>
        <p><?= e(text('home.card.medicine.body')) ?></p>
        <span class="card-more">Explore services <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="diabetic-centre.php">
        <?= $cardOpener('card-diabetes', 'droplet', 'gold') ?>
        <h3><?= e(text('home.card.diabetes.title')) ?></h3>
        <p><?= e(text('home.card.diabetes.body')) ?></p>
        <span class="card-more">Diabetes care <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="maternity.php">
        <?= $cardOpener('card-maternity', 'maternity', 'green') ?>
        <h3><?= e(text('home.card.maternity.title')) ?></h3>
        <p><?= e(text('home.card.maternity.body')) ?></p>
        <span class="card-more">Maternity care <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="emergency.php">
        <?= $cardOpener('card-emergency', 'emergency', 'red') ?>
        <h3><?= e(text('home.card.emergency.title')) ?></h3>
        <p><?= e(text('home.card.emergency.body')) ?></p>
        <span class="card-more">Emergency care <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="facilities.php">
        <?= $cardOpener('card-lab', 'lab') ?>
        <h3><?= e(text('home.card.lab.title')) ?></h3>
        <p><?= e(text('home.card.lab.body')) ?></p>
        <span class="card-more">See facilities <?= icon('arrow-right') ?></span>
      </a>

      <a class="card card-link" href="tariff.php">
        <?= $cardOpener('card-tariff', 'list') ?>
        <h3><?= e(text('home.card.tariff.title')) ?></h3>
        <p><?= e(text('home.card.tariff.body')) ?></p>
        <span class="card-more">View tariff <?= icon('arrow-right') ?></span>
      </a>
    </div>
  </div>
</section>

<!-- Doctors ----------------------------------------------------------- -->
<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><?= e(text('home.doctors.eyebrow')) ?></span>
      <h2><?= e(text('home.doctors.title')) ?></h2>
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
      <span class="eyebrow"><?= e(text('home.facilities.eyebrow')) ?></span>
      <h2><?= e(text('home.facilities.title')) ?></h2>
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
        <span class="eyebrow"><?= e(text('home.find.eyebrow')) ?></span>
        <h2><?= e(text('home.find.title')) ?></h2>
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
