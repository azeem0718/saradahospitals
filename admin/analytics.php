<?php
/**
 * Analytics — how the OP token system is actually being used.
 *
 * Reception and the doctors get answers to the questions they ask at the end
 * of a month: how many patients came, which days are heavy, is online booking
 * being adopted, how many tokens go to waste as no-shows. One date-range
 * filter scopes everything below it, so every number on the screen agrees.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/charts.php';
require_once __DIR__ . '/../includes/icons.php';

$user = require_login();

$ranges = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];
$days   = (int) query('days', '30');
if (!isset($ranges[$days])) {
    $days = 30;
}

$end   = new DateTimeImmutable('today');
$start = $end->modify('-' . ($days - 1) . ' days');
$pdo   = db();

// Headline counts. Cancelled tokens are excluded from "issued" — they were
// given back — and counted on their own tile instead.
$stmt = $pdo->prepare(
    'SELECT
        COALESCE(SUM(status <> "cancelled"), 0)                              AS issued,
        COALESCE(SUM(status = "completed"), 0)                               AS completed,
        COALESCE(SUM(status = "no_show"), 0)                                 AS no_show,
        COALESCE(SUM(status = "cancelled"), 0)                               AS cancelled,
        COALESCE(SUM(booked_via = "online" AND status <> "cancelled"), 0)    AS online
       FROM bookings
      WHERE booking_date BETWEEN ? AND ?'
);
$stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
$kpi = array_map('intval', $stmt->fetch());

$onlineShare = $kpi['issued'] > 0 ? (int) round($kpi['online'] * 100 / $kpi['issued']) : 0;
$noShowRate  = $kpi['issued'] > 0 ? (int) round($kpi['no_show'] * 100 / $kpi['issued']) : 0;

// Tokens per day, split by how they were booked.
$stmt = $pdo->prepare(
    'SELECT booking_date, booked_via, COUNT(*) AS c
       FROM bookings
      WHERE booking_date BETWEEN ? AND ? AND status <> "cancelled"
      GROUP BY booking_date, booked_via'
);
$stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
$perDay = [];
foreach ($stmt as $row) {
    $perDay[$row['booking_date']][$row['booked_via']] = (int) $row['c'];
}

$seriesVia  = ['Online', 'Reception'];
$dayColumns = [];
$weekdayTotals = array_fill(0, 7, 0);
for ($d = clone $start; $d <= $end; $d = $d->modify('+1 day')) {
    $key    = $d->format('Y-m-d');
    $online = $perDay[$key]['online'] ?? 0;
    $desk   = $perDay[$key]['reception'] ?? 0;
    $dayColumns[] = [
        'label'  => $days <= 7 ? $d->format('D') : $d->format('j M'),
        'title'  => $d->format('D, j M'),
        'values' => [$online, $desk],
    ];
    $weekdayTotals[(int) $d->format('w')] += $online + $desk;
}

$weekdayColumns = [];
foreach ([1, 2, 3, 4, 5, 6, 0] as $w) { // clinic weeks start Monday
    $name = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$w];
    $weekdayColumns[] = [
        'label'  => substr($name, 0, 3),
        'title'  => $name,
        'values' => [$weekdayTotals[$w]],
    ];
}

// Per doctor: sessions, outcomes.
$stmt = $pdo->prepare(
    'SELECT d.name,
            COALESCE(SUM(b.session = "morning"), 0)  AS morning,
            COALESCE(SUM(b.session = "evening"), 0)  AS evening,
            COALESCE(SUM(b.status = "completed"), 0) AS completed,
            COALESCE(SUM(b.status = "no_show"), 0)   AS no_show,
            COUNT(b.id)                              AS issued
       FROM doctors d
       LEFT JOIN bookings b
         ON b.doctor_id = d.id
        AND b.booking_date BETWEEN ? AND ?
        AND b.status <> "cancelled"
      GROUP BY d.id, d.name
      ORDER BY d.sort_order, d.id'
);
$stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
$byDoctor = $stmt->fetchAll();

$rangeLabel    = format_date($start->format('Y-m-d')) . ' – ' . format_date($end->format('Y-m-d'));
$adminTitle    = 'Analytics';
$adminSubtitle = $rangeLabel;
$adminNav      = 'analytics';

require __DIR__ . '/_header.php';
?>

<div class="range-row" role="group" aria-label="Date range">
  <?php foreach ($ranges as $n => $label): ?>
    <a class="btn btn-sm <?= $n === $days ? 'btn-primary' : 'btn-outline' ?>"
       href="analytics.php?days=<?= $n ?>"
       <?= $n === $days ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="stat-grid">
  <div class="stat accent-navy">
    <span class="stat-label">Tokens issued</span>
    <span class="stat-value"><?= number_format($kpi['issued']) ?></span>
  </div>
  <div class="stat accent-green">
    <span class="stat-label">Completed</span>
    <span class="stat-value"><?= number_format($kpi['completed']) ?></span>
  </div>
  <div class="stat accent-gold">
    <span class="stat-label">Booked online</span>
    <span class="stat-value"><?= $onlineShare ?>%</span>
  </div>
  <div class="stat accent-red">
    <span class="stat-label">No-shows</span>
    <span class="stat-value"><?= number_format($kpi['no_show']) ?><small class="stat-sub"><?= $noShowRate ?>%</small></span>
  </div>
  <div class="stat accent-navy">
    <span class="stat-label">Cancelled</span>
    <span class="stat-value"><?= number_format($kpi['cancelled']) ?></span>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <div>
      <h2>Tokens per day</h2>
      <p>How each day's tokens were booked — online or at the desk.</p>
    </div>
    <?= chart_legend($seriesVia) ?>
  </div>
  <div class="panel-body">
    <?= svg_column_chart($dayColumns, $seriesVia, $days <= 7 ? 1 : ($days <= 30 ? 4 : 10)) ?>
    <?= chart_table($dayColumns, $seriesVia, 'Tokens per day, online vs reception') ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <div>
      <h2>Busy days of the week</h2>
      <p>Total tokens by weekday over the range — where the heavy OP days fall.</p>
    </div>
  </div>
  <div class="panel-body">
    <?= svg_column_chart($weekdayColumns, ['Tokens']) ?>
    <?= chart_table($weekdayColumns, ['Tokens'], 'Tokens by weekday') ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <div>
      <h2>By doctor</h2>
      <p>Sessions and outcomes across the range.</p>
    </div>
  </div>
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="chart-table">
        <thead>
          <tr>
            <th scope="col">Doctor</th>
            <th scope="col">Morning</th>
            <th scope="col">Evening</th>
            <th scope="col">Completed</th>
            <th scope="col">No-shows</th>
            <th scope="col">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($byDoctor as $row): ?>
            <tr>
              <th scope="row"><?= e($row['name']) ?></th>
              <td><?= (int) $row['morning'] ?></td>
              <td><?= (int) $row['evening'] ?></td>
              <td><?= (int) $row['completed'] ?></td>
              <td><?= (int) $row['no_show'] ?></td>
              <td><strong><?= (int) $row['issued'] ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= e(asset('assets/js/charts.js', '../')) ?>" defer></script>

<?php require __DIR__ . '/_footer.php'; ?>
