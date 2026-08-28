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
const SCHEMA_VERSION = 10;

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

        // Photographs reception uploads for the page banners and the department
        // cards. One row per slot; a slot with no row falls back to the drawn
        // artwork, so the site is never waiting on a photo to look finished.
        4 => static function (PDO $pdo): void {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS `site_images` (
                   `slot`       VARCHAR(60)  NOT NULL,
                   `file`       VARCHAR(160) NOT NULL,
                   `alt`        VARCHAR(200) NOT NULL DEFAULT \'\',
                   `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
                   PRIMARY KEY (`slot`)
                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        },

        // Openly licensed default photographs for the banners and cards, shipped
        // in the repository (credits.php lists their authors and licences).
        // INSERT IGNORE: a slot reception has already filled — or has since
        // emptied and refilled — is theirs, and this never overwrites it.
        5 => static function (PDO $pdo): void {
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO site_images (slot, file, alt) VALUES (?,?,?)'
            );
            $defaults = [
                ['banner-about', 'banner-about.jpg', 'A stethoscope resting on a wooden heart'],
                ['banner-services', 'banner-services.jpg', 'A stethoscope laid out on a table'],
                ['banner-diabetes', 'banner-diabetes.jpg', 'A glucometer showing a blood sugar reading'],
                ['banner-maternity', 'banner-maternity.jpg', 'A newborn baby sleeping'],
                ['banner-emergency', 'banner-emergency.jpg', 'An ambulance outside a hospital'],
                ['card-medicine', 'card-medicine.jpg', 'A doctor holding the chest piece of a stethoscope'],
                ['card-diabetes', 'card-diabetes.jpg', 'A glucometer showing a blood sugar reading'],
                ['card-maternity', 'card-maternity.jpg', 'A newborn baby sleeping in a basket'],
                ['card-emergency', 'card-emergency.jpg', 'A Force Traveller ambulance'],
                ['card-lab', 'card-lab.jpg', 'A gloved hand holding a blood sample tube'],
            ];
            $dir = dirname(__DIR__) . '/assets/img/site/';
            foreach ($defaults as [$slot, $file, $alt]) {
                if (is_file($dir . $file)) {
                    $stmt->execute([$slot, $file, $alt]);
                }
            }
        },

        // The tariff slots arrived a round later: a hand holding a rupee note,
        // for the page that says exactly what treatment costs. Same INSERT
        // IGNORE contract as migration 5.
        // Editable page text. The table holds only what reception has
        // actually changed: every field's default still lives in PHP beside
        // the code that uses it, so a fresh database renders the site exactly
        // as it shipped, and clearing a row is what "reset to default" means.
        // Editable lists — tariff rows, standing offers, service lists. One
        // table serves them all: a tariff row uses title/amount/unit, an offer
        // uses title/body/icon, a service list uses title alone. As with the
        // content table, an empty list means "still showing the defaults".
        // Card lists carry an accent colour. Without somewhere to keep it, making
        // those cards editable would have quietly flattened the page to one hue.
        // The hero slideshow's five pictures. Seeded from the department card
        // photographs, which are already on disk and already credited, so the
        // slideshow arrives looking finished; reception can swap any of them
        // afterwards without touching the cards they were borrowed from.
        10 => static function (PDO $pdo): void {
            require_once __DIR__ . '/site-images.php';
            $stmt = $pdo->prepare('INSERT IGNORE INTO site_images (slot, file, alt) VALUES (?,?,?)');
            $dir  = dirname(__DIR__) . '/assets/img/site/';
            foreach (site_image_seeds() as $slot => $seed) {
                if (is_file($dir . $seed['file'])) {
                    $stmt->execute([$slot, $seed['file'], $seed['alt']]);
                }
            }
        },

        9 => static function (PDO $pdo): void {
            add_column($pdo, 'list_items', 'tone', "VARCHAR(20) NOT NULL DEFAULT '' AFTER `icon`");
        },

        8 => static function (PDO $pdo): void {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `list_items` (
                  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `list_key`   VARCHAR(40)  NOT NULL,
                  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
                  `title`      VARCHAR(160) NOT NULL DEFAULT '',
                  `body`       VARCHAR(400) NOT NULL DEFAULT '',
                  `icon`       VARCHAR(40)  NOT NULL DEFAULT '',
                  `amount`     INT UNSIGNED DEFAULT NULL,
                  `unit`       VARCHAR(40)  NOT NULL DEFAULT '',
                  PRIMARY KEY (`id`),
                  KEY `idx_list` (`list_key`, `sort_order`, `id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        },

        7 => static function (PDO $pdo): void {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `content` (
                  `content_key`   VARCHAR(80) NOT NULL,
                  `content_value` TEXT        NOT NULL,
                  `updated_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                              ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`content_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        },

        6 => static function (PDO $pdo): void {
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO site_images (slot, file, alt) VALUES (?,?,?)'
            );
            $dir = dirname(__DIR__) . '/assets/img/site/';
            $defaults = [
                ['card-tariff', 'card-tariff.jpg', 'A hand holding an Indian rupee note'],
                ['banner-tariff', 'banner-tariff.jpg', 'A hand holding an Indian rupee note'],
            ];
            foreach ($defaults as [$slot, $file, $alt]) {
                if (is_file($dir . $file)) {
                    $stmt->execute([$slot, $file, $alt]);
                }
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

    // The installer loads this file for its helpers while there is deliberately
    // no config and no database yet. Trying to migrate there takes the setup
    // page down with it, which is the one page that has to work.
    if (!SNH_CONFIGURED) {
        return;
    }

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
