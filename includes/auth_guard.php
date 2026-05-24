<?php
/**
 * Session guard. Include this at the top of any page that should only
 * be reachable by a logged-in user:
 *
 *     require_once __DIR__ . '/../includes/auth_guard.php';
 *
 * It loads the DB + helper functions, starts the session, and bounces
 * unauthenticated visitors back to the login page (preserving the
 * URL they were trying to reach so we can return them after login).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/' . 'functions.php';

start_app_session();

if (empty($_SESSION['user_id'])) {
    // Remember where they were heading so login can send them back.
    if (!empty($_SERVER['REQUEST_URI'])) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
    }
    flash_set('error', 'Please log in to continue.');
    redirect('login.php');
}

// Expose the current user for downstream pages.
$current_user_id   = (int)$_SESSION['user_id'];
$current_user_name = $_SESSION['user_name']  ?? '';
$current_user_role = $_SESSION['user_role']  ?? 'developer';
