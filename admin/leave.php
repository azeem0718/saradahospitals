<?php
/**
 * Block days when a doctor is on leave, or the whole hospital OP is closed.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';

$user    = require_login();
$doctors = get_doctors(false);
$errors  = [];

if (is_post()) {
    require_csrf();

    if (post('action') === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($id > 0) {
            db()->prepare('DELETE FROM blocked_days WHERE id = ?')->execute([$id]);
            flash('Leave entry removed. That session is open again.');
        }
        redirect('leave.php');
    }

    $doctorRaw = post('doctor_id');
    $doctorId  = $doctorRaw === 'all' ? null : (filter_var($doctorRaw, FILTER_VALIDATE_INT) ?: 0);
    $date      = post('block_date');
    $session   = post('session');
    $reason    = post('reason');

    if ($doctorId !== null && !get_doctor($doctorId)) {
        $errors['doctor_id'] = 'Please choose a doctor.';
    }
    if (!valid_date($date)) {
        $errors['block_date'] = 'Please choose a valid date.';
    }
    if (!in_array($session, ['morning', 'evening', 'both'], true)) {
        $errors['session'] = 'Please choose which session to block.';
    }
    if (mb_strlen($reason) > 160) {
        $errors['reason'] = 'Please keep the reason short.';
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO blocked_days (doctor_id, block_date, session, reason)
             VALUES (?,?,?,?)'
        )->execute([$doctorId, $date, $session, $reason ?: null]);

        flash('Leave added. Online booking is now closed for that session.');
        redirect('leave.php');
    }
}

// Show upcoming blocks, plus the last week for context.
$stmt = db()->prepare(
    'SELECT bd.*, d.name AS doctor_name
       FROM blocked_days bd LEFT JOIN doctors d ON d.id = bd.doctor_id
      WHERE bd.block_date >= (CURDATE() - INTERVAL 7 DAY)
      ORDER BY bd.block_date, bd.session'
);
$stmt->execute();
$blocks = $stmt->fetchAll();

$adminTitle    = 'Doctor Leave & Closures';
$adminSubtitle = 'Close a session so patients cannot book it online.';
$adminNav      = 'leave';

require __DIR__ . '/_header.php';
?>

<div class="grid" style="grid-template-columns:minmax(0,1fr);gap:1.5rem">

  <div class="panel" style="max-width:640px">
    <div class="panel-head"><h2>Add leave or closure</h2></div>
    <div class="panel-body">
      <form method="post" action="leave.php">
        <?= csrf_field() ?>

        <div class="field">
          <label for="doctor_id">Who is unavailable? <span class="req">*</span></label>
          <select id="doctor_id" name="doctor_id" required>
            <option value="all">Whole hospital OP (all doctors)</option>
            <?php foreach ($doctors as $doc): ?>
              <option value="<?= (int) $doc['id'] ?>"><?= e($doc['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['doctor_id'])): ?><span class="error"><?= e($errors['doctor_id']) ?></span><?php endif; ?>
        </div>

        <div class="field-row cols-2">
          <div class="field">
            <label for="block_date">Date <span class="req">*</span></label>
            <input type="date" id="block_date" name="block_date" required
                   min="<?= e(date('Y-m-d')) ?>" value="<?= e(post('block_date', date('Y-m-d'))) ?>">
            <?php if (isset($errors['block_date'])): ?><span class="error"><?= e($errors['block_date']) ?></span><?php endif; ?>
          </div>

          <div class="field">
            <label for="session">Session <span class="req">*</span></label>
            <select id="session" name="session" required>
              <option value="both">Whole day</option>
              <option value="morning">Morning only</option>
              <option value="evening">Evening only</option>
            </select>
            <?php if (isset($errors['session'])): ?><span class="error"><?= e($errors['session']) ?></span><?php endif; ?>
          </div>
        </div>

        <div class="field">
          <label for="reason">Reason</label>
          <span class="hint">For your own reference only. Patients do not see this.</span>
          <input type="text" id="reason" name="reason" maxlength="160"
                 placeholder="e.g. Conference, personal leave">
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= icon('plus') ?> Add Leave</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>Scheduled leave</h2>
        <p>Upcoming closures, and the past week for reference.</p>
      </div>
    </div>
    <div class="panel-body flush">
      <?php if (!$blocks): ?>
        <div class="empty-state">
          <?= icon('calendar') ?>
          <p>No leave has been recorded. All sessions are open.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="admin-table" style="min-width:600px">
            <thead>
              <tr>
                <th scope="col">Date</th>
                <th scope="col">Doctor</th>
                <th scope="col">Session</th>
                <th scope="col">Reason</th>
                <th scope="col"><span class="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blocks as $b): $past = $b['block_date'] < date('Y-m-d'); ?>
                <tr<?= $past ? ' style="opacity:.5"' : '' ?>>
                  <td><?= e(format_date($b['block_date'])) ?></td>
                  <td><?= $b['doctor_name'] ? e($b['doctor_name']) : '<em>All doctors</em>' ?></td>
                  <td>
                    <span class="pill pill-navy">
                      <?= $b['session'] === 'both' ? 'Whole day' : e(session_label($b['session'])) ?>
                    </span>
                  </td>
                  <td class="muted"><?= e($b['reason'] ?? '—') ?></td>
                  <td class="actions">
                    <form method="post" action="leave.php" data-confirm="Remove this leave entry and reopen the session?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                      <button class="btn-icon danger" type="submit" title="Remove"><?= icon('close') ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    Blocking a session stops new online bookings. Tokens already issued for that
    session are not cancelled automatically — check
    <a href="bookings.php">All Bookings</a> and call those patients.
  </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
