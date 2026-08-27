<?php
/**
 * Database backup — a pure-PHP mysqldump.
 *
 * Shared hosting often disables exec(), so no shelling out: this walks the
 * schema over PDO and writes a gzipped SQL file that mysql/phpMyAdmin can
 * import as-is. Backups land in storage/backups/, which .htaccess denies to
 * the web; the admin panel streams them to a signed-in admin instead.
 *
 * Meant to run nightly from hPanel > Advanced > Cron Jobs:
 *
 *     /usr/bin/php /home/USER/domains/saradahospitals.com/public_html/tools/backup.php
 *
 * If only URL-based cron is available, define BACKUP_KEY in config.php and
 * point the cron at tools/backup.php?key=THAT-KEY — without the key a web
 * request is refused, so strangers cannot make the server gzip the database
 * all day. Backups older than 14 days are pruned each run.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

const BACKUP_KEEP_DAYS = 14;

/** Where backups live: outside no directory, but denied to the web. */
function backup_dir(): string
{
    return dirname(__DIR__) . '/storage/backups';
}

/**
 * Write a complete gzipped dump. Returns the file's basename.
 */
function backup_database(): string
{
    $dir = backup_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Backup directory could not be created.');
    }

    $pdo  = db();
    $name = 'backup-' . date('Ymd-His') . '.sql.gz';
    $tmp  = $dir . '/.' . $name . '.part';

    $gz = gzopen($tmp, 'wb9');
    if ($gz === false) {
        throw new RuntimeException('Could not open the backup file for writing.');
    }

    try {
        gzwrite($gz, "-- Sarada Nursing Home database backup\n");
        gzwrite($gz, '-- ' . date('c') . "\n\n");
        gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $quoted = '`' . str_replace('`', '``', (string) $table) . '`';

            $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch();
            gzwrite($gz, "DROP TABLE IF EXISTS {$quoted};\n");
            gzwrite($gz, $create['Create Table'] . ";\n\n");

            $batch = [];
            foreach ($pdo->query('SELECT * FROM ' . $quoted, PDO::FETCH_NUM) as $row) {
                $values = array_map(static fn ($v) => match (true) {
                    $v === null     => 'NULL',
                    is_int($v)      => (string) $v,
                    default         => $pdo->quote((string) $v),
                }, $row);
                $batch[] = '(' . implode(',', $values) . ')';

                if (count($batch) === 200) {
                    gzwrite($gz, "INSERT INTO {$quoted} VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                gzwrite($gz, "INSERT INTO {$quoted} VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
    } finally {
        gzclose($gz);
    }

    if (!rename($tmp, $dir . '/' . $name)) {
        @unlink($tmp);
        throw new RuntimeException('Could not finalise the backup file.');
    }

    return $name;
}

/** Delete backups older than the retention window. Returns how many went. */
function backup_prune(int $keepDays = BACKUP_KEEP_DAYS): int
{
    $cutoff = time() - $keepDays * 86400;
    $gone   = 0;
    foreach (glob(backup_dir() . '/backup-*.sql.gz') ?: [] as $file) {
        if (filemtime($file) < $cutoff && @unlink($file)) {
            $gone++;
        }
    }
    return $gone;
}

/** Existing backups, newest first: [name, bytes, mtime]. */
function backup_list(): array
{
    $out = [];
    foreach (glob(backup_dir() . '/backup-*.sql.gz') ?: [] as $file) {
        $out[] = ['name' => basename($file), 'bytes' => filesize($file), 'mtime' => filemtime($file)];
    }
    usort($out, static fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $out;
}

// ---------------------------------------------------------------------------
// Entry point — only when called directly (CLI or URL cron), not when the
// admin panel includes this file for its functions.
// ---------------------------------------------------------------------------
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli') {
        $key = defined('BACKUP_KEY') ? (string) BACKUP_KEY : '';
        if ($key === '' || !hash_equals($key, (string) ($_GET['key'] ?? ''))) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit("Forbidden. Run from the command line, or define BACKUP_KEY in config.php and pass ?key=.\n");
        }
        header('Content-Type: text/plain; charset=utf-8');
    }

    try {
        $name   = backup_database();
        $pruned = backup_prune();
        $size   = round(filesize(backup_dir() . '/' . $name) / 1024);
        echo "OK {$name} ({$size} KB), pruned {$pruned} old backup(s)\n";
    } catch (Throwable $e) {
        // The message goes to the cron log / response; details to error_log.
        error_log('Backup failed: ' . $e->getMessage());
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
        }
        echo "FAILED - see the server error log\n";
        exit(1);
    }
}
