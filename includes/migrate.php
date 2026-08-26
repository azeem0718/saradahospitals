<?php
/**
 * Schema migrations.
 *
 * The site deploys straight from git with no build step and no console, so a
 * release that needs new columns has nothing to run them. Each change is
 * recorded here against a version number held in `settings.schema_version`;
 * the first request after a deploy notices it is behind and applies whatever
 * is missing.
 *
 * Every step must be safe to run against a database that already has it —
 * they are checked against information_schema rather than relying on
 * "ADD COLUMN IF NOT EXISTS", which MySQL does not accept.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Bump this when a migration is added below. */
const SCHEMA_VERSION = 3;

/**
 * Migrations, keyed by the version they bring the database up to.
 *
 * @return array<int, callable(PDO): void>
 */
function schema_migrations(): array
{
    return [
        // Doctor profiles: the fields a patient wants before choosing a
        // consultant, all editable from the admin panel.
        2 => static function (PDO $pdo): void {
            add_column($pdo, 'doctors', 'designation', "VARCHAR(160) NOT NULL DEFAULT '' AFTER `speciality`");
            add_column($pdo, 'doctors', 'experience_years', 'SMALLINT UNSIGNED DEFAULT NULL AFTER `designation`');
            add_column($pdo, 'doctors', 'languages', "VARCHAR(160) NOT NULL DEFAULT '' AFTER `experience_years`");
            add_column($pdo, 'doctors', 'reg_no', "VARCHAR(60) NOT NULL DEFAULT '' AFTER `languages`");
            add_column($pdo, 'doctors', 'location', "VARCHAR(160) NOT NULL DEFAULT '' AFTER `reg_no`");
            add_column($pdo, 'doctors', 'opd_timings', "VARCHAR(200) NOT NULL DEFAULT '' AFTER `location`");
            add_column($pdo, 'doctors', 'education', 'TEXT DEFAULT NULL AFTER `bio`');
            add_column($pdo, 'doctors', 'services', 'TEXT DEFAULT NULL AFTER `education`');
        },

        // Fill the two seeded profiles from what the hospital's own signage and
        // brochure already say, so the new page is not blank on the day it ships.
        // Only where reception has not written something already. Held as a
        // literal snapshot rather than read from includes/site.php: a migration
        // has to keep meaning the same thing after the constants move on.
        3 => static function (PDO $pdo): void {
            $seed = [
                'dr-gundavarapu-venkatesh' => [
                    'education' => [
                        'MBBS',
                        'MD in General Medicine — SRM University, Chennai',
                        'Diploma in Endocrinology & Diabetology',
                        'Changing the Paradigm in Type 2 Diabetes Mellitus Management — '
                        . 'Medical Trends, based on official resources of the American '
                        . 'Diabetes Association (ADA)',
                    ],
                    'services' => [
                        'Diabetes (Sugar) & Blood Pressure', 'Heart & Kidney Problems',
                        'Paralysis & Stroke', 'Thyroid Disorders', 'All Types of Fever',
                        'Dengue & Malaria', 'Snake Bite & Scorpion Sting',
                        'Asthma & Tuberculosis', 'Rheumatology', 'Skin Diseases',
                        'Neurological Problems', 'Lung Problems', 'Liver Problems',
                        '2D Echo Scan',
                    ],
                ],
                'dr-maddipudi-brahmani' => [
                    'education' => [
                        'MBBS',
                        'MS in Obstetrics & Gynaecology',
                    ],
                    'services' => [
                        'Normal Delivery', 'Caesarean Section', 'High Risk Pregnancy',
                        'PCOD Treatment', 'Menstrual Problems', 'Hysterectomy',
                        'Ectopic Pregnancy', 'Laparoscopic Operations',
                        'Infertility Treatment', 'Tubectomy Operations',
                        'Menopause Care', 'Maternity Scans',
                    ],
                ],
            ];

            $stmt = $pdo->prepare(
                "UPDATE doctors
                    SET education = COALESCE(NULLIF(education, ''), ?),
                        services  = COALESCE(NULLIF(services,  ''), ?)
                  WHERE slug = ?"
            );

            foreach ($seed as $slug => $fields) {
                $stmt->execute([
                    implode("\n", $fields['education']),
                    implode("\n", $fields['services']),
                    $slug,
                ]);
            }
        },
    ];
}

/** True when $table already has $column. */
function has_column(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Add a column unless it is already there.
 *
 * $definition is interpolated, so it must be a literal from this file — never
 * anything that reached us from a request.
 */
function add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (has_column($pdo, $table, $column)) {
        return;
    }
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}

/**
 * Bring the database up to SCHEMA_VERSION.
 *
 * Cheap to call on every request: it is a single integer comparison against
 * the already-loaded settings unless there is genuinely work to do.
 */
function run_migrations(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $from = setting_int('schema_version', 1);
    if ($from >= SCHEMA_VERSION) {
        return;
    }

    try {
        $pdo  = db();
        $save = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach (schema_migrations() as $version => $step) {
            if ($version <= $from) {
                continue;
            }
            $step($pdo);
            $save->execute(['schema_version', (string) $version]);
            setting_forget();
        }
    } catch (PDOException $e) {
        // A site that cannot migrate should still serve. The pages that need
        // the new columns degrade rather than fatal, so log and carry on.
        error_log('Migration failed: ' . $e->getMessage());
    }
}
