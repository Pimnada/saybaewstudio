<?php
/**
 * Copy this file to config.php and fill in the real values.
 * config.php is gitignored and must never be overwritten on the server.
 */

// 'sqlite' for local development, 'mysql' in production.
define('DB_DRIVER', 'sqlite');

// SQLite (local only)
define('DB_SQLITE_PATH', __DIR__ . '/dev.sqlite');

// MySQL (production)
define('DB_HOST', 'localhost');
define('DB_NAME', 'saybaewstudio');
define('DB_USER', 'changeme');
define('DB_PASS', 'changeme');

// Absolute URL of the site, no trailing slash.
define('SITE_URL', 'http://localhost:8210');

// Mail — SMTP is optional; without it PHP mail() is used, and if MAIL_LOG_ONLY
// is true nothing is sent at all and every letter is written to email_log.
define('MAIL_LOG_ONLY', true);
define('MAIL_FROM', 'no-reply@saybaewstudio.com');
define('MAIL_FROM_NAME', 'สายแบ้วสตูดิโอ');
define('MAIL_ADMIN', 'saybaewstudio@gmail.com');
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Storage quota shown in the admin sidebar, in gigabytes.
define('STORAGE_QUOTA_GB', 100);

// Set true only while debugging locally.
define('APP_DEBUG', true);
