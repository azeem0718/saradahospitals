<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

// Already signed in? Go straight through.
if (current_user()) {
    redirect('index.php');
}

// No accounts yet — send them to first-run setup instead of a dead login.
try {
    if (user_count() === 0) {
        redirect('../setup.php');
    }
} catch (PDOException $e) {
    http_response_code(503);
    exit('Database tables are missing. Import sql/schema.sql and sql/seed.sql first.');
}

$error = null;
$next  = query('next');

// Only allow relative redirect targets, never an off-site URL.
if ($next === '' || !preg_match('#^/?[A-Za-z0-9_./-]*$#', $next) || str_contains($next, '..')) {
    $next = 'index.php';
}

if (is_post()) {
    require_csrf();
    $error = attempt_login(strtolower(post('username')), (string) ($_POST['password'] ?? ''));
    if ($error === null) {
        redirect($next);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Login | Sarada Nursing Home</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css', '../')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css', '../')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css', '../')) ?>">
</head>
<body class="admin">
<div class="login-wrap">
  <div class="login-card">

    <div class="text-center mb-3">
      <?= logo_mark('brand-mark') ?>
      <h1 style="font-size:1.5rem;margin-top:.9rem;margin-bottom:.2rem">Sarada Nursing Home</h1>
      <p class="muted">Reception &amp; Admin Panel</p>
    </div>

    <?php if ($error !== null): ?>
      <div class="notice notice-emergency">
        <?= icon('alert') ?><p><?= e($error) ?></p>
      </div>
    <?php endif; ?>

    <form class="form-card" method="post" action="login.php?next=<?= e(urlencode($next)) ?>">
      <?= csrf_field() ?>

      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus
               autocapitalize="off" spellcheck="false" autocomplete="username"
               value="<?= e(post('username')) ?>">
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password">
      </div>

      <button class="btn btn-primary btn-lg btn-block" type="submit">
        <?= icon('lock') ?> Sign In
      </button>
    </form>

    <p class="text-center small muted mt-2 mb-0">
      <a href="../index.php">Back to the website</a>
    </p>

  </div>
</div>
</body>
</html>
