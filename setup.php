<?php
/**
 * First-run installer.
 *
 * Three steps, all in the browser, so nothing has to be hand-edited on the
 * server and no credential ever has to be committed to the repository:
 *
 *   1. database credentials  → tested, then written to includes/config.php
 *   2. tables                → sql/schema.sql and sql/seed.sql imported
 *   3. administrator account → created with a password chosen here
 *
 * Once a user account exists this page refuses to do anything at all. That is
 * the lock: an installed site cannot be reconfigured through it.
 */

declare(strict_types=1);

// Tells includes/db.php not to turn an unconfigured site away — this page is
// how it gets configured. Must be defined before the chain is loaded.
define('SNH_INSTALLER', true);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/installer.php';
require_once __DIR__ . '/includes/icons.php';

// ---------------------------------------------------------------
// Work out which step we are on, without booting the application.
// ---------------------------------------------------------------
$step        = 1;
$installed   = false;
$pdo         = null;
$configVals  = null;

if (config_exists()) {
    require_once CONFIG_PATH;

    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $configVals = [DB_HOST, DB_NAME, DB_USER, DB_PASS];
        $probe = try_connection(...$configVals);

        if ($probe['ok']) {
            $pdo  = $probe['pdo'];
            $step = tables_present($pdo) ? 3 : 2;

            if ($step === 3) {
                $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                $installed = $count > 0;
            }
        }
    }
}

$errors  = [];
$notice  = null;
$manual  = null;   // config file contents to copy by hand, if writing failed
$done    = false;

// ---------------------------------------------------------------
// Handle each step's submission.
// ---------------------------------------------------------------
if (is_post() && !$installed) {
    require_csrf();
    $action = post('action');

    // --- Step 1: test credentials, then write config.php ---------------
    if ($action === 'config' && !config_exists()) {
        $host = post('db_host', 'localhost');
        $name = post('db_name');
        $user = post('db_user');
        $pass = (string) ($_POST['db_pass'] ?? '');
        $url  = rtrim(post('site_url'), '/');

        if ($host === '')                       { $errors['db_host'] = 'Enter the database host. On Hostinger this is localhost.'; }
        if ($name === '')                       { $errors['db_name'] = 'Enter the database name.'; }
        if ($user === '')                       { $errors['db_user'] = 'Enter the database username.'; }
        if ($pass === '')                       { $errors['db_pass'] = 'Enter the database password.'; }
        if (!filter_var($url, FILTER_VALIDATE_URL)) { $errors['site_url'] = 'Enter the full address of the site, including https://'; }

        if (!$errors) {
            $probe = try_connection($host, $name, $user, $pass);
            if (!$probe['ok']) {
                $errors['form'] = $probe['error'];
            } else {
                $contents = render_config($host, $name, $user, $pass, $url);
                $write    = write_config($contents);

                if ($write['ok']) {
                    redirect('setup.php');
                }
                // Could not write it — hand the file over to be created manually.
                $errors['form'] = $write['error'];
                $manual = $contents;
            }
        }
    }

    // --- Step 2: import the tables -------------------------------------
    if ($action === 'import' && $pdo instanceof PDO && !tables_present($pdo)) {
        $schema = import_sql_file($pdo, SCHEMA_PATH);
        if (!$schema['ok']) {
            $errors['form'] = $schema['error'];
        } else {
            $seed = import_sql_file($pdo, SEED_PATH);
            if (!$seed['ok']) {
                $errors['form'] = $seed['error'];
            } else {
                redirect('setup.php');
            }
        }
    }

    // --- Step 3: create the administrator ------------------------------
    if ($action === 'admin' && $pdo instanceof PDO && $step === 3 && !$installed) {
        $username = strtolower(post('username'));
        $fullName = post('full_name');
        $pw       = (string) ($_POST['password'] ?? '');
        $pw2      = (string) ($_POST['password_confirm'] ?? '');

        if (preg_match('/^[a-z0-9_.-]{3,50}$/', $username) !== 1) {
            $errors['username'] = 'Use 3–50 characters: letters, numbers, dot, dash or underscore.';
        }
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors['full_name'] = 'Please enter the full name.';
        }
        if (strlen($pw) < 10) {
            $errors['password'] = 'Please use at least 10 characters.';
        } elseif (preg_match('/[A-Za-z]/', $pw) !== 1 || preg_match('/\d/', $pw) !== 1) {
            $errors['password'] = 'Please include at least one letter and one number.';
        } elseif (defined('DB_PASS') && hash_equals(DB_PASS, $pw)) {
            $errors['password'] = 'Please choose a different password from your database password.';
        }
        if ($pw !== $pw2) {
            $errors['password_confirm'] = 'The two passwords do not match.';
        }

        if (!$errors) {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password_hash, full_name, role, must_change_pw)
                     VALUES (?,?,?,"admin",0)'
                );
                $stmt->execute([$username, password_hash($pw, PASSWORD_DEFAULT), $fullName]);
                $done = true;
                $installed = true;
            } catch (PDOException $e) {
                error_log('Setup failed: ' . $e->getMessage());
                $errors['form'] = $e->getCode() === '23000'
                    ? 'That username is already taken.'
                    : 'Could not create the account. Please try again.';
            }
        }
    }
}

$stepLabels = [1 => 'Database', 2 => 'Tables', 3 => 'Your login'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set up | Sarada Nursing Home</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/fonts.css?v=2">
<link rel="stylesheet" href="assets/css/style.css?v=2">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<style>
  body { background: var(--ground); display: grid; place-items: start center; padding: 2.5rem 1.25rem 4rem; }
  .setup { width: 100%; max-width: 560px; }
  .setup-head { text-align: center; margin-bottom: 1.75rem; }
  .setup-head .brand-mark { width: 54px; height: 54px; margin: 0 auto .9rem; }
  .setup-head h1 { font-size: 1.5rem; margin-bottom: .15rem; }
  .setup-head p { color: var(--ink-500); font-size: .93rem; margin: 0; }
  .steps { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
  .steps li { flex: 1; list-style: none; margin: 0; text-align: center; font-size: .78rem;
              padding-top: .55rem; border-top: 3px solid var(--rule); color: var(--ink-400); font-weight: 600; }
  .steps li.on   { border-top-color: var(--navy-800); color: var(--navy-900); }
  .steps li.past { border-top-color: var(--green-600); color: var(--green-700); }
  .manual { margin-top: 1.25rem; }
  .manual textarea { width: 100%; min-height: 260px; font-family: ui-monospace, Menlo, monospace;
                     font-size: .82rem; line-height: 1.55; }
</style>
</head>
<body>
<main class="setup">

  <div class="setup-head">
    <?= logo_mark('brand-mark') ?>
    <h1>Sarada Nursing Home</h1>
    <p>Website setup</p>
  </div>

  <?php if ($installed && !$done): ?>

    <div class="notice notice-info">
      <?= icon('lock') ?>
      <p>
        <strong>Setup is already complete.</strong>
        An administrator account exists, so this page is switched off.
        <a href="admin/login.php">Go to the staff login</a>.
      </p>
    </div>
    <div class="notice notice-warn mb-0">
      <?= icon('alert') ?>
      <p><strong>Delete setup.php from the server.</strong>
         It will not run again, but removing it is tidier.</p>
    </div>

  <?php elseif ($done): ?>

    <div class="notice notice-success">
      <?= icon('check-circle') ?>
      <p><strong>Your website is ready.</strong>
         The administrator account has been created and the site is live.</p>
    </div>
    <div class="notice notice-warn">
      <?= icon('alert') ?>
      <p><strong>Two last things.</strong>
         Delete <code>setup.php</code> from the server, and change your database
         password in hPanel if you have shared it anywhere. Setup will rewrite
         <code>includes/config.php</code> for you only while the site is
         uninstalled, so change the password there by hand afterwards.</p>
    </div>
    <div class="btn-row">
      <a class="btn btn-primary btn-lg" href="admin/login.php"><?= icon('lock') ?> Sign in to reception</a>
      <a class="btn btn-outline btn-lg" href="index.php">View the website</a>
    </div>

  <?php else: ?>

    <ol class="steps">
      <?php foreach ($stepLabels as $n => $label): ?>
        <li class="<?= $n === $step ? 'on' : ($n < $step ? 'past' : '') ?>">
          <?= $n < $step ? '&#10003; ' : '' ?><?= e($label) ?>
        </li>
      <?php endforeach; ?>
    </ol>

    <?php if (isset($errors['form'])): ?>
      <div class="notice notice-emergency">
        <?= icon('alert') ?><p><?= e($errors['form']) ?></p>
      </div>
    <?php endif; ?>

    <?php if ($manual !== null): ?>
      <div class="form-card manual">
        <h2 style="font-size:1.1rem">Create this file by hand</h2>
        <p class="small muted">
          Setup could not write the file itself. In File Manager, create
          <code>public_html/includes/config.php</code> and paste exactly this,
          then reload this page.
        </p>
        <textarea readonly onclick="this.select()"><?= e($manual) ?></textarea>
      </div>

    <?php elseif ($step === 1): ?>
      <div class="notice notice-info">
        <?= icon('info') ?>
        <p>
          Find these in hPanel, under <strong>Databases &rarr; MySQL Databases</strong>
          &mdash; Hostinger prefixes both the database name and the username with
          your account ID, so use the full prefixed values.
        </p>
      </div>

      <form class="form-card" method="post" action="setup.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="config">

        <div class="field">
          <label for="db_name">Database name <span class="req">*</span></label>
          <input type="text" id="db_name" name="db_name" required autofocus
                 placeholder="u123456_example" value="<?= e(post('db_name')) ?>">
          <?php if (isset($errors['db_name'])): ?><span class="error"><?= e($errors['db_name']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="db_user">Database username <span class="req">*</span></label>
          <input type="text" id="db_user" name="db_user" required
                 placeholder="u123456_example" value="<?= e(post('db_user')) ?>">
          <?php if (isset($errors['db_user'])): ?><span class="error"><?= e($errors['db_user']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="db_pass">Database password <span class="req">*</span></label>
          <input type="password" id="db_pass" name="db_pass" required>
          <?php if (isset($errors['db_pass'])): ?><span class="error"><?= e($errors['db_pass']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="db_host">Database host</label>
          <span class="hint">Leave as localhost unless Hostinger told you otherwise.</span>
          <input type="text" id="db_host" name="db_host" value="<?= e(post('db_host', 'localhost')) ?>">
          <?php if (isset($errors['db_host'])): ?><span class="error"><?= e($errors['db_host']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="site_url">Website address</label>
          <input type="text" id="site_url" name="site_url"
                 value="<?= e(post('site_url', guess_site_url())) ?>">
          <?php if (isset($errors['site_url'])): ?><span class="error"><?= e($errors['site_url']) ?></span><?php endif; ?>
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit">
          <?= icon('check') ?> Test and save
        </button>
        <p class="small muted mt-2 mb-0 text-center">
          The connection is tested before anything is saved.
        </p>
      </form>

    <?php elseif ($step === 2): ?>
      <div class="notice notice-info">
        <?= icon('info') ?>
        <p><strong>Connected to the database.</strong>
           It has no tables yet, so setup will create them and add the two
           doctors, their weekly sessions and the default settings.</p>
      </div>

      <form class="form-card" method="post" action="setup.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import">
        <button class="btn btn-primary btn-lg btn-block" type="submit">
          <?= icon('plus') ?> Create the tables
        </button>
        <p class="small muted mt-2 mb-0 text-center">
          Nothing existing is deleted. Equivalent to importing
          <code>sql/schema.sql</code> then <code>sql/seed.sql</code> in phpMyAdmin.
        </p>
      </form>

    <?php else: ?>
      <div class="notice notice-info">
        <?= icon('info') ?>
        <p><strong>The database is ready.</strong>
           Create the login your reception staff will use for the booking panel.</p>
      </div>

      <form class="form-card" method="post" action="setup.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin">

        <div class="field">
          <label for="full_name">Your full name <span class="req">*</span></label>
          <input type="text" id="full_name" name="full_name" required maxlength="100" autofocus
                 value="<?= e(post('full_name')) ?>">
          <?php if (isset($errors['full_name'])): ?><span class="error"><?= e($errors['full_name']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="username">Username <span class="req">*</span></label>
          <span class="hint">Lowercase, for example <code>reception</code>.</span>
          <input type="text" id="username" name="username" required maxlength="50"
                 autocapitalize="off" spellcheck="false" value="<?= e(post('username')) ?>">
          <?php if (isset($errors['username'])): ?><span class="error"><?= e($errors['username']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="password">Password <span class="req">*</span></label>
          <span class="hint">At least 10 characters, with letters and numbers.
                             Do not reuse the database password.</span>
          <input type="password" id="password" name="password" required autocomplete="new-password">
          <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="password_confirm">Confirm password <span class="req">*</span></label>
          <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
          <?php if (isset($errors['password_confirm'])): ?><span class="error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit">
          <?= icon('check') ?> Finish setup
        </button>
      </form>
    <?php endif; ?>

  <?php endif; ?>

</main>
</body>
</html>
