<?php
/**
 * Token slip: shows a confirmed booking, or a lookup form when no valid
 * reference is supplied.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$activeNav = '';
$ref       = strtoupper(trim(query('ref')));
$booking   = null;
$notFound  = false;

if ($ref !== '') {
    // Cheap shape check before touching the database.
    if (preg_match('/^SN[2-9BCDFGHJKLMNPQRSTVWXZ]{6}$/', $ref) === 1) {
        $booking = get_booking_by_reference($ref);
    }
    $notFound = $booking === null;
}

$pageTitle       = $booking ? 'Your Token' : 'Find Your Token';
$pageDescription = 'View your outpatient token for Sarada Nursing Home, Kandukur.';

/* BREADCRUMB-SEO */
$pageNoIndex     = true;

require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="wrap wrap-narrow">

  <?php if ($booking): ?>

    <?php
      $cancelled = $booking['status'] === 'cancelled';
      $past      = $booking['booking_date'] < date('Y-m-d');

      $slip = sprintf(
          "*Sarada Nursing Home* — OP Token\n\n" .
          "Token No: *%d*\n" .
          "Reference: %s\n" .
          "Patient: %s (%d/%s)\n" .
          "Doctor: %s\n" .
          "Date: %s\n" .
          "Session: %s\n\n" .
          "%s, %s, %s\n" .
          "Phone: %s\n\n" .
          "Please arrive 15 minutes early and show this token at reception.",
          (int) $booking['token_no'],
          $booking['reference'],
          $booking['patient_name'],
          (int) $booking['patient_age'],
          ucfirst(substr($booking['patient_sex'], 0, 1)),
          $booking['doctor_name'],
          format_date($booking['booking_date']),
          session_label($booking['session']),
          HOSPITAL['address']['line1'],
          HOSPITAL['address']['line2'],
          HOSPITAL['address']['district'],
          HOSPITAL['mobile_display']
      );
    ?>

    <?php if ($cancelled): ?>
      <div class="notice notice-emergency">
        <?= icon('alert') ?>
        <p>
          <strong>This booking has been cancelled.</strong>
          If this is unexpected, please call
          <a href="tel:<?= e(HOSPITAL['mobile']) ?>"><?= e(HOSPITAL['mobile_display']) ?></a>.
        </p>
      </div>
    <?php elseif ($past): ?>
      <div class="notice notice-info">
        <?= icon('info') ?>
        <p><strong>This token was for a past date.</strong>
           To see the doctor again, please <a href="book.php">book a new token</a>.</p>
      </div>
    <?php else: ?>
      <div class="notice notice-success">
        <?= icon('check-circle') ?>
        <p>
          <strong>Your token is confirmed.</strong>
          Please arrive about 15 minutes before your session and show this token,
          or your reference number, at reception.
        </p>
      </div>
    <?php endif; ?>

    <div class="token-slip">
      <div class="token-slip-head">
        <?= logo_mark() ?>
        <span class="brand-name"><?= brand_name_html() ?></span>
        <span class="brand-tag"><?= e(HOSPITAL['tagline']) ?></span>
      </div>

      <div class="token-number">
        <span class="label">Your Token Number</span>
        <span class="value"><?= (int) $booking['token_no'] ?></span>
        <span class="ref">Reference <strong><?= e($booking['reference']) ?></strong></span>
      </div>

      <div class="token-details">
        <dl>
          <dt>Patient</dt>
          <dd><?= e($booking['patient_name']) ?></dd>

          <dt>Age / Sex</dt>
          <dd><?= (int) $booking['patient_age'] ?> / <?= e(ucfirst($booking['patient_sex'])) ?></dd>

          <dt>Doctor</dt>
          <dd><?= e($booking['doctor_name']) ?></dd>

          <dt>Date</dt>
          <dd><?= e(format_date($booking['booking_date'])) ?></dd>

          <dt>Session</dt>
          <dd><?= e(session_label($booking['session'])) ?></dd>

          <?php if (is_free_op_day($booking['booking_date'])): ?>
            <dt>Consultation</dt>
            <dd><span class="pill pill-green">Free OP Day</span></dd>
          <?php endif; ?>

          <dt>Status</dt>
          <dd>
            <span class="pill <?= $cancelled ? 'pill-red' : 'pill-navy' ?>">
              <?= e(status_label($booking['status'])) ?>
            </span>
          </dd>
        </dl>
      </div>

      <div class="token-foot">
        <?= e(HOSPITAL['address']['line1']) ?>,
        <?= e(HOSPITAL['address']['line2']) ?><br>
        <strong><?= e(HOSPITAL['landline_display']) ?></strong> &middot;
        <strong><?= e(HOSPITAL['mobile_display']) ?></strong>
      </div>
    </div>

    <?php if (!$cancelled && !$past): ?>
      <?php if ($booking['booking_date'] === date('Y-m-d')): ?>
        <div class="btn-row mt-3 no-print" style="justify-content:center">
          <a class="btn btn-primary" href="queue.php?ref=<?= e($booking['reference']) ?>">
            <?= icon('users') ?> Live queue — see your place
          </a>
        </div>
      <?php endif; ?>
      <div class="btn-row mt-3 no-print" style="justify-content:center">
        <a class="btn btn-whatsapp"
           href="https://wa.me/?text=<?= rawurlencode($slip) ?>"
           target="_blank" rel="noopener">
          <?= icon('whatsapp') ?> Send to WhatsApp
        </a>
        <button class="btn btn-outline" type="button" data-print>
          <?= icon('print') ?> Print Token
        </button>
      </div>

      <div class="notice notice-info mt-3 mb-0 no-print">
        <?= icon('info') ?>
        <p>
          <strong>Need to cancel or change this booking?</strong>
          <?php if (booking_cancellable($booking)): ?>
            You can <a href="cancel.php?ref=<?= e($booking['reference']) ?>">cancel it
            online</a> with the phone number you booked with, or call
          <?php else: ?>
            Please call
          <?php endif; ?>
          <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>
          so someone else can use the token.
        </p>
      </div>
    <?php endif; ?>

  <?php else: ?>

    <div class="section-head">
      <h1>Find your token</h1>
      <p class="lede">
        Enter the reference number from your booking, for example
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

    <form class="form-card" method="get" action="booking.php">
      <div class="field">
        <label for="ref">Booking reference</label>
        <input type="text" id="ref" name="ref" required
               maxlength="10" autocomplete="off" spellcheck="false"
               style="text-transform:uppercase;letter-spacing:.12em;font-weight:600"
               placeholder="SN4KQ2TB" value="<?= e($ref) ?>">
      </div>
      <button class="btn btn-primary btn-block" type="submit">
        <?= icon('search') ?> Find My Token
      </button>
    </form>

    <p class="text-center mt-3 mb-0">
      <a class="btn btn-outline" href="book.php"><?= icon('ticket') ?> Book a new token</a>
    </p>

  <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
