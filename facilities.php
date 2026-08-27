<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Our Facilities';
$pageDescription = 'Facilities at Sarada Nursing Home, Kandukur — ICU care, modern laboratory, 2D Echo scan, maternity scanning, oxygen support and air-conditioned rooms and wards.';
$activeNav       = '';

require __DIR__ . '/includes/header.php';
page_hero(
    text('facilities.hero.title'),
    text('facilities.hero.lede'),
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
        <span class="eyebrow"><?= e(text('facilities.rooms.eyebrow')) ?></span>
        <h2><?= e(text('facilities.rooms.title')) ?></h2>
        <?php $roomParas = text_paragraphs('facilities.rooms.body'); ?>
        <?php foreach ($roomParas as $i => $para): ?>
          <p<?= $i === count($roomParas) - 1 ? ' class="mb-0"' : '' ?>><?= $para ?></p>
        <?php endforeach; ?>
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
      <span class="eyebrow"><?= e(text('facilities.diagnostics.eyebrow')) ?></span>
      <h2><?= e(text('facilities.diagnostics.title')) ?></h2>
    </div>
    <div class="grid grid-3">
      <?php foreach (list_shaped('facilities.diagnostics') as $d): ?>
        <div class="card">
          <span class="card-icon"><?= icon($d['icon']) ?></span>
          <h3><?= e($d['title']) ?></h3>
          <p><?= e($d['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="notice notice-success mt-3 mb-0">
      <?= icon('discount') ?>
      <p><?= text_rich('facilities.note.seniors') ?></p>
    </div>
  </div>
</section>

<?php cta_band(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
