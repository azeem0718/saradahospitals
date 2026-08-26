<?php
/**
 * First-run setup: creates the first admin account.
 *
 * Refuses to run once any user exists, so it cannot be used to add accounts
 * later. No password is ever stored in the repository — you choose it here.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

$alreadySetUp = false;
try {
    $alreadySetUp = user_count() > 0;
} catch (PDOException $e) {
    http_response_code(503);
    exit('Database tables are missing. Import sql/schema.sql and sql/seed.sql first.');
}

$errors = [];
$done   = false;

if (is_post() && !$alreadySetUp) {
    require_csrf();

    $username = strtolower(post('username'));
    $fullName = post('full_name');
    $pw       = $_POST['password']         ?? '';
    $pw2      = $_POST['password_confirm'] ?? '';

    if (preg_match('/^[a-z0-9_.-]{3,50}$/', $username) !== 1) {
        $errors['username'] = 'Use 3–50 characters: letters, numbers, dot, dash or underscore.';
    }
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors['full_name'] = 'Please enter the full name.';
    }
    if ($problem = password_problem((string) $pw)) {
        $errors['password'] = $problem;
    }
    if ($pw !== $pw2) {
        $errors['password_confirm'] = 'The two passwords do not match.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (username, password_hash, full_name, role, must_change_pw)
                 VALUES (?,?,?,"admin",0)'
            );
            $stmt->execute([$username, password_hash((string) $pw, PASSWORD_DEFAULT), $fullName]);
            $done = true;
        } catch (PDOException $e) {
            error_log('Setup failed: ' . $e->getMessage());
            $errors['form'] = 'Could not create the account. Please check the database and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup | Sarada Nursing Home</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/fonts.css?v=2">
<link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body style="background:var(--ground)">
<main class="section">
  <div class="wrap" style="max-width:520px">

    <div class="text-center mb-3">
      <?= logo_mark('brand-mark') ?>
      <h1 style="font-size:1.75rem;margin-top:1rem">Sarada Nursing Home</h1>
      <p class="muted">First-time setup</p>
    </div>

    <?php if ($alreadySetUp): ?>
      <div class="notice notice-info">
        <?= icon('lock') ?>
        <p>
          <strong>Setup is already complete.</strong>
          An administrator account exists, so this page is disabled.
          <a href="admin/login.php">Go to the staff login</a>.
        </p>
      </div>
      <div class="notice notice-warn mb-0">
        <?= icon('alert') ?>
        <p>
          <strong>Delete this file.</strong>
          For safety, remove <code>setup.php</code> from the server now that it is
          no longer needed.
        </p>
      </div>

    <?php elseif ($done): ?>
      <div class="notice notice-success">
        <?= icon('check-circle') ?>
        <p>
          <strong>Administrator account created.</strong>
          You can now sign in to the reception panel.
        </p>
      </div>
      <div class="notice notice-warn">
        <?= icon('alert') ?>
        <p>
          <strong>Delete setup.php now.</strong>
          It refuses to run again, but removing it from the server is cleaner.
        </p>
      </div>
      <a class="btn btn-primary btn-lg btn-block" href="admin/login.php">
        <?= icon('lock') ?> Go to Staff Login
      </a>

    <?php else: ?>
      <?php if (isset($errors['form'])): ?>
        <div class="notice notice-emergency">
          <?= icon('alert') ?><p><?= e($errors['form']) ?></p>
        </div>
      <?php endif; ?>

      <div class="notice notice-info">
        <?= icon('info') ?>
        <p>
          Create the first administrator account. This page switches itself off
          permanently once an account exists.
        </p>
      </div>

      <form class="form-card" method="post" action="setup.php" autocomplete="off">
        <?= csrf_field() ?>

        <div class="field">
          <label for="full_name">Your full name <span class="req">*</span></label>
          <input type="text" id="full_name" name="full_name" required maxlength="100"
                 value="<?= e(post('full_name')) ?>">
          <?php if (isset($errors['full_name'])): ?><span class="error"><?= e($errors['full_name']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="username">Username <span class="req">*</span></label>
          <span class="hint">Lowercase letters and numbers, for example <code>reception</code>.</span>
          <input type="text" id="username" name="username" required maxlength="50"
                 autocapitalize="off" spellcheck="false"
                 value="<?= e(post('username')) ?>">
          <?php if (isset($errors['username'])): ?><span class="error"><?= e($errors['username']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="password">Password <span class="req">*</span></label>
          <span class="hint">At least 10 characters, with letters and numbers.</span>
          <input type="password" id="password" name="password" required
                 autocomplete="new-password">
          <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="password_confirm">Confirm password <span class="req">*</span></label>
          <input type="password" id="password_confirm" name="password_confirm" required
                 autocomplete="new-password">
          <?php if (isset($errors['password_confirm'])): ?><span class="error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit">
          <?= icon('check') ?> Create Administrator Account
        </button>
      </form>
    <?php endif; ?>

  </div>
</main>
</body>
</html>
