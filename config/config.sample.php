<?php
/**
 * TaskDesk — application configuration.
 *
 * Copy this file to config/config.php and update the values for your
 * local environment. config.php is git-ignored so credentials never
 * get committed.
 */

// ----------------------------------------------------------
// Database
// ----------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'taskdesk');
define('DB_PORT', 3306);

// ----------------------------------------------------------
// Application
// ----------------------------------------------------------
define('APP_NAME', 'TaskDesk');
// Base URL the app is served from (no trailing slash).
define('BASE_URL', 'http://localhost/TaskDesk/public');

// Toggle verbose error output. Turn OFF in production.
define('APP_DEBUG', true);

// ----------------------------------------------------------
// Session
// ----------------------------------------------------------
define('SESSION_NAME', 'taskdesk_session');
