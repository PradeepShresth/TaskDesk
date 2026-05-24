<?php
/**
 * Database connection.
 *
 * Loads config and opens a MySQLi connection that the rest of the app
 * uses via the global $conn variable.
 */

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die(
        'Missing config/config.php. Copy config/config.sample.php to '
        . 'config/config.php and update the database credentials.'
    );
}
require_once $configFile;

// Throw exceptions on MySQLi errors instead of silent warnings.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed. Please try again later.');
}
