<?php
// Application configuration — copy this file to config.local.php and adjust credentials.

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'shelfaware');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'ShelfAware');
define('LOAN_DAYS', 14);
define('FINE_PER_DAY', 0.50);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
