<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Tariff & Charges';
$pageDescription = 'Published consultation fees and room charges at Sarada Nursing Home, Kandukur — OP consultation ₹200, ICU ₹3,000 per day, A/C rooms from ₹1,400 per day.';

/* BREADCRUMB-SEO */
$breadcrumb      = [['Tariff & Charges', null]];
$activeNav       = 'tariff';

require __DIR__ . '/includes/header.php';
page_hero(
    text('tariff.hero.title'),
    text('tariff.hero.lede'),
    'Tariff',
    'tariff'
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
      <p><?= text_rich('tariff.note.friday') ?></p>
    </div>

    <div class="notice notice-success">
      <?= icon('discount') ?>
      <p><?= text_rich('tariff.note.seniors') ?></p>
    </div>

    <div class="notice notice-info mb-0">
      <?= icon('info') ?>
      <p><?= text_rich('tariff.note.small') ?></p>
    </div>

  </div>
</section>

<?php cta_band('Questions about charges?', 'Call our reception and we will explain what your treatment is likely to cost.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
