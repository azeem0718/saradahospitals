<?php
/**
 * Shared helpers: escaping, sessions, CSRF, validation, booking logic.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------
// Output escaping
// ---------------------------------------------------------------

/** Escape for HTML text and attribute contexts. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape a value for use inside a JS string / JSON block. */
function json_attr(mixed $value): string
{
    return htmlspecialchars(
        json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'null',
        ENT_QUOTES,
        'UTF-8'
    );
}

// ---------------------------------------------------------------
// Sessions
// ---------------------------------------------------------------

/** Start a hardened session. Safe to call repeatedly. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SNHSESS');
    session_start();
}

// ---------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Constant-time CSRF check. */
function csrf_verify(?string $token): bool
{
    start_session();
    $expected = $_SESSION['csrf_token'] ?? '';
    return $expected !== ''
        && is_string($token)
        && hash_equals($expected, $token);
}

/** Abort the request unless a valid CSRF token was posted. */
function require_csrf(): void
{
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Your session expired. Please go back, reload the page and try again.');
    }
}

// ---------------------------------------------------------------
// Request helpers
// ---------------------------------------------------------------

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

/** Client IP packed to binary, for rate limiting. Null when unparseable. */
function client_ip_binary(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $packed = @inet_pton($ip);
    return $packed === false ? null : $packed;
}

// ---------------------------------------------------------------
// Validation
// ---------------------------------------------------------------

/**
 * Normalise an Indian mobile number to 10 digits.
 * Accepts spaces, dashes, +91 and a leading 0. Returns null if invalid.
 */
function normalise_phone(string $raw): ?string
{
    $digits = preg_replace('/\D+/', '', $raw) ?? '';

    if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
        $digits = substr($digits, 2);
    } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    // Indian mobile numbers are 10 digits starting 6-9.
    return preg_match('/^[6-9]\d{9}$/', $digits) === 1 ? $digits : null;
}

/** Validate a Y-m-d date string strictly (rejects 2026-02-31). */
function valid_date(string $value): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

// ---------------------------------------------------------------
// Formatting
// ---------------------------------------------------------------

function money(int $amount): string
{
    return '₹' . number_format($amount);
}

function format_time(string $sqlTime): string
{
    $t = DateTimeImmutable::createFromFormat('H:i:s', $sqlTime)
        ?: DateTimeImmutable::createFromFormat('H:i', $sqlTime);
    return $t ? $t->format('g:i A') : $sqlTime;
}

function format_date(string $sqlDate): string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $sqlDate);
    return $d ? $d->format('D, j M Y') : $sqlDate;
}

function session_label(string $session): string
{
    return $session === 'morning' ? 'Morning' : 'Evening';
}

function status_label(string $status): string
{
    return match ($status) {
        'booked'    => 'Booked',
        'arrived'   => 'Arrived',
        'completed' => 'Completed',
        'no_show'   => 'No Show',
        'cancelled' => 'Cancelled',
        default     => ucfirst($status),
    };
}

/** True when the given date is the free-consultation weekday. */
function is_free_op_day(string $sqlDate): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $sqlDate);
    return $d !== false && (int) $d->format('w') === setting_int('free_op_weekday', 5);
}

// ---------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------

/*
 * Start the session now, while nothing has been sent to the browser yet.
 *
 * CSRF tokens are emitted by csrf_field() in the middle of a page, long after
 * the HTML head has gone out. PHP refuses to start a session once headers are
 * sent, so a lazily-started session would silently fail there and every form
 * submission would be rejected as expired. Starting here — every page requires
 * this file before printing anything — keeps that from happening.
 */
start_session();
