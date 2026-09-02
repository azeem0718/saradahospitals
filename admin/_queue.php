<?php
/**
 * Renders a table of bookings with the reception action buttons.
 * Expects $rows (booking rows) and $backUrl (where actions return to).
 */

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $rows */
/** @var string $backUrl */

$backUrl = $backUrl ?? 'index.php';

if (!$rows) {
    ?>
    <div class="empty-state">
      <?= icon('calendar') ?>
      <p>No bookings to show here.</p>
    </div>
    <?php
    return;
}
?>
<div class="table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th scope="col" style="text-align:center">Token</th>
        <th scope="col">Patient</th>
        <th scope="col">Doctor</th>
        <th scope="col">Date &amp; Session</th>
        <th scope="col">Phone</th>
        <th scope="col">Status</th>
        <th scope="col"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr class="<?= $row['status'] === 'cancelled' ? 'is-cancelled' : '' ?>">
          <td class="token-cell"><?= (int) $row['token_no'] ?></td>

          <td>
            <span class="patient-name"><?= e($row['patient_name']) ?></span>
            <span class="patient-meta">
              <?= (int) $row['patient_age'] ?> / <?= e(ucfirst(substr($row['patient_sex'], 0, 1))) ?>
              <?php if (!empty($row['town'])): ?> &middot; <?= e($row['town']) ?><?php endif; ?>
              &middot; <?= e($row['reference']) ?>
            </span>
            <?php if (!empty($row['reason'])): ?>
              <span class="patient-meta" style="display:block;font-style:italic"><?= e($row['reason']) ?></span>
            <?php endif; ?>
          </td>

          <td><?= e($row['doctor_name']) ?></td>

          <td>
            <?= e(format_date($row['booking_date'])) ?><br>
            <span class="patient-meta"><?= e(session_label($row['session'])) ?></span>
          </td>

          <td><a href="tel:+91<?= e($row['phone']) ?>"><?= e($row['phone']) ?></a></td>

          <td><span class="pill status-<?= e($row['status']) ?>"><?= e(status_label($row['status'])) ?></span></td>

          <td class="actions">
            <div class="row-actions">
              <?php if ($row['status'] !== 'cancelled'): ?>

                <?php if ($row['status'] === 'booked'): ?>
                  <form method="post" action="action.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="status" value="arrived">
                    <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                    <button class="btn-icon good" type="submit" title="Mark arrived"><?= icon('check') ?></button>
                  </form>
                <?php endif; ?>

                <?php if (in_array($row['status'], ['booked', 'arrived'], true)): ?>
                  <form method="post" action="action.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="status" value="completed">
                    <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                    <button class="btn-icon good" type="submit" title="Mark completed"><?= icon('check-circle') ?></button>
                  </form>

                  <form method="post" action="action.php"
                        data-confirm="Mark this patient as a no-show?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="status" value="no_show">
                    <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                    <button class="btn-icon" type="submit" title="Mark no-show"><?= icon('close') ?></button>
                  </form>
                <?php endif; ?>

                <?php if (in_array($row['status'], ['completed', 'no_show'], true)): ?>
                  <!-- Marking the wrong row is easy on a busy desk; allow a step back. -->
                  <form method="post" action="action.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="status" value="booked">
                    <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                    <button class="btn-icon" type="submit" title="Undo — put back in the queue">
                      <?= icon('undo') ?>
                    </button>
                  </form>
                <?php endif; ?>

                <form method="post" action="action.php"
                      data-confirm="Cancel this booking? The patient keeps their slip but the token is freed.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input type="hidden" name="status" value="cancelled">
                  <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                  <button class="btn-icon danger" type="submit" title="Cancel booking"><?= icon('alert') ?></button>
                </form>

              <?php else: ?>
                <form method="post" action="action.php" data-confirm="Restore this cancelled booking?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input type="hidden" name="status" value="booked">
                  <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                  <button class="btn-icon" type="submit" title="Restore booking"><?= icon('plus') ?></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
