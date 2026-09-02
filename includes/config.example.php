<?php
/**
 * Sarada Nursing Home — local configuration.
 *
 * COPY THIS FILE to includes/config.php on the server and fill in the real
 * values. config.php is git-ignored, so your credentials never reach GitHub
 * and survive every deployment.
 */

// --- Database (hPanel > Databases > MySQL Databases) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// --- Site ---
define('SITE_URL', 'https://saradahospitals.com');

// Set to true only while debugging. Never leave true on a live site.
define('DEBUG_MODE', false);

// Only needed if the nightly backup runs as a URL cron instead of a CLI cron:
// tools/backup.php?key=... must match this. Use a long random string.
// define('BACKUP_KEY', '');
