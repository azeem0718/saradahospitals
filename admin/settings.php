<?php
/**
 * Booking rules and the site announcement banner. Admin only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';

$user   = require_login(true);
$errors = [];

if (is_post()) {
    require_csrf();

    $window   = filter_input(INPUT_POST, 'booking_window_days', FILTER_VALIDATE_INT);
    $cutoff   = filter_input(INPUT_POST, 'booking_cutoff_minutes', FILTER_VALIDATE_INT);
    $freeDay  = filter_input(INPUT_POST, 'free_op_weekday', FILTER_VALIDATE_INT);
    $enabled  = isset($_POST['bookings_enabled']) ? '1' : '0';
    $heroPick = post('hero_style') === 'classic' ? 'classic' : 'slides';
    $announce = post('announcement');

    if ($window === false || $window < 1 || $window > 90) {
        $errors['booking_window_days'] = 'Choose between 1 and 90 days.';
    }
    if ($cutoff === false || $cutoff < 0 || $cutoff > 1440) {
        $errors['booking_cutoff_minutes'] = 'Choose between 0 and 1440 minutes.';
    }
    if ($freeDay === false || $freeDay < -1 || $freeDay > 6) {
        $errors['free_op_weekday'] = 'Choose a valid day.';
    }
    if (mb_strlen($announce) > 300) {
        $errors['announcement'] = 'Please keep the announcement under 300 characters.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ([
            'booking_window_days'    => (string) $window,
            'booking_cutoff_minutes' => (string) $cutoff,
            'free_op_weekday'        => (string) $freeDay,
            'bookings_enabled'       => $enabled,
            'hero_style'             => $heroPick,
            'announcement'           => $announce,
        ] as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        flash('Settings saved.');
        redirect('settings.php');
    }
}

$weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

$adminTitle    = 'Settings';
$adminSubtitle = 'Booking rules and the notice shown across the website.';
$adminNav      = 'settings';

require __DIR__ . '/_header.php';
?>

<div class="panel" style="max-width:720px">
  <div class="panel-body">
    <form method="post" action="settings.php">
      <?= csrf_field() ?>

      <div class="field">
        <label class="choice-label" style="cursor:pointer" for="hero_classic">
          <input type="checkbox" id="hero_classic" name="hero_style" value="classic"
                 style="width:20px;height:20px;margin-top:2px"
                 <?= setting('hero_style', 'slides') === 'classic' ? 'checked' : '' ?>>
          <span>
            <span class="choice-title">Use the plain home page banner</span>
            <span class="choice-sub">
              The home page normally opens with a slideshow that fades between
              five pictures of what the hospital treats. Tick this to go back to
              the original banner — the headline, the booking bar and the drawn
              picture of the building. Nothing else on the page changes.
            </span>
          </span>
        </label>
      </div>

      <div class="field">
        <label class="choice-label" style="cursor:pointer" for="bookings_enabled">
          <input type="checkbox" id="bookings_enabled" name="bookings_enabled" value="1"
                 style="width:20px;height:20px;margin-top:2px"
                 <?= setting('bookings_enabled', '1') === '1' ? 'checked' : '' ?>>
          <span>
            <span class="choice-title">Online booking is open</span>
            <span class="choice-sub">
              Untick to close online booking. The booking page then asks patients
              to call instead. Reception can still issue tokens.
            </span>
          </span>
        </label>
      </div>

      <hr>

      <div class="field-row cols-2">
        <div class="field">
          <label for="booking_window_days">Booking window (days)</label>
          <span class="hint">How far ahead patients may book, including today.</span>
          <input type="number" id="booking_window_days" name="booking_window_days"
                 min="1" max="90" required
                 value="<?= (int) setting_int('booking_window_days', 7) ?>">
          <?php if (isset($errors['booking_window_days'])): ?>
            <span class="error"><?= e($errors['booking_window_days']) ?></span>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="booking_cutoff_minutes">Booking cutoff (minutes)</label>
          <span class="hint">Online booking closes this long before a session ends.</span>
          <input type="number" id="booking_cutoff_minutes" name="booking_cutoff_minutes"
                 min="0" max="1440" required
                 value="<?= (int) setting_int('booking_cutoff_minutes', 60) ?>">
          <?php if (isset($errors['booking_cutoff_minutes'])): ?>
            <span class="error"><?= e($errors['booking_cutoff_minutes']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label for="free_op_weekday">Free OP day</label>
        <span class="hint">Marked on the booking calendar and the token slip.</span>
        <select id="free_op_weekday" name="free_op_weekday">
          <option value="-1" <?= setting_int('free_op_weekday', 5) === -1 ? 'selected' : '' ?>>
            No free OP day
          </option>
          <?php foreach ($weekdays as $n => $day): ?>
            <option value="<?= $n ?>" <?= setting_int('free_op_weekday', 5) === $n ? 'selected' : '' ?>>
              <?= e($day) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <hr>

      <div class="field">
        <label for="announcement">Website announcement</label>
        <span class="hint">
          Shown as a yellow notice on every page. Leave blank to hide it.
          Useful for holidays, doctor absence or camp days.
        </span>
        <textarea id="announcement" name="announcement" maxlength="300"><?= e(setting('announcement')) ?></textarea>
        <?php if (isset($errors['announcement'])): ?>
          <span class="error"><?= e($errors['announcement']) ?></span>
        <?php endif; ?>
      </div>

      <button class="btn btn-primary btn-lg" type="submit"><?= icon('check') ?> Save Settings</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
