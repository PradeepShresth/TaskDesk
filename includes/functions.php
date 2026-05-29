<?php
/**
 * Shared utility functions used across the app.
 *
 * Assumes config/config.php has already been required (for BASE_URL
 * and SESSION_NAME). The simplest way is to include config/db.php
 * first, then this file.
 */

/**
 * Escape a value for safe HTML output.
 */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build an absolute URL from a path relative to BASE_URL.
 */
function url($path = '')
{
    $path = ltrim((string)$path, '/');
    $base = rtrim(BASE_URL, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

/**
 * Redirect to a path (relative to BASE_URL) and stop execution.
 */
function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Start the named app session exactly once per request.
 */
function start_app_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Store a one-shot flash message (read once, then cleared).
 */
function flash_set($key, $message)
{
    start_app_session();
    $_SESSION['_flash'][$key] = $message;
}

/**
 * Fetch and clear a flash message. Returns null if none exists.
 */
function flash_get($key)
{
    start_app_session();
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $message;
}

/**
 * Quick test for whether a flash message is queued.
 */
function flash_has($key)
{
    start_app_session();
    return isset($_SESSION['_flash'][$key]);
}

/**
 * Trim and collapse whitespace on a posted string value.
 */
function clean_input($value)
{
    return trim((string)$value);
}

/**
 * True if a ticket has a due_date earlier than today and is still
 * actionable (not resolved or closed).
 */
function ticket_is_overdue($due_date, $status)
{
    if (empty($due_date)) return false;
    if (in_array($status, ['resolved', 'closed'], true)) return false;
    return strtotime($due_date) < strtotime(date('Y-m-d'));
}

/**
 * Convenience: is the logged-in user an admin?
 */
function is_admin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Bounce non-admin users back to the dashboard with an error flash.
 * Call at the top of pages or handlers that should be admin-only.
 */
function require_admin()
{
    if (!is_admin()) {
        flash_set('error', 'That action is only available to admins.');
        redirect('dashboard.php');
    }
}

/**
 * Append a row to the ticket_history audit log.
 * $conn must already be a live MySQLi connection.
 */
function log_ticket_event($conn, $ticket_id, $user_id, $event_type, $old_value = null, $new_value = null)
{
    $stmt = $conn->prepare(
        'INSERT INTO ticket_history (ticket_id, user_id, event_type, old_value, new_value)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iisss', $ticket_id, $user_id, $event_type, $old_value, $new_value);
    $stmt->execute();
    $stmt->close();
}

