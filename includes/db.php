<?php
/**
 * Database connection and settings access.
 */

declare(strict_types=1);

if (!defined('SNH_APP')) {
    define('SNH_APP', true);
}

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Site not configured. Copy includes/config.example.php to includes/config.php and fill in the database credentials.');
}
require_once $configFile;
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
        http_response_code(503);
        exit(DEBUG_MODE
            ? 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES)
            : 'The site is temporarily unavailable. Please call ' . HOSPITAL['mobile_display'] . '.');
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
