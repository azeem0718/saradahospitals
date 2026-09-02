<?php
/**
 * First-run installer helpers.
 *
 * These let the whole of setup happen in a browser — database credentials,
 * table import and the first login — so nobody has to hand-edit a PHP file on
 * the server. The alternative was committing credentials to the repository,
 * which for a public repo means publishing them.
 *
 * Everything here is reachable ONLY while the site is unconfigured. Once
 * config.php exists AND a user account exists, setup.php refuses to run and
 * none of this can be called.
 */

declare(strict_types=1);

const CONFIG_PATH  = __DIR__ . '/config.php';
const SCHEMA_PATH  = __DIR__ . '/../sql/schema.sql';
const SEED_PATH    = __DIR__ . '/../sql/seed.sql';

/** Has the credentials file been written yet? */
function config_exists(): bool
{
    return is_file(CONFIG_PATH);
}

/**
 * Open a connection with candidate credentials, without touching the
 * application's own connection. Returns the PDO handle or an error message.
 *
 * @return array{ok:bool, pdo?:PDO, error?:string}
 */
function try_connection(string $host, string $name, string $user, string $pass): array
{
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name),
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 8,
            ]
        );
        return ['ok' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => friendly_db_error($e)];
    }
}

/** Turn a driver error into something a non-developer can act on. */
function friendly_db_error(PDOException $e): string
{
    $raw = $e->getMessage();

    return match (true) {
        str_contains($raw, '1045') => 'The database username or password was not accepted. Check both in hPanel → Databases → MySQL Databases, and remember Hostinger prefixes the username with your account ID.',
        str_contains($raw, '1049') => 'That database name does not exist. Check the exact name in hPanel, including the account ID prefix.',
        // 1044 is what MySQL returns when the name exists but this user has no
        // grant on it — indistinguishable from a typo, from the user's side.
        str_contains($raw, '1044') => 'That database exists but this user has no access to it. In hPanel, check the database name, and that the user has all privileges on it.',
        str_contains($raw, '2002'),
        str_contains($raw, '2005') => 'The database server could not be reached at that host. On Hostinger the host is almost always localhost.',
        default                    => 'The database could not be reached. Please check the details and try again.',
    };
}

/** Do the application's tables already exist in this database? */
function tables_present(PDO $pdo): bool
{
    try {
        $need = ['users', 'doctors', 'doctor_sessions', 'bookings', 'settings'];
        $have = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        return count(array_diff($need, $have)) === 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Render the credentials file.
 *
 * Values go through var_export, so a password containing a quote, a backslash
 * or a dollar sign cannot break out of the string or be interpolated.
 */
function render_config(string $host, string $name, string $user, string $pass, string $siteUrl): string
{
    $lines = [
        '<?php',
        '/**',
        ' * Sarada Nursing Home — server configuration.',
        ' *',
        ' * Written by setup.php. This file is git-ignored: it is never pushed to',
        ' * GitHub and is never overwritten by a deployment.',
        ' */',
        '',
        '// --- Database (hPanel > Databases > MySQL Databases) ---',
        sprintf('define(%s, %s);', var_export('DB_HOST', true), var_export($host, true)),
        sprintf('define(%s, %s);', var_export('DB_NAME', true), var_export($name, true)),
        sprintf('define(%s, %s);', var_export('DB_USER', true), var_export($user, true)),
        sprintf('define(%s, %s);', var_export('DB_PASS', true), var_export($pass, true)),
        '',
        '// --- Site ---',
        sprintf('define(%s, %s);', var_export('SITE_URL', true), var_export($siteUrl, true)),
        '',
        '// Keep this false on the live site. When true, database errors print to the page.',
        "define('DEBUG_MODE', false);",
        '',
    ];
    return implode("\n", $lines);
}

/**
 * Write the credentials file.
 *
 * @return array{ok:bool, error?:string}
 */
function write_config(string $contents): array
{
    $dir = dirname(CONFIG_PATH);

    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'The includes folder is not writable, so the file could not be saved automatically.'];
    }
    if (file_put_contents(CONFIG_PATH, $contents, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'The file could not be written.'];
    }

    // Readable by the web server, closed to everyone else.
    @chmod(CONFIG_PATH, 0640);

    return ['ok' => true];
}

/**
 * Run a .sql file through the connection.
 *
 * Our schema and seed files are plain statements with no stored routines and
 * no semicolons inside string literals, so splitting on ";\n" is sufficient
 * and avoids depending on a MySQL client binary being available.
 *
 * @return array{ok:bool, ran?:int, error?:string}
 */
function import_sql_file(PDO $pdo, string $path): array
{
    if (!is_file($path)) {
        return ['ok' => false, 'error' => 'Could not find ' . basename($path) . ' in the sql folder.'];
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        return ['ok' => false, 'error' => 'Could not read ' . basename($path) . '.'];
    }

    // Strip full-line comments so they cannot be mistaken for statements.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;

    $statements = array_filter(
        array_map('trim', preg_split('/;\s*[\r\n]/', $sql) ?: []),
        static fn(string $s): bool => $s !== ''
    );

    $ran = 0;
    try {
        foreach ($statements as $statement) {
            $pdo->exec($statement);
            $ran++;
        }
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Import failed on statement ' . ($ran + 1) . ': ' . $e->getMessage()];
    }

    return ['ok' => true, 'ran' => $ran];
}

/** Best guess at the site's own address, used to prefill SITE_URL. */
function guess_site_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    return ($https ? 'https://' : 'http://') . $host . $dir;
}
