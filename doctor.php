<?php
/**
 * A single doctor's profile.
 *
 * Everything on this page comes from the doctors table, so reception fills it
 * in from the admin panel. Sections with nothing in them are not rendered,
 * which keeps a half-filled profile looking deliberate rather than broken.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$doctor = get_doctor_by_slug(query('slug'));

if ($doctor === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$short     = doctor_short_name($doctor['name']);
$education = profile_lines($doctor['education'] ?? null);
$services  = profile_lines($doctor['services'] ?? null);
$timings   = trim((string) ($doctor['opd_timings'] ?? '')) !== ''
    ? profile_lines($doctor['opd_timings'])
    : doctor_opd_summary((int) $doctor['id']);

$pageTitle       = $doctor['name'];
$pageDescription = trim(($doctor['name'] . ' — ' . $doctor['speciality'] . ' at Sarada Nursing Home, Kandukur. '
                       . mb_substr((string) $doctor['bio'], 0, 130)));
$activeNav       = 'doctors';

require __DIR__ . '/includes/header.php';
?>

<?php [$docClass, $docStyle] = hero_art_attrs('doctors', 'doctors'); ?>
<section class="page-hero<?= $docClass ?>"<?= $docStyle ?>>
  <div class="wrap">
    <p class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep" aria-hidden="true">/</span>
      <a href="doctors.php">Doctors</a>
      <span class="sep" aria-hidden="true">/</span>
      <span><?= e($short) ?></span>
    </p>
    <h1><?= e($doctor['name']) ?></h1>
    <?php if ($doctor['designation'] !== ''): ?>
      <p><?= e($doctor['designation']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="profile-layout">

      <!-- Identity card -->
      <aside class="profile-aside">
        <div class="profile-card">
          <div class="profile-portrait">
            <?php if (!empty($doctor['photo'])): ?>
              <img src="<?= e(asset('assets/img/doctors/' . $doctor['photo'])) ?>"
                   alt="<?= e($doctor['name']) ?>" loading="lazy" width="320" height="320">
            <?php else: ?>
              <?= doctor_avatar('') ?>
            <?php endif; ?>
          </div>

          <h2 class="profile-name"><?= e($doctor['name']) ?></h2>
          <p class="profile-quals"><?= e($doctor['qualifications']) ?></p>

          <dl class="profile-facts">
            <div>
              <dt>Department</dt>
              <dd><?= e($doctor['speciality']) ?></dd>
            </div>
            <?php if (!empty($doctor['experience_years'])): ?>
              <div>
                <dt>Experience</dt>
                <dd><?= (int) $doctor['experience_years'] ?> years</dd>
              </div>
            <?php endif; ?>
            <?php if ($doctor['designation'] !== ''): ?>
              <div>
                <dt>Designation</dt>
                <dd><?= e($doctor['designation']) ?></dd>
              </div>
            <?php endif; ?>
            <?php if ($doctor['languages'] !== ''): ?>
              <div>
                <dt>Languages</dt>
                <dd><?= e($doctor['languages']) ?></dd>
              </div>
            <?php endif; ?>
            <?php if ($doctor['reg_no'] !== ''): ?>
              <div>
                <dt>Medical Reg. No.</dt>
                <dd><?= e($doctor['reg_no']) ?></dd>
              </div>
            <?php endif; ?>
            <?php if ($timings): ?>
              <div>
                <dt>OP timings</dt>
                <dd>
                  <?php foreach ($timings as $line): ?>
                    <span class="profile-timing"><?= e($line) ?></span>
                  <?php endforeach; ?>
                </dd>
              </div>
            <?php endif; ?>
            <div>
              <dt>Location</dt>
              <dd><?= e($doctor['location'] !== ''
                    ? $doctor['location']
                    : HOSPITAL['address']['line2'] . ', ' . HOSPITAL['address']['district']) ?></dd>
            </div>
          </dl>

          <a class="btn btn-primary btn-block" href="book.php?doctor=<?= (int) $doctor['id'] ?>">
            <?= icon('ticket') ?> Book an OP Token
          </a>
          <a class="btn btn-outline btn-block" href="tel:<?= e(HOSPITAL['landline']) ?>">
            <?= icon('phone') ?> <?= e(HOSPITAL['landline_display']) ?>
          </a>
        </div>
      </aside>

      <!-- The write-up -->
      <div class="profile-main">
        <?php if (!empty($doctor['bio'])): ?>
          <section class="profile-block">
            <h2>About the Doctor</h2>
            <p class="lede"><?= e($doctor['bio']) ?></p>
          </section>
        <?php endif; ?>

        <?php if ($education): ?>
          <section class="profile-block">
            <h2>Educational Qualifications</h2>
            <ul class="profile-list">
              <?php foreach ($education as $line): ?>
                <li><?= icon('arrow-right') ?><span><?= e($line) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if ($services): ?>
          <section class="profile-block">
            <h2>Services offered</h2>
            <ul class="profile-list two-col">
              <?php foreach ($services as $line): ?>
                <li><?= icon('arrow-right') ?><span><?= e($line) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <div class="notice notice-info mb-0">
          <?= icon('clock') ?>
          <p>
            <strong>Tokens, not appointment times.</strong>
            Book a token for a morning or evening session and arrive about
            15 minutes before it starts. Live token counts are on the
            <a href="book.php?doctor=<?= (int) $doctor['id'] ?>">booking page</a>.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<?php cta_band('Book with ' . $short, 'Choose a day and session, and get your token number straight away.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
