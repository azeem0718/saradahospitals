<?php
/**
 * Staff authentication for the admin panel.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const AUTH_IDLE_TIMEOUT = 7200; // 2 hours of inactivity ends a session

/** Number of staff accounts that exist. Used by setup.php. */
function user_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function find_user_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function find_user(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Verify credentials and start an authenticated session.
 * Returns null on success, or a message safe to show the user.
 */
function attempt_login(string $username, string $password): ?string
{
    $user = find_user_by_username($username);

    // Always run a hash comparison so a missing username and a wrong password
    // take the same time and cannot be told apart.
    $hash = $user['password_hash']
        ?? '$2y$12$iZPW1MRUEmqb5ijOPA2Dn.vxavtI0dORhjJjmnwgCtBkTJ/tliYNq';

    if (!password_verify($password, $hash) || !$user) {
        return 'Incorrect username or password.';
    }

    // Upgrade the stored hash if PHP's default cost has since changed.
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }

    start_session();
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['last_seen']  = time();
    $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    return null;
}

function logout(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** The signed-in user, or null. Enforces idle timeout and UA pinning. */
function current_user(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user;
    }

    start_session();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (($_SESSION['user_agent'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
        logout();
        return null;
    }

    if (time() - (int) ($_SESSION['last_seen'] ?? 0) > AUTH_IDLE_TIMEOUT) {
        logout();
        return null;
    }
    $_SESSION['last_seen'] = time();

    $found = find_user((int) $_SESSION['user_id']);
    if (!$found || !$found['is_active']) {
        logout();
        return null;
    }

    return $user = $found;
}

/**
 * Gate an admin page. Redirects to login, or to the forced password change.
 * $adminOnly restricts the page to the admin role.
 */
function require_login(bool $adminOnly = false): array
{
    $user = current_user();

    if (!$user) {
        $target = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect('login.php?next=' . urlencode($target));
    }

    $onPasswordPage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'password.php';
    if ($user['must_change_pw'] && !$onPasswordPage) {
        redirect('password.php?forced=1');
    }

    if ($adminOnly && $user['role'] !== 'admin') {
        http_response_code(403);
        exit('You do not have permission to open that page.');
    }

    return $user;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/**
 * Reject a password that is too weak to protect patient contact details.
 * Returns null when acceptable.
 */
function password_problem(string $password): ?string
{
    if (strlen($password) < 10) {
        return 'Please use at least 10 characters.';
    }
    if (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
        return 'Please include at least one letter and one number.';
    }
    if (in_array(strtolower($password), ['password12', 'password123', 'admin12345', '1234567890'], true)) {
        return 'That password is too easy to guess. Please choose another.';
    }
    return null;
}

function set_password(int $userId, string $password): void
{
    $stmt = db()->prepare(
        'UPDATE users SET password_hash = ?, must_change_pw = 0 WHERE id = ?'
    );
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
}

/** One-time flash message across a redirect. */
function flash(?string $message = null, string $type = 'success'): ?array
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
