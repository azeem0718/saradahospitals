<?php
/**
 * Reception dashboard — today's queue at a glance.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/icons.php';  // icon() is used below, before _header.php loads it

$user = require_login();

$date = query('date');
if (!valid_date($date)) {
    $date = date('Y-m-d');
}

$prev = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
$next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');

$stmt = db()->prepare(
    'SELECT b.*, d.name AS doctor_name
       FROM bookings b JOIN doctors d ON d.id = b.doctor_id
      WHERE b.booking_date = ?
      ORDER BY FIELD(b.session,"morning","evening"), d.sort_order, b.token_no'
);
$stmt->execute([$date]);
$all = $stmt->fetchAll();

$counts = ['booked' => 0, 'arrived' => 0, 'completed' => 0, 'no_show' => 0, 'cancelled' => 0];
foreach ($all as $row) {
    $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
}
$active = count($all) - $counts['cancelled'];

$bySession = ['morning' => [], 'evening' => []];
foreach ($all as $row) {
    $bySession[$row['session']][] = $row;
}

$backUrl = 'index.php?date=' . urlencode($date);

$isToday       = $date === date('Y-m-d');
$adminTitle    = $isToday ? 'Today' : format_date($date);
$adminSubtitle = ($isToday ? format_date($date) . ' — ' : '')
               . $active . ' active token' . ($active === 1 ? '' : 's');
$adminNav      = 'today';
$adminActions  =
    '<a class="btn btn-outline btn-sm" href="index.php?date=' . e($prev) . '">' . icon('chevron-left') . ' Previous</a>' .
    '<a class="btn btn-outline btn-sm" href="index.php">Today</a>' .
    '<a class="btn btn-outline btn-sm" href="index.php?date=' . e($next) . '">Next ' . icon('chevron-right') . '</a>' .
    '<a class="btn btn-primary btn-sm" href="new.php?date=' . e($date) . '">' . icon('plus') . ' New Token</a>';

require __DIR__ . '/_header.php';
?>

<div class="stat-grid">
  <div class="stat accent-navy">
    <span class="stat-label">Total Tokens</span>
    <span class="stat-value"><?= $active ?></span>
  </div>
  <div class="stat accent-navy">
    <span class="stat-label">Waiting</span>
    <span class="stat-value"><?= $counts['booked'] ?></span>
  </div>
  <div class="stat accent-gold">
    <span class="stat-label">Arrived</span>
    <span class="stat-value"><?= $counts['arrived'] ?></span>
  </div>
  <div class="stat accent-green">
    <span class="stat-label">Completed</span>
    <span class="stat-value"><?= $counts['completed'] ?></span>
  </div>
  <div class="stat accent-red">
    <span class="stat-label">No Show</span>
    <span class="stat-value"><?= $counts['no_show'] ?></span>
  </div>
</div>

<?php if (is_free_op_day($date)): ?>
  <div class="notice notice-success">
    <?= icon('calendar') ?>
    <p><strong>Free OP day.</strong> Consultations on this day are free of charge.</p>
  </div>
<?php endif; ?>

<?php foreach (['morning', 'evening'] as $session): ?>
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2><?= e(session_label($session)) ?> Session</h2>
        <p><?= count($bySession[$session]) ?> booking<?= count($bySession[$session]) === 1 ? '' : 's' ?></p>
      </div>
    </div>
    <div class="panel-body flush">
      <?php
        $rows = $bySession[$session];
        require __DIR__ . '/_queue.php';
      ?>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/_footer.php'; ?>
