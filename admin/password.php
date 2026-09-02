<?php
/**
 * Change your own password. Forced on first login for a seeded account.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

$user   = require_login();
$forced = query('forced') === '1' || (bool) $user['must_change_pw'];
$errors = [];

if (is_post()) {
    require_csrf();

    $current = (string) ($_POST['current_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!password_verify($current, $user['password_hash'])) {
        $errors['current_password'] = 'That is not your current password.';
    }
    if ($problem = password_problem($new)) {
        $errors['new_password'] = $problem;
    }
    if ($new !== $confirm) {
        $errors['confirm_password'] = 'The two passwords do not match.';
    }
    if (!$errors && $new === $current) {
        $errors['new_password'] = 'Please choose a different password from your current one.';
    }

    if (!$errors) {
        set_password((int) $user['id'], $new);
        flash('Your password has been changed.');
        redirect('index.php');
    }
}

$adminTitle    = 'Change Password';
$adminSubtitle = $forced ? 'Please set your own password before continuing.' : '';
$adminNav      = '';

require __DIR__ . '/_header.php';
?>

<?php if ($forced): ?>
  <div class="notice notice-warn" style="max-width:560px">
    <?= icon('lock') ?>
    <p>
      <strong>Set a password before you continue.</strong>
      Your account is still using the password it was created with.
    </p>
  </div>
<?php endif; ?>

<div class="panel" style="max-width:560px">
  <div class="panel-body">
    <form method="post" action="password.php" autocomplete="off">
      <?= csrf_field() ?>

      <div class="field">
        <label for="current_password">Current password <span class="req">*</span></label>
        <input type="password" id="current_password" name="current_password" required
               autocomplete="current-password">
        <?php if (isset($errors['current_password'])): ?>
          <span class="error"><?= e($errors['current_password']) ?></span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="new_password">New password <span class="req">*</span></label>
        <span class="hint">At least 10 characters, with letters and numbers.</span>
        <input type="password" id="new_password" name="new_password" required
               autocomplete="new-password">
        <?php if (isset($errors['new_password'])): ?>
          <span class="error"><?= e($errors['new_password']) ?></span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="confirm_password">Confirm new password <span class="req">*</span></label>
        <input type="password" id="confirm_password" name="confirm_password" required
               autocomplete="new-password">
        <?php if (isset($errors['confirm_password'])): ?>
          <span class="error"><?= e($errors['confirm_password']) ?></span>
        <?php endif; ?>
      </div>

      <button class="btn btn-primary btn-lg btn-block" type="submit">
        <?= icon('lock') ?> Change Password
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
