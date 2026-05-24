<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

start_app_session();

// Drop all logged-in user state.
$_SESSION = [];

// Issue a fresh session id so the old one (which a browser cookie or
// network capture might still have) can no longer be used to log in.
session_regenerate_id(true);

flash_set('success', 'You have been logged out.');
redirect('login.php');
