<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$pageTitle       = 'Our Doctors';
$pageDescription = 'Meet the doctors at Sarada Nursing Home, Kandukur — Dr. Gundavarapu Venkatesh (General Medicine, Diabetology) and Dr. Maddipudi Brahmani (Obstetrics & Gynaecology).';

/* BREADCRUMB-SEO */
$breadcrumb      = [['Our Doctors', null]];
$activeNav       = 'doctors';

$doctors = get_doctors();

require __DIR__ . '/includes/header.php';
page_hero(
    text('doctors.hero.title'),
    text('doctors.hero.lede'),
    'Doctors',
    'doctors'
);
?>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow"><?= e(text('doctors.list.eyebrow')) ?></span>
      <h2><?= e(text('doctors.list.title')) ?></h2>
      <p><?= e(text('doctors.list.lede')) ?></p>
    </div>

    <div class="doc-cards">
      <?php foreach ($doctors as $doc): ?>
        <?php
          $short   = doctor_short_name($doc['name']);
          $timings = trim((string) ($doc['opd_timings'] ?? '')) !== ''
              ? profile_lines($doc['opd_timings'])
              : doctor_opd_summary((int) $doc['id']);
        ?>
        <article class="doc-card">
          <div class="doc-card-portrait">
            <?php if (!empty($doc['photo'])): ?>
              <img src="<?= e(asset('assets/img/doctors/' . $doc['photo'])) ?>"
                   alt="<?= e($doc['name']) ?>" loading="lazy" width="200" height="200">
            <?php else: ?>
              <?= doctor_avatar('') ?>
            <?php endif; ?>
          </div>

          <div class="doc-card-body">
            <h3><a href="doctor.php?slug=<?= e($doc['slug']) ?>"><?= e($doc['name']) ?></a></h3>
            <p class="doc-card-quals"><?= e($doc['qualifications']) ?></p>
            <p class="doc-card-role">
              <?= e($doc['designation'] !== '' ? $doc['designation'] : $doc['speciality']) ?>
            </p>

            <ul class="doc-card-meta">
              <?php if (!empty($doc['experience_years'])): ?>
                <li><?= icon('award') ?><?= (int) $doc['experience_years'] ?> years</li>
              <?php endif; ?>
              <?php if ($timings): ?>
                <li><?= icon('clock') ?><?= e(count($timings) > 1 ? 'Morning & evening OP' : $timings[0]) ?></li>
              <?php endif; ?>
              <li>
                <?= icon('location') ?>
                <?= e($doc['location'] !== '' ? $doc['location'] : HOSPITAL['address']['line2']) ?>
              </li>
            </ul>
          </div>

          <div class="doc-card-actions">
            <a class="btn btn-outline btn-block" href="doctor.php?slug=<?= e($doc['slug']) ?>">
              View Profile
            </a>
            <a class="btn btn-primary btn-block" href="book.php?doctor=<?= (int) $doc['id'] ?>">
              <?= icon('ticket') ?> Book a Token
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="notice notice-info mt-3 mb-0">
      <?= icon('clock') ?>
      <p>
        <strong>OP consultation timings</strong>
        Current session timings and the number of tokens still available are shown
        live on the <a href="book.php">booking page</a>.
      </p>
    </div>
  </div>
</section>

<?php cta_band('Book with a doctor', 'Choose your doctor and session, and get your token number straight away.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
