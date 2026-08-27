<?php
/**
 * Cancel a booking.
 *
 * A token nobody will use should go back into the pool while someone else
 * can still take it. The patient proves the booking is theirs with the
 * reference off their slip plus the phone number they booked with — the
 * reference alone is not enough, because a slip can be photographed over
 * someone's shoulder in a waiting hall.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$activeNav = '';
$ref       = strtoupper(trim(is_post() ? post('ref') : query('ref')));
$booking   = null;
$notFound  = false;
$error     = '';
$done      = false;

if ($ref !== '') {
    if (preg_match('/^SN[2-9BCDFGHJKLMNPQRSTVWXZ]{6}$/', $ref) === 1) {
        $booking = get_booking_by_reference($ref);
    }
    $notFound = $booking === null;
}

if (is_post() && $booking !== null) {
    require_csrf();

    // A soft brake on guessing phone numbers against a photographed slip.
    $_SESSION['cancel_tries'] = (int) ($_SESSION['cancel_tries'] ?? 0) + 1;
    if ($_SESSION['cancel_tries'] > 10) {
        $error = 'Too many attempts. Please call ' . HOSPITAL['landline_display']
               . ' and reception will cancel it for you.';
    } elseif (normalise_phone(post('phone')) !== $booking['phone']) {
        $error = 'That phone number does not match this booking. Please use the '
               . 'number you booked with.';
    } elseif (!booking_cancellable($booking)) {
        $error = $booking['status'] === 'booked'
            ? 'This session has already started, so the token can no longer be '
              . 'cancelled online. If you cannot come, please call reception.'
            : 'This booking can no longer be cancelled online — its status is "'
              . status_label($booking['status']) . '". Please call reception if '
              . 'something is wrong.';
    } else {
        // Guard the status in the WHERE so a race with reception cannot
        // cancel a token that was just marked arrived.
        $stmt = db()->prepare(
            'UPDATE bookings SET status = "cancelled" WHERE id = ? AND status = "booked"'
        );
        $stmt->execute([$booking['id']]);
        if ($stmt->rowCount() === 1) {
            $done    = true;
            $booking = get_booking_by_reference($ref);
        } else {
            $error = 'The booking changed while you were cancelling it. Please '
                   . 'call reception to sort it out.';
        }
    }
}

$pageTitle       = 'Cancel a Booking';
$pageDescription = 'Cancel an OP token at Sarada Nursing Home, Kandukur, so the '
                 . 'slot can go to another patient.';

require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="wrap wrap-narrow">

  <?php if ($done): ?>

    <div class="notice notice-success">
      <?= icon('check-circle') ?>
      <p>
        <strong>Your booking is cancelled.</strong>
        Token <?= (int) $booking['token_no'] ?> for
        <?= e($booking['doctor_name']) ?> on
        <?= e(format_date($booking['booking_date'])) ?>
        (<?= e(session_label($booking['session'])) ?>) has been released.
      </p>
    </div>

    <div class="section-head mt-4">
      <h2>Need a different day instead?</h2>
      <p class="lede">Book a fresh token — it takes under a minute.</p>
    </div>
    <div class="btn-row" style="justify-content:center">
      <a class="btn btn-primary" href="book.php?doctor=<?= (int) $booking['doctor_id'] ?>">
        <?= icon('ticket') ?> Book a new token
      </a>
      <a class="btn btn-outline" href="index.php"><?= icon('home') ?> Back to home</a>
    </div>

  <?php elseif ($booking !== null): ?>

    <div class="section-head">
      <h1>Cancel this booking?</h1>
      <p class="lede">
        Check the details, then confirm with the phone number you booked with.
      </p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="notice notice-emergency">
        <?= icon('alert') ?>
        <p><strong><?= e($error) ?></strong></p>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <dl class="cancel-summary">
        <dt>Reference</dt><dd><strong><?= e($booking['reference']) ?></strong></dd>
        <dt>Patient</dt><dd><?= e($booking['patient_name']) ?></dd>
        <dt>Doctor</dt><dd><?= e($booking['doctor_name']) ?></dd>
        <dt>Date</dt><dd><?= e(format_date($booking['booking_date'])) ?> ·
            <?= e(session_label($booking['session'])) ?></dd>
        <dt>Token</dt><dd><?= (int) $booking['token_no'] ?></dd>
        <dt>Status</dt>
        <dd><span class="pill <?= $booking['status'] === 'cancelled' ? 'pill-red' : 'pill-navy' ?>">
          <?= e(status_label($booking['status'])) ?></span></dd>
      </dl>

      <?php if ($booking['status'] === 'cancelled'): ?>
        <div class="notice notice-info mb-0">
          <?= icon('info') ?>
          <p><strong>This booking is already cancelled.</strong>
             <a href="book.php">Book a new token</a> if you need one.</p>
        </div>
      <?php elseif (!booking_cancellable($booking)): ?>
        <div class="notice notice-info mb-0">
          <?= icon('info') ?>
          <p>
            <strong>This booking can no longer be cancelled online.</strong>
            Please call
            <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>
            and reception will help.
          </p>
        </div>
      <?php else: ?>
        <form method="post" action="cancel.php">
          <?= csrf_field() ?>
          <input type="hidden" name="ref" value="<?= e($booking['reference']) ?>">
          <div class="field">
            <label for="phone">Phone number used for this booking</label>
            <input type="tel" id="phone" name="phone" required inputmode="numeric"
                   autocomplete="tel" placeholder="10-digit mobile number">
          </div>
          <button class="btn btn-emergency btn-block" type="submit">
            <?= icon('close') ?> Cancel this booking
          </button>
          <p class="small muted text-center mt-1 mb-0">
            The token goes back into the pool for another patient.
            This cannot be undone online.
          </p>
        </form>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div class="section-head">
      <h1>Cancel a booking</h1>
      <p class="lede">
        Enter the reference number from your token slip, for example
        <strong>SN4KQ2TB</strong>.
      </p>
    </div>

    <?php if ($notFound): ?>
      <div class="notice notice-emergency">
        <?= icon('alert') ?>
        <p>
          <strong>We could not find that reference.</strong>
          Please check the letters and numbers and try again, or call
          <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>.
        </p>
      </div>
    <?php endif; ?>

    <form class="form-card" method="get" action="cancel.php">
      <div class="field">
        <label for="ref">Booking reference</label>
        <input type="text" id="ref" name="ref" required
               maxlength="10" autocomplete="off" spellcheck="false"
               style="text-transform:uppercase;letter-spacing:.12em;font-weight:600"
               placeholder="SN4KQ2TB" value="<?= e($ref) ?>">
      </div>
      <button class="btn btn-primary btn-block" type="submit">
        <?= icon('search') ?> Find My Booking
      </button>
    </form>

  <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
