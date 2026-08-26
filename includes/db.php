<?php
/**
 * Database connection and settings access.
 */

declare(strict_types=1);

if (!defined('SNH_APP')) {
    define('SNH_APP', true);
}

require_once __DIR__ . '/not-configured.php';

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    render_not_configured(
        'The file includes/config.php has not been created yet, so the site does not know how to reach its database.'
    );
}
require_once $configFile;

// A half-filled config is as broken as a missing one, and fails much later
// and less obviously, so check it here.
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SITE_URL'] as $required) {
    if (!defined($required)) {
        render_not_configured(
            'includes/config.php is missing the ' . $required . ' setting.',
            'Compare it against includes/config.example.php — every define() in that file must be present.'
        );
    }
}
if (DB_NAME === 'your_database_name' || DB_USER === 'your_database_user') {
    render_not_configured(
        'includes/config.php still contains the example placeholder values.',
        'Replace your_database_name and your_database_user with the real credentials from hPanel.'
    );
}

// Optional, and safe to assume off: an older config.php may predate it.
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

require_once __DIR__ . '/site.php';

if (DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

date_default_timezone_set('Asia/Kolkata');

/**
 * Shared PDO handle. Throws on error, returns real types, no emulation.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        render_not_configured(
            'The database could not be reached.',
            DEBUG_MODE ? $e->getMessage() : '',
            true
        );
    }

    // Keep MySQL's clock aligned with PHP's so DATE(NOW()) matches "today".
    $pdo->exec("SET time_zone = '+05:30'");

    return $pdo;
}

/**
 * Read a setting, falling back to $default when absent.
 * Settings are loaded once per request.
 */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log('Settings load failed: ' . $e->getMessage());
        }
    }
    return $cache[$key] ?? $default;
}

function setting_int(string $key, int $default = 0): int
{
    $value = setting($key, (string) $default);
    return is_numeric($value) ? (int) $value : $default;
}
