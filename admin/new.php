<?php
/**
 * Reception books a walk-in patient. Same engine as the public form, but
 * reception may book past the online cutoff and past the public window.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';

$user    = require_login();
$doctors = get_doctors();
$errors  = [];

$defaultDate = query('date');
if (!valid_date($defaultDate)) {
    $defaultDate = date('Y-m-d');
}

$values = [
    'doctor_id'    => (string) ($doctors[0]['id'] ?? ''),
    'booking_date' => $defaultDate,
    'session'      => '',
    'patient_name' => '',
    'patient_age'  => '',
    'patient_sex'  => '',
    'phone'        => '',
    'town'         => '',
    'reason'       => '',
];

if (is_post()) {
    require_csrf();

    foreach (array_keys($values) as $key) {
        $values[$key] = post($key);
    }

    $doctorId = (int) $values['doctor_id'];
    if (!get_doctor($doctorId)) {
        $errors['doctor_id'] = 'Please choose a doctor.';
    }

    if (!valid_date($values['booking_date'])) {
        $errors['booking_date'] = 'Please enter a valid date.';
    } elseif ($values['booking_date'] < date('Y-m-d')) {
        $errors['booking_date'] = 'You cannot book a token for a past date.';
    }

    if (!in_array($values['session'], SESSIONS, true)) {
        $errors['session'] = 'Please choose a session.';
    }

    if (mb_strlen($values['patient_name']) < 2) {
        $errors['patient_name'] = 'Please enter the patient\'s name.';
    }

    $age = filter_var($values['patient_age'], FILTER_VALIDATE_INT);
    if ($age === false || $age < 0 || $age > 120) {
        $errors['patient_age'] = 'Enter an age between 0 and 120.';
    }

    if (!in_array($values['patient_sex'], ['male', 'female', 'other'], true)) {
        $errors['patient_sex'] = 'Please select the sex.';
    }

    $phone = normalise_phone($values['phone']);
    if ($phone === null) {
        $errors['phone'] = 'Enter a valid 10-digit mobile number.';
    }

    if (!$errors) {
        $result = create_booking([
            'doctor_id'    => $doctorId,
            'booking_date' => $values['booking_date'],
            'session'      => $values['session'],
            'patient_name' => $values['patient_name'],
            'patient_age'  => $age,
            'patient_sex'  => $values['patient_sex'],
            'phone'        => $phone,
            'town'         => $values['town'],
            'reason'       => $values['reason'],
        ], 'reception');

        if ($result['ok']) {
            $b = $result['booking'];
            flash(sprintf(
                'Token %d issued to %s for the %s session on %s. Reference %s.',
                (int) $b['token_no'],
                $b['patient_name'],
                strtolower(session_label($b['session'])),
                format_date($b['booking_date']),
                $b['reference']
            ));
            redirect('index.php?date=' . urlencode($b['booking_date']));
        }
        $errors['form'] = $result['error'];
    }
}

$adminTitle    = 'New Token';
$adminSubtitle = 'Issue a token for a patient at the desk or on the phone.';
$adminNav      = 'new';

require __DIR__ . '/_header.php';
?>

<?php if (isset($errors['form'])): ?>
  <div class="notice notice-emergency"><?= icon('alert') ?><p><?= e($errors['form']) ?></p></div>
<?php endif; ?>

<div class="panel" style="max-width:760px">
  <div class="panel-body">
    <form method="post" action="new.php" novalidate>
      <?= csrf_field() ?>

      <div class="field-row cols-2">
        <div class="field">
          <label for="doctor_id">Doctor <span class="req">*</span></label>
          <select id="doctor_id" name="doctor_id" required>
            <?php foreach ($doctors as $doc): ?>
              <option value="<?= (int) $doc['id'] ?>" <?= $values['doctor_id'] === (string) $doc['id'] ? 'selected' : '' ?>>
                <?= e($doc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['doctor_id'])): ?><span class="error"><?= e($errors['doctor_id']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="booking_date">Date <span class="req">*</span></label>
          <input type="date" id="booking_date" name="booking_date" required
                 min="<?= e(date('Y-m-d')) ?>" value="<?= e($values['booking_date']) ?>">
          <?php if (isset($errors['booking_date'])): ?><span class="error"><?= e($errors['booking_date']) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label for="session">Session <span class="req">*</span></label>
        <select id="session" name="session" required>
          <option value="">Choose a session</option>
          <?php foreach (SESSIONS as $s): ?>
            <option value="<?= e($s) ?>" <?= $values['session'] === $s ? 'selected' : '' ?>>
              <?= e(session_label($s)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['session'])): ?><span class="error"><?= e($errors['session']) ?></span><?php endif; ?>
      </div>

      <hr>

      <div class="field">
        <label for="patient_name">Patient name <span class="req">*</span></label>
        <input type="text" id="patient_name" name="patient_name" required maxlength="120"
               value="<?= e($values['patient_name']) ?>">
        <?php if (isset($errors['patient_name'])): ?><span class="error"><?= e($errors['patient_name']) ?></span><?php endif; ?>
      </div>

      <div class="field-row cols-3">
        <div class="field">
          <label for="patient_age">Age <span class="req">*</span></label>
          <input type="number" id="patient_age" name="patient_age" required min="0" max="120"
                 value="<?= e($values['patient_age']) ?>">
          <?php if (isset($errors['patient_age'])): ?><span class="error"><?= e($errors['patient_age']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="patient_sex">Sex <span class="req">*</span></label>
          <select id="patient_sex" name="patient_sex" required>
            <option value="">Choose</option>
            <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l): ?>
              <option value="<?= e($v) ?>" <?= $values['patient_sex'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['patient_sex'])): ?><span class="error"><?= e($errors['patient_sex']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="phone">Mobile <span class="req">*</span></label>
          <input type="tel" id="phone" name="phone" required maxlength="15" inputmode="tel"
                 value="<?= e($values['phone']) ?>">
          <?php if (isset($errors['phone'])): ?><span class="error"><?= e($errors['phone']) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label for="town">Town or village</label>
        <input type="text" id="town" name="town" maxlength="100" value="<?= e($values['town']) ?>">
      </div>

      <div class="field">
        <label for="reason">Reason for visit</label>
        <textarea id="reason" name="reason" maxlength="500"><?= e($values['reason']) ?></textarea>
      </div>

      <div class="btn-row">
        <button class="btn btn-primary btn-lg" type="submit"><?= icon('ticket') ?> Issue Token</button>
        <a class="btn btn-outline btn-lg" href="index.php">Cancel</a>
      </div>
    </form>
  </div>
</div>

<div class="notice notice-info" style="max-width:760px">
  <?= icon('info') ?>
  <p>
    Tokens issued here are not limited by the online booking cutoff, so reception
    can always add a walk-in patient. The session token cap still applies.
  </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
