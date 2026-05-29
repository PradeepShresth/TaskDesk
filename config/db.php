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

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
