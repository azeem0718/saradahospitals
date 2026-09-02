<?php
/**
 * Public token booking form.
 *
 * Validates server-side regardless of what the browser sent, allocates a token
 * inside a transaction, then redirects to the confirmation slip so a refresh
 * cannot create a second booking.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$pageTitle       = 'Book an OP Token';
$pageDescription = 'Book an outpatient consultation token at Sarada Nursing Home, Kandukur. Choose your doctor and session and get your token number instantly.';

/* BREADCRUMB-SEO */
$breadcrumb      = [['Book an OP Token', null]];
$activeNav       = '';

$doctors = get_doctors();
$dates   = bookable_dates();
$errors  = [];

// Sticky form values.
// The hero's find bar links here with a doctor, date and session already
// chosen, so the patient lands on a form that is largely filled in.
$prefillDate    = query('date');
$prefillSession = query('session');

$values = [
    'doctor_id'    => (string) (filter_input(INPUT_GET, 'doctor', FILTER_VALIDATE_INT) ?: ''),
    'booking_date' => date_in_window($prefillDate) ? $prefillDate : '',
    'session'      => in_array($prefillSession, SESSIONS, true) ? $prefillSession : '',
    'patient_name' => '',
    'patient_age'  => '',
    'patient_sex'  => '',
    'phone'        => '',
    'town'         => '',
    'reason'       => '',
];

$bookingsOpen = setting('bookings_enabled', '1') === '1';

if (is_post() && $bookingsOpen) {
    require_csrf();

    foreach (array_keys($values) as $key) {
        $values[$key] = post($key);
    }

    // Honeypot: a real person never fills a field they cannot see.
    if (post('website') !== '') {
        $errors['form'] = 'Your booking could not be submitted. Please call the hospital.';
    }

    if (!$errors && !rate_limit_ok()) {
        $errors['form'] = 'Too many booking attempts from this connection. Please wait a while, or call '
                        . HOSPITAL['mobile_display'] . ' to book by phone.';
    }

    $doctorId = (int) $values['doctor_id'];
    $doctor   = $doctorId > 0 ? get_doctor($doctorId) : null;
    if (!$doctor) {
        $errors['doctor_id'] = 'Please choose a doctor.';
    }

    if (!date_in_window($values['booking_date'])) {
        $errors['booking_date'] = 'Please choose a date from the list.';
    }

    if (!in_array($values['session'], SESSIONS, true)) {
        $errors['session'] = 'Please choose a session.';
    }

    $name = $values['patient_name'];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors['patient_name'] = 'Please enter the patient\'s full name.';
    }

    $age = filter_var($values['patient_age'], FILTER_VALIDATE_INT);
    if ($age === false || $age < 0 || $age > 120) {
        $errors['patient_age'] = 'Please enter an age between 0 and 120.';
    }

    if (!in_array($values['patient_sex'], ['male', 'female', 'other'], true)) {
        $errors['patient_sex'] = 'Please select the patient\'s sex.';
    }

    $phone = normalise_phone($values['phone']);
    if ($phone === null) {
        $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
    }

    if (mb_strlen($values['town']) > 100) {
        $errors['town'] = 'Please shorten the town or village name.';
    }

    if (mb_strlen($values['reason']) > 500) {
        $errors['reason'] = 'Please keep the reason under 500 characters.';
    }

    // Re-check availability at submit time; the form may have been open a while.
    if (!$errors) {
        $slot = session_availability($doctorId, $values['booking_date'], $values['session']);
        if ($slot === null) {
            $errors['session'] = 'That session is not available on the date you chose.';
        } elseif (!$slot['available']) {
            $errors['session'] = $slot['reason'] . '. Please choose another session.';
        }
    }

    if (!$errors) {
        $result = create_booking([
            'doctor_id'    => $doctorId,
            'booking_date' => $values['booking_date'],
            'session'      => $values['session'],
            'patient_name' => $name,
            'patient_age'  => $age,
            'patient_sex'  => $values['patient_sex'],
            'phone'        => $phone,
            'town'         => $values['town'],
            'reason'       => $values['reason'],
        ]);

        if ($result['ok']) {
            redirect('booking.php?ref=' . urlencode($result['booking']['reference']));
        }
        $errors['form'] = $result['error'];
    }
}

require __DIR__ . '/includes/header.php';
page_hero(
    'Book an OP Token',
    'Pick your doctor, day and session. You will get a token number to show at reception.',
    'Book a Token',
    'book'
);
?>

<section class="section">
  <div class="wrap wrap-narrow">

    <?php emergency_notice(); ?>

    <?php if (!$bookingsOpen): ?>
      <div class="notice notice-warn">
        <?= icon('info') ?>
        <p>
          <strong>Online booking is temporarily closed.</strong>
          Please call <a href="tel:<?= e(HOSPITAL['mobile']) ?>"><?= e(HOSPITAL['mobile_display']) ?></a>
          or visit the hospital reception to book your consultation.
        </p>
      </div>
    <?php else: ?>

      <?php if (isset($errors['form'])): ?>
        <div class="notice notice-emergency">
          <?= icon('alert') ?>
          <p><?= e($errors['form']) ?></p>
        </div>
      <?php elseif ($errors): ?>
        <div class="notice notice-emergency">
          <?= icon('alert') ?>
          <p>Please check the highlighted fields below and try again.</p>
        </div>
      <?php endif; ?>

      <form class="form-card" id="booking-form" method="post" action="book.php" novalidate>
        <?= csrf_field() ?>

        <!-- Honeypot -->
        <div class="hp-field" aria-hidden="true">
          <label for="website">Website</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <!-- Doctor -->
        <fieldset>
          <legend class="fieldset-legend step"><span class="num">1</span> Choose a doctor</legend>
          <div class="choice-grid cols-2">
            <?php foreach ($doctors as $doc): ?>
              <div class="choice">
                <input type="radio" name="doctor_id" id="doc-<?= (int) $doc['id'] ?>"
                       value="<?= (int) $doc['id'] ?>"
                       <?= $values['doctor_id'] === (string) $doc['id'] ? 'checked' : '' ?> required>
                <label class="choice-label" for="doc-<?= (int) $doc['id'] ?>">
                  <?= doctor_avatar() ?>
                  <span>
                    <span class="choice-title"><?= e($doc['name']) ?></span>
                    <span class="choice-sub"><?= e($doc['speciality']) ?></span>
                  </span>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (isset($errors['doctor_id'])): ?>
            <span class="error"><?= e($errors['doctor_id']) ?></span>
          <?php endif; ?>
        </fieldset>

        <!-- Date -->
        <fieldset>
          <legend class="fieldset-legend step"><span class="num">2</span> Choose a date</legend>
          <div class="date-strip">
            <?php foreach ($dates as $i => $date):
              $d      = new DateTimeImmutable($date);
              $isFree = is_free_op_day($date);
            ?>
              <div class="date-choice">
                <input type="radio" name="booking_date" id="date-<?= $i ?>"
                       value="<?= e($date) ?>"
                       <?= $values['booking_date'] === $date ? 'checked' : '' ?> required>
                <label class="choice-label" for="date-<?= $i ?>">
                  <span class="date-dow"><?= $i === 0 ? 'Today' : e($d->format('D')) ?></span>
                  <span class="date-day"><?= e($d->format('j')) ?></span>
                  <span class="date-mon"><?= e($d->format('M')) ?></span>
                  <?php if ($isFree): ?><span class="date-free">FREE OP</span><?php endif; ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (isset($errors['booking_date'])): ?>
            <span class="error"><?= e($errors['booking_date']) ?></span>
          <?php endif; ?>
        </fieldset>

        <!-- Session -->
        <fieldset>
          <legend class="fieldset-legend step"><span class="num">3</span> Choose a session</legend>
          <!-- The session radios are built by JS once availability is known, so the
               choice made in the hero's find bar (or lost to a failed submit) is
               handed over here and applied after that first render. -->
          <div id="session-options" data-preselect="<?= e($values['session']) ?>">
            <div class="notice notice-info mb-0">
              <p>Choose a doctor and a date to see available sessions.</p>
            </div>
          </div>
          <?php if (isset($errors['session'])): ?>
            <span class="error"><?= e($errors['session']) ?></span>
          <?php endif; ?>
        </fieldset>

        <hr>

        <!-- Patient -->
        <fieldset>
          <legend class="fieldset-legend step"><span class="num">4</span> Patient details</legend>

          <div class="field">
            <label for="patient_name">Patient's full name <span class="req">*</span></label>
            <input type="text" id="patient_name" name="patient_name" required
                   maxlength="120" autocomplete="name"
                   value="<?= e($values['patient_name']) ?>"
                   <?= isset($errors['patient_name']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['patient_name'])): ?>
              <span class="error"><?= e($errors['patient_name']) ?></span>
            <?php endif; ?>
          </div>

          <div class="field-row cols-2">
            <div class="field">
              <label for="patient_age">Age <span class="req">*</span></label>
              <input type="number" id="patient_age" name="patient_age" required
                     min="0" max="120" inputmode="numeric"
                     value="<?= e($values['patient_age']) ?>"
                     <?= isset($errors['patient_age']) ? 'aria-invalid="true"' : '' ?>>
              <?php if (isset($errors['patient_age'])): ?>
                <span class="error"><?= e($errors['patient_age']) ?></span>
              <?php endif; ?>
            </div>

            <div class="field">
              <label for="phone">Mobile number <span class="req">*</span></label>
              <input type="tel" id="phone" name="phone" required
                     inputmode="tel" autocomplete="tel" maxlength="15"
                     placeholder="10-digit mobile"
                     value="<?= e($values['phone']) ?>"
                     <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>>
              <?php if (isset($errors['phone'])): ?>
                <span class="error"><?= e($errors['phone']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="field">
            <span class="fieldset-legend" id="sex-label">Sex <span class="req">*</span></span>
            <div class="choice-grid cols-3" role="radiogroup" aria-labelledby="sex-label">
              <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label): ?>
                <div class="choice">
                  <input type="radio" name="patient_sex" id="sex-<?= e($val) ?>" value="<?= e($val) ?>"
                         <?= $values['patient_sex'] === $val ? 'checked' : '' ?> required>
                  <label class="choice-label choice-center" for="sex-<?= e($val) ?>">
                    <span class="choice-title"><?= e($label) ?></span>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (isset($errors['patient_sex'])): ?>
              <span class="error"><?= e($errors['patient_sex']) ?></span>
            <?php endif; ?>
          </div>

          <div class="field">
            <label for="town">Town or village</label>
            <span class="hint">Helps our reception identify you quickly.</span>
            <input type="text" id="town" name="town" maxlength="100"
                   value="<?= e($values['town']) ?>">
          </div>

          <div class="field">
            <label for="reason">Reason for visit</label>
            <span class="hint">Optional. A short note, for example "sugar check-up" or "fever for 3 days".</span>
            <textarea id="reason" name="reason" maxlength="500"><?= e($values['reason']) ?></textarea>
          </div>
        </fieldset>

        <button class="btn btn-primary btn-lg btn-block" type="submit">
          <?= icon('check') ?> Confirm and Get My Token
        </button>

        <p class="small muted mt-2 mb-0 text-center">
          By booking you agree that we may contact you on the number above about this appointment.
        </p>
      </form>

    <?php endif; ?>

    <div class="notice notice-info mt-3 mb-0">
      <?= icon('info') ?>
      <p>
        <strong>Already have a token?</strong>
        Look it up with your reference number on the
        <a href="booking.php">token lookup page</a>.
      </p>
    </div>

  </div>
</section>

<script src="<?= e(asset('assets/js/booking.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
