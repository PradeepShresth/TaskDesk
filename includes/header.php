<?php
/**
 * Shared page header for authenticated views.
 *
 * Expected to be preceded by includes/auth_guard.php (so $current_user_*
 * are defined). Pages can set $page_title before requiring this file:
 *
 *     $page_title = 'Dashboard';
 *     require_once __DIR__ . '/../includes/header.php';
 */

if (!isset($current_user_id)) {
    require_once __DIR__ . '/auth_guard.php';
}

$page_title    = $page_title ?? APP_NAME;
$current_page  = basename($_SERVER['PHP_SELF']);

if (!function_exists('nav_link')) {
    function nav_link($href, $label, array $active_pages) {
        global $current_page;
        $is_active = in_array($current_page, $active_pages, true);
        $cls = 'nav__link' . ($is_active ? ' nav__link--active' : '');
        return '<a class="' . $cls . '" href="' . e(url($href)) . '">'
             . e($label) . '</a>';
    }
}

$flash_success = flash_get('success');
$flash_error   = flash_get('error');
$flash_info    = flash_get('info');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> &mdash; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner container">
            <a class="site-header__brand" href="<?= e(url('dashboard.php')) ?>">
                <span class="site-header__logo">TD</span>
                <span><?= e(APP_NAME) ?></span>
            </a>

            <nav class="nav">
                <?= nav_link('dashboard.php', 'Dashboard', ['dashboard.php']) ?>
                <?= nav_link('tickets.php',   'Tickets',   ['tickets.php', 'ticket.php', 'ticket_create.php', 'ticket_edit.php']) ?>
                <?php if (is_admin()): ?>
                    <?= nav_link('reports.php', 'Reports', ['reports.php']) ?>
                <?php endif; ?>
            </nav>

            <div class="site-header__user">
                <span class="site-header__greeting">Hi, <?= e($current_user_name) ?></span>
                <a class="btn btn--secondary btn--sm" href="<?= e(url('logout.php')) ?>">Log out</a>
            </div>
        </div>
    </header>

    <main class="container site-main">
<?php if ($flash_success !== null): ?>
        <div class="flash flash--success"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error !== null): ?>
        <div class="flash flash--error"><?= e($flash_error) ?></div>
<?php endif; ?>
<?php if ($flash_info !== null): ?>
        <div class="flash flash--info"><?= e($flash_info) ?></div>
<?php endif; ?>
