<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$pageTitle       = 'Our Doctors';
$pageDescription = 'Meet the doctors at Sarada Nursing Home, Kandukur — Dr. Gundavarapu Venkatesh (General Medicine, Diabetology) and Dr. Maddipudi Brahmani (Obstetrics & Gynaecology).';
$activeNav       = 'doctors';

$doctors = get_doctors();

require __DIR__ . '/includes/header.php';
page_hero(
    'Our Doctors',
    'Two resident consultants covering general medicine, diabetes care and women\'s health.',
    'Doctors'
);
?>

<section class="section">
  <div class="wrap">
    <?php foreach ($doctors as $i => $doc): ?>
      <article class="card" style="margin-bottom:1.5rem">
        <div class="grid" style="grid-template-columns:minmax(0,1fr);gap:1.5rem">
          <div style="display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap">
            <div class="doctor-portrait" style="width:96px;height:96px"><?= doctor_avatar('') ?></div>
            <div style="flex:1;min-width:240px">
              <h2 style="font-size:1.5rem;margin-bottom:.15rem"><?= e($doc['name']) ?></h2>
              <p class="doctor-quals" style="font-size:.95rem"><?= e($doc['qualifications']) ?></p>
              <p class="doctor-spec" style="margin-bottom:.9rem"><?= e($doc['speciality']) ?></p>
              <a class="btn btn-primary btn-sm" href="book.php?doctor=<?= (int) $doc['id'] ?>">
                <?= icon('ticket') ?> Book with <?= e(explode(' ', $doc['name'])[0] . ' ' . (explode(' ', $doc['name'])[2] ?? '')) ?>
              </a>
            </div>
          </div>

          <?php if (!empty($doc['bio'])): ?>
            <p style="margin:0"><?= e($doc['bio']) ?></p>
          <?php endif; ?>

          <?php if ($doc['slug'] === 'dr-gundavarapu-venkatesh'): ?>
            <div class="notice notice-warn mb-0">
              <?= icon('award') ?>
              <p>
                <strong>Certified in Type 2 Diabetes Management</strong>
                Completed the <em>Changing the Paradigm in Type 2 Diabetes Mellitus
                Management</em> self-study programme — a multidisciplinary diabetes
                programme developed by Medical Trends and based on official resources
                of the American Diabetes Association (ADA).
              </p>
            </div>
            <div>
              <h3 style="font-size:1.05rem">Consults for</h3>
              <ul class="service-list two-col">
                <?php foreach (GENERAL_MEDICINE as $s): ?>
                  <li><?= icon('check') ?><span><?= e($s) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php else: ?>
            <div>
              <h3 style="font-size:1.05rem">Consults for</h3>
              <ul class="service-list two-col">
                <?php foreach (OBG_SERVICES as $s): ?>
                  <li><?= icon('check') ?><span><?= e($s) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>

    <div class="notice notice-info">
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
