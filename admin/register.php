<?php
/**
 * Daily OP register — the day's bookings as a printable register and a CSV.
 *
 * The paper register is what the hospital already keeps by hand; this prints
 * the same thing from the system, with a signature column so it can still be
 * signed against at the desk. The CSV is for anyone who wants the day in a
 * spreadsheet.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/icons.php';

$user = require_login();

$date = query('date');
if (!valid_date($date)) {
    $date = date('Y-m-d');
}

$stmt = db()->prepare(
    'SELECT b.*, d.name AS doctor_name
       FROM bookings b JOIN doctors d ON d.id = b.doctor_id
      WHERE b.booking_date = ?
      ORDER BY FIELD(b.session, "morning", "evening"), d.sort_order, b.token_no'
);
$stmt->execute([$date]);
$all = $stmt->fetchAll();

// The CSV leaves before any HTML. Cells that a spreadsheet could read as a
// formula are prefixed with a space, so a crafted "name" cannot execute.
if (query('export') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="op-register-' . $date . '.csv"');
    header('Cache-Control: no-store');

    $safe = static function (?string $v): string {
        $v = (string) $v;
        return $v !== '' && strpbrk($v[0], '=+-@') !== false ? ' ' . $v : $v;
    };

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Session', 'Token', 'Reference', 'Patient', 'Age', 'Sex',
                   'Phone', 'Town', 'Reason', 'Doctor', 'Status', 'Booked via', 'Booked at']);
    foreach ($all as $row) {
        fputcsv($out, [
            $row['booking_date'],
            $row['session'],
            $row['token_no'],
            $row['reference'],
            $safe($row['patient_name']),
            $row['patient_age'],
            $row['patient_sex'],
            $safe($row['phone']),
            $safe($row['town']),
            $safe($row['reason']),
            $row['doctor_name'],
            $row['status'],
            $row['booked_via'],
            $row['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

$bySession = ['morning' => [], 'evening' => []];
foreach ($all as $row) {
    $bySession[$row['session']][] = $row;
}
$active = count(array_filter($all, static fn ($r) => $r['status'] !== 'cancelled'));

$prev = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
$next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');

$adminTitle    = 'OP Register';
$adminSubtitle = format_date($date) . ' — ' . $active . ' active token' . ($active === 1 ? '' : 's');
$adminNav      = 'today';
$adminActions  =
    '<a class="btn btn-outline btn-sm" href="register.php?date=' . e($prev) . '">' . icon('chevron-left') . ' Previous</a>' .
    '<a class="btn btn-outline btn-sm" href="register.php">Today</a>' .
    '<a class="btn btn-outline btn-sm" href="register.php?date=' . e($next) . '">Next ' . icon('chevron-right') . '</a>' .
    '<a class="btn btn-outline btn-sm" href="register.php?date=' . e($date) . '&amp;export=csv">' . icon('arrow-right') . ' CSV</a>' .
    '<button class="btn btn-primary btn-sm" type="button" data-print>' . icon('print') . ' Print</button>';

require __DIR__ . '/_header.php';
?>

<div class="register-sheet">
  <div class="register-head">
    <?= logo_mark('brand-mark', '../') ?>
    <div>
      <h2><?= e(HOSPITAL['name']) ?></h2>
      <p><?= e(HOSPITAL['address']['line1']) ?>, <?= e(HOSPITAL['address']['line2']) ?>,
         <?= e(HOSPITAL['address']['district']) ?> ·
         <?= e(HOSPITAL['landline_display']) ?></p>
    </div>
    <div class="register-date">
      <span>Outpatient Register</span>
      <strong><?= e(format_date($date)) ?></strong>
      <?php if (is_free_op_day($date)): ?><em>Free OP day</em><?php endif; ?>
    </div>
  </div>

  <?php foreach (['morning', 'evening'] as $session): ?>
    <h3 class="register-session"><?= e(session_label($session)) ?> session
      <span>· <?= count($bySession[$session]) ?> booking<?= count($bySession[$session]) === 1 ? '' : 's' ?></span></h3>

    <?php if (!$bySession[$session]): ?>
      <p class="muted register-empty">No bookings.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="chart-table register-table">
          <thead>
            <tr>
              <th scope="col">Token</th>
              <th scope="col">Patient</th>
              <th scope="col">Age / Sex</th>
              <th scope="col">Phone</th>
              <th scope="col">Town</th>
              <th scope="col">Doctor</th>
              <th scope="col">Via</th>
              <th scope="col">Status</th>
              <th scope="col" class="register-sign">Signature</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bySession[$session] as $row): ?>
              <tr<?= $row['status'] === 'cancelled' ? ' class="is-cancelled"' : '' ?>>
                <td><strong><?= (int) $row['token_no'] ?></strong>
                    <span class="muted small"><?= e($row['reference']) ?></span></td>
                <th scope="row"><?= e($row['patient_name']) ?></th>
                <td><?= (int) $row['patient_age'] ?> / <?= e(ucfirst(substr($row['patient_sex'], 0, 1))) ?></td>
                <td><?= e($row['phone']) ?></td>
                <td><?= e($row['town'] ?? '') ?></td>
                <td><?= e(doctor_short_name($row['doctor_name'])) ?></td>
                <td><?= $row['booked_via'] === 'online' ? 'Online' : 'Desk' ?></td>
                <td><?= e(status_label($row['status'])) ?></td>
                <td class="register-sign"></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <p class="register-foot">
    Printed <?= e(date('j M Y, g:i a')) ?> by <?= e($user['full_name']) ?> ·
    <?= count($all) ?> entr<?= count($all) === 1 ? 'y' : 'ies' ?> in all
  </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
