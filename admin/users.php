<?php
/**
 * Staff accounts. Admin only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

$user   = require_login(true);
$errors = [];

if (is_post()) {
    require_csrf();
    $action = post('action');
    $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

    // An admin must never be able to lock themselves out.
    if ($id === (int) $user['id'] && in_array($action, ['deactivate', 'delete'], true)) {
        flash('You cannot deactivate or delete your own account.', 'error');
        redirect('users.php');
    }

    if ($action === 'create') {
        $username = strtolower(post('username'));
        $fullName = post('full_name');
        $role     = post('role');
        $pw       = (string) ($_POST['password'] ?? '');

        if (preg_match('/^[a-z0-9_.-]{3,50}$/', $username) !== 1) {
            $errors['username'] = 'Use 3–50 characters: letters, numbers, dot, dash or underscore.';
        }
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors['full_name'] = 'Please enter the full name.';
        }
        if (!in_array($role, ['admin', 'reception'], true)) {
            $errors['role'] = 'Choose a role.';
        }
        if ($problem = password_problem($pw)) {
            $errors['password'] = $problem;
        }

        if (!$errors) {
            try {
                db()->prepare(
                    'INSERT INTO users (username, password_hash, full_name, role, must_change_pw)
                     VALUES (?,?,?,?,1)'
                )->execute([$username, password_hash($pw, PASSWORD_DEFAULT), $fullName, $role]);

                flash('Account created. ' . $fullName . ' will be asked to set their own password at first sign-in.');
                redirect('users.php');
            } catch (PDOException $e) {
                $errors['username'] = $e->getCode() === '23000'
                    ? 'That username is already taken.'
                    : 'Could not create the account.';
                if ($e->getCode() !== '23000') {
                    error_log('User create failed: ' . $e->getMessage());
                }
            }
        }
    } elseif (in_array($action, ['activate', 'deactivate'], true) && $id > 0) {
        db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')
            ->execute([$action === 'activate' ? 1 : 0, $id]);
        flash('Account ' . ($action === 'activate' ? 'reactivated' : 'deactivated') . '.');
        redirect('users.php');
    } elseif ($action === 'reset' && $id > 0) {
        $pw = (string) ($_POST['new_password'] ?? '');
        if ($problem = password_problem($pw)) {
            flash($problem, 'error');
        } else {
            db()->prepare('UPDATE users SET password_hash = ?, must_change_pw = 1 WHERE id = ?')
                ->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            flash('Password reset. They will be asked to choose their own at next sign-in.');
        }
        redirect('users.php');
    }
}

$staff = db()->query('SELECT * FROM users ORDER BY role, full_name')->fetchAll();

$adminTitle    = 'Staff Accounts';
$adminSubtitle = 'Who can sign in to this panel.';
$adminNav      = 'users';

require __DIR__ . '/_header.php';
?>

<div class="panel">
  <div class="panel-head"><h2>Current staff</h2></div>
  <div class="panel-body flush">
    <div class="table-wrap">
      <table class="admin-table" style="min-width:700px">
        <thead>
          <tr>
            <th scope="col">Name</th>
            <th scope="col">Username</th>
            <th scope="col">Role</th>
            <th scope="col">Last sign-in</th>
            <th scope="col">Status</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($staff as $s): ?>
            <tr<?= $s['is_active'] ? '' : ' style="opacity:.55"' ?>>
              <td>
                <span class="patient-name"><?= e($s['full_name']) ?></span>
                <?php if ((int) $s['id'] === (int) $user['id']): ?>
                  <span class="patient-meta">This is you</span>
                <?php endif; ?>
              </td>
              <td><code><?= e($s['username']) ?></code></td>
              <td><span class="pill pill-navy"><?= e(ucfirst($s['role'])) ?></span></td>
              <td class="muted">
                <?= $s['last_login_at']
                      ? e((new DateTimeImmutable($s['last_login_at']))->format('j M Y, g:i A'))
                      : 'Never' ?>
              </td>
              <td>
                <?php if (!$s['is_active']): ?>
                  <span class="pill status-cancelled">Inactive</span>
                <?php elseif ($s['must_change_pw']): ?>
                  <span class="pill status-arrived">Must set password</span>
                <?php else: ?>
                  <span class="pill status-completed">Active</span>
                <?php endif; ?>
              </td>
              <td class="actions">
                <?php if ((int) $s['id'] !== (int) $user['id']): ?>
                  <form method="post" action="users.php"
                        data-confirm="<?= $s['is_active'] ? 'Deactivate this account?' : 'Reactivate this account?' ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $s['is_active'] ? 'deactivate' : 'activate' ?>">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button class="btn-icon <?= $s['is_active'] ? 'danger' : 'good' ?>" type="submit"
                            title="<?= $s['is_active'] ? 'Deactivate' : 'Reactivate' ?>">
                      <?= icon($s['is_active'] ? 'close' : 'check') ?>
                    </button>
                  </form>
                <?php else: ?>
                  <a class="btn-icon" href="password.php" title="Change your password"><?= icon('lock') ?></a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="panel" style="max-width:600px">
  <div class="panel-head"><h2>Add a staff account</h2></div>
  <div class="panel-body">
    <form method="post" action="users.php" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="field">
        <label for="full_name">Full name <span class="req">*</span></label>
        <input type="text" id="full_name" name="full_name" required maxlength="100"
               value="<?= e(post('full_name')) ?>">
        <?php if (isset($errors['full_name'])): ?><span class="error"><?= e($errors['full_name']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="username">Username <span class="req">*</span></label>
        <input type="text" id="username" name="username" required maxlength="50"
               autocapitalize="off" spellcheck="false" value="<?= e(post('username')) ?>">
        <?php if (isset($errors['username'])): ?><span class="error"><?= e($errors['username']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="role">Role <span class="req">*</span></label>
        <span class="hint">
          Reception can manage bookings, the schedule and leave.
          Admin can additionally manage staff and settings.
        </span>
        <select id="role" name="role" required>
          <option value="reception">Reception</option>
          <option value="admin">Admin</option>
        </select>
        <?php if (isset($errors['role'])): ?><span class="error"><?= e($errors['role']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="password">Temporary password <span class="req">*</span></label>
        <span class="hint">
          Give this to them in person. They must change it the first time they sign in.
        </span>
        <input type="text" id="password" name="password" required autocomplete="off">
        <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
      </div>

      <button class="btn btn-primary btn-block" type="submit"><?= icon('plus') ?> Create Account</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
