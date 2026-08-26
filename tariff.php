<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Tariff & Charges';
$pageDescription = 'Published consultation fees and room charges at Sarada Nursing Home, Kandukur — OP consultation ₹200, ICU ₹3,000 per day, A/C rooms from ₹1,400 per day.';
$activeNav       = 'tariff';

require __DIR__ . '/includes/header.php';
page_hero(
    'Tariff & Charges',
    'Our fees are displayed at reception and published here, so you know what to expect before you arrive.',
    'Tariff'
);
?>

<section class="section">
  <div class="wrap wrap-narrow">

    <div class="table-card mb-3">
      <table>
        <caption class="sr-only">Consultation fees</caption>
        <thead>
          <tr><th scope="col">Consultation</th><th scope="col">Charge</th></tr>
        </thead>
        <tbody>
          <?php foreach (CONSULTATION_FEES as $fee): ?>
            <tr>
              <th scope="row" style="font-weight:500"><?= e($fee['label']) ?></th>
              <td class="amount"><?= money($fee['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="table-card mb-3">
      <table>
        <caption class="sr-only">Room and service charges</caption>
        <thead>
          <tr><th scope="col">Room &amp; Services</th><th scope="col">Charge</th></tr>
        </thead>
        <tbody>
          <?php foreach (ROOM_CHARGES as $row): ?>
            <tr>
              <th scope="row" style="font-weight:500"><?= e($row['label']) ?></th>
              <td class="amount">
                <?= money($row['amount']) ?>
                <span class="muted small nowrap" style="font-weight:400"><?= e($row['unit']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="notice notice-success">
      <?= icon('calendar') ?>
      <p>
        <strong>Free OP every Friday.</strong>
        Outpatient consultations are provided free of charge every Friday.
      </p>
    </div>

    <div class="notice notice-success">
      <?= icon('discount') ?>
      <p>
        <strong>20% off blood tests for patients above 60.</strong>
        Please tell reception your age when the test is ordered.
      </p>
    </div>

    <div class="notice notice-info mb-0">
      <?= icon('info') ?>
      <p>
        <strong>Please note</strong>
        The charges above cover consultation, room occupancy and the listed
        services. Medicines, laboratory investigations, procedures and surgery are
        billed separately according to the treatment given. For an estimate before
        admission, please ask at reception or call
        <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>.
      </p>
    </div>

  </div>
</section>

<?php cta_band('Questions about charges?', 'Call our reception and we will explain what your treatment is likely to cost.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
