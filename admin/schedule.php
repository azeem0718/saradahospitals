<?php
/**
 * Per-doctor weekly session times and token caps.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';

$user    = require_login();
$doctors = get_doctors(false);

$doctorId = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT)
    ?: (int) ($doctors[0]['id'] ?? 0);

if (is_post()) {
    require_csrf();

    $doctorId = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT) ?: 0;
    $rows     = $_POST['sessions'] ?? [];

    if (!get_doctor($doctorId)) {
        flash('That doctor was not found.', 'error');
        redirect('schedule.php');
    }

    $saved  = 0;
    $update = db()->prepare(
        'UPDATE doctor_sessions
            SET start_time = ?, end_time = ?, token_cap = ?, is_active = ?
          WHERE doctor_id = ? AND weekday = ? AND session = ?'
    );

    foreach (SESSIONS as $session) {
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            $row = $rows[$weekday][$session] ?? null;
            if (!is_array($row)) {
                continue;
            }

            $start = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($row['start'] ?? '')) === 1
                ? $row['start'] . ':00' : null;
            $end = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($row['end'] ?? '')) === 1
                ? $row['end'] . ':00' : null;
            $cap = filter_var($row['cap'] ?? '', FILTER_VALIDATE_INT);

            // Skip anything malformed rather than writing nonsense to the table.
            if ($start === null || $end === null || $cap === false || $cap < 1 || $cap > 500) {
                continue;
            }
            if ($end <= $start) {
                continue;
            }

            $update->execute([
                $start, $end, $cap,
                isset($row['active']) ? 1 : 0,
                $doctorId, $weekday, $session,
            ]);
            $saved++;
        }
    }

    flash($saved > 0
        ? 'Schedule saved.'
        : 'Nothing was saved — please check the times and token caps.',
        $saved > 0 ? 'success' : 'error');
    redirect('schedule.php?doctor_id=' . $doctorId);
}

$stmt = db()->prepare(
    'SELECT weekday, session, start_time, end_time, token_cap, is_active
       FROM doctor_sessions WHERE doctor_id = ?'
);
$stmt->execute([$doctorId]);

$schedule = [];
foreach ($stmt->fetchAll() as $row) {
    $schedule[(int) $row['weekday']][$row['session']] = $row;
}

$weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$doctor   = get_doctor($doctorId);

$adminTitle    = 'Consultation Schedule';
$adminSubtitle = 'Set session times and how many tokens each session can take.';
$adminNav      = 'schedule';

require __DIR__ . '/_header.php';
?>

<div class="panel">
  <div class="panel-head">
    <div>
      <h2><?= $doctor ? e($doctor['name']) : 'Schedule' ?></h2>
      <p>Untick a session to close it for that day.</p>
    </div>
    <form method="get" action="schedule.php">
      <select class="panel-select" name="doctor_id" data-autosubmit>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= (int) $doc['id'] ?>" <?= $doctorId === (int) $doc['id'] ? 'selected' : '' ?>>
            <?= e($doc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="panel-body flush">
    <form method="post" action="schedule.php">
      <?= csrf_field() ?>
      <input type="hidden" name="doctor_id" value="<?= $doctorId ?>">

      <div class="table-wrap">
        <table class="admin-table sched-table">
          <thead>
            <tr>
              <th scope="col">Day</th>
              <th scope="col">Session</th>
              <th scope="col">Open</th>
              <th scope="col">From</th>
              <th scope="col">To</th>
              <th scope="col">Tokens</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($weekdays as $wd => $dayName): ?>
              <?php foreach (SESSIONS as $i => $session):
                $row = $schedule[$wd][$session] ?? null;
                $base = "sessions[{$wd}][{$session}]";
              ?>
                <tr>
                  <?php if ($i === 0): ?>
                    <td class="sched-day" rowspan="2"><?= e($dayName) ?></td>
                  <?php endif; ?>
                  <td><?= e(session_label($session)) ?></td>
                  <td>
                    <input type="checkbox" name="<?= e($base) ?>[active]" value="1"
                           style="width:20px;height:20px"
                           <?= ($row['is_active'] ?? 1) ? 'checked' : '' ?>>
                  </td>
                  <td>
                    <input type="time" name="<?= e($base) ?>[start]" required
                           value="<?= e(substr((string) ($row['start_time'] ?? '09:00:00'), 0, 5)) ?>">
                  </td>
                  <td>
                    <input type="time" name="<?= e($base) ?>[end]" required
                           value="<?= e(substr((string) ($row['end_time'] ?? '13:00:00'), 0, 5)) ?>">
                  </td>
                  <td>
                    <input type="number" name="<?= e($base) ?>[cap]" min="1" max="500" required
                           value="<?= (int) ($row['token_cap'] ?? setting_int('default_token_cap', 30)) ?>">
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="panel-head" style="border-top:1px solid var(--rule);border-bottom:0">
        <p>Changes apply to future bookings. Tokens already issued are not affected.</p>
        <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save Schedule</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
