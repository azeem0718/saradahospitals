<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Our Facilities';
$pageDescription = 'Facilities at Sarada Nursing Home, Kandukur — ICU care, modern laboratory, 2D Echo scan, maternity scanning, oxygen support and air-conditioned rooms and wards.';
$activeNav       = '';

require __DIR__ . '/includes/header.php';
page_hero(
    'Our Facilities',
    'Everything on this page is available inside the building, so patients are not sent elsewhere mid-treatment.',
    'Facilities',
    'facilities'
);
?>

<section class="section">
  <div class="wrap">
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

<section class="section section-paper">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Rooms &amp; Admission</span>
        <h2>Air-conditioned rooms and wards</h2>
        <p>
          Patients who need to be admitted can choose from an air-conditioned
          single room, a sharing room, or the general ward. Every option is
          air-conditioned, and charges are published openly.
        </p>
        <p class="mb-0">
          Oxygen support and infusion are available for admitted patients, charged
          as listed on the tariff.
        </p>
        <a class="btn btn-outline mt-2" href="tariff.php"><?= icon('list') ?> View room charges</a>
      </div>

      <div class="table-card">
        <table>
          <caption class="sr-only">Room types and charges</caption>
          <thead><tr><th scope="col">Room Type</th><th scope="col">Per Day</th></tr></thead>
          <tbody>
            <?php foreach (ROOM_CHARGES as $row): if ($row['unit'] !== 'per day') continue; ?>
              <tr>
                <th scope="row" style="font-weight:500"><?= e($row['label']) ?></th>
                <td class="amount"><?= money($row['amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Diagnostics</span>
      <h2>Tests done here, not sent away</h2>
    </div>
    <div class="grid grid-3">
      <div class="card">
        <span class="card-icon"><?= icon('lab') ?></span>
        <h3>Modern Laboratory</h3>
        <p>Blood investigations and routine tests processed in our own laboratory, so results reach the doctor quickly.</p>
      </div>
      <div class="card">
        <span class="card-icon"><?= icon('scan') ?></span>
        <h3>2D Echo Scan</h3>
        <p>Cardiac echo scanning on site for patients with heart complaints or long-standing diabetes.</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('maternity') ?></span>
        <h3>Maternity Scans</h3>
        <p>Pregnancy scanning as part of routine antenatal care and high-risk pregnancy monitoring.</p>
      </div>
    </div>

    <div class="notice notice-success mt-3 mb-0">
      <?= icon('discount') ?>
      <p>
        <strong>Patients above 60 receive 20% off blood tests.</strong>
        Please mention your age when the test is ordered.
      </p>
    </div>
  </div>
</section>

<?php cta_band(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
