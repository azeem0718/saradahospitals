<?php
/**
 * All bookings with filtering, phone search and paging.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';

$user = require_login();

$search    = query('q');
$status    = query('status');
$doctorId  = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT) ?: 0;
$fromDate  = query('from');
$toDate    = query('to');
$page      = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage   = 40;

$where  = [];
$params = [];

if ($search !== '') {
    // Search by phone digits, reference, or name.
    $digits = preg_replace('/\D+/', '', $search) ?? '';
    if ($digits !== '' && strlen($digits) >= 4) {
        $where[]  = 'b.phone LIKE ?';
        $params[] = '%' . $digits . '%';
    } else {
        $where[]  = '(b.patient_name LIKE ? OR b.reference = ?)';
        $params[] = '%' . $search . '%';
        $params[] = strtoupper($search);
    }
}

if (in_array($status, ['booked', 'arrived', 'completed', 'no_show', 'cancelled'], true)) {
    $where[]  = 'b.status = ?';
    $params[] = $status;
}

if ($doctorId > 0) {
    $where[]  = 'b.doctor_id = ?';
    $params[] = $doctorId;
}

if (valid_date($fromDate)) {
    $where[]  = 'b.booking_date >= ?';
    $params[] = $fromDate;
}

if (valid_date($toDate)) {
    $where[]  = 'b.booking_date <= ?';
    $params[] = $toDate;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM bookings b {$whereSql}");
$countStmt->execute($params);
$total  = (int) $countStmt->fetchColumn();
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT b.*, d.name AS doctor_name
          FROM bookings b JOIN doctors d ON d.id = b.doctor_id
          {$whereSql}
      ORDER BY b.booking_date DESC,
               FIELD(b.session,'morning','evening'), b.token_no
         LIMIT {$perPage} OFFSET {$offset}";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$doctors = get_doctors(false);

// Preserve filters when paging.
$qs = array_filter([
    'q'         => $search,
    'status'    => $status,
    'doctor_id' => $doctorId ?: '',
    'from'      => $fromDate,
    'to'        => $toDate,
], static fn($v): bool => $v !== '' && $v !== 0);

$backUrl = 'bookings.php?' . http_build_query($qs + ['page' => $page]);

$adminTitle    = 'All Bookings';
$adminSubtitle = number_format($total) . ' booking' . ($total === 1 ? '' : 's') . ' found';
$adminNav      = 'bookings';

require __DIR__ . '/_header.php';
?>

<div class="panel">
  <form method="get" action="bookings.php" class="filter-bar">
    <div class="field" style="flex:2 1 220px">
      <label for="q">Search</label>
      <input type="text" id="q" name="q" value="<?= e($search) ?>"
             placeholder="Phone, name or reference">
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (['booked','arrived','completed','no_show','cancelled'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>>
            <?= e(status_label($s)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="doctor_id">Doctor</label>
      <select id="doctor_id" name="doctor_id" data-autosubmit>
        <option value="">All doctors</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= (int) $doc['id'] ?>" <?= $doctorId === (int) $doc['id'] ? 'selected' : '' ?>>
            <?= e($doc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="from">From</label>
      <input type="date" id="from" name="from" value="<?= e($fromDate) ?>">
    </div>

    <div class="field">
      <label for="to">To</label>
      <input type="date" id="to" name="to" value="<?= e($toDate) ?>">
    </div>

    <button class="btn btn-primary" type="submit"><?= icon('search') ?> Search</button>
    <a class="btn btn-outline" href="bookings.php">Clear</a>
  </form>

  <div class="panel-body flush">
    <?php require __DIR__ . '/_queue.php'; ?>
  </div>

  <?php if ($pages > 1): ?>
    <div class="panel-head" style="border-top:1px solid var(--rule);border-bottom:0;justify-content:center">
      <div class="btn-row">
        <?php if ($page > 1): ?>
          <a class="btn btn-outline btn-sm" href="bookings.php?<?= e(http_build_query($qs + ['page' => $page - 1])) ?>">
            <?= icon('chevron-left') ?> Previous
          </a>
        <?php endif; ?>
        <span class="btn btn-sm" style="pointer-events:none">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
          <a class="btn btn-outline btn-sm" href="bookings.php?<?= e(http_build_query($qs + ['page' => $page + 1])) ?>">
            Next <?= icon('chevron-right') ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
