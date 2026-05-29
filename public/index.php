<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

start_app_session();

// Logged-in visitors skip the marketing page.
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> &mdash; Track issues. Move work forward.</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/landing.css')) ?>">
</head>
<body>
    <header class="landing-nav">
        <a class="landing-nav__brand" href="<?= e(url('index.php')) ?>">
            <span class="landing-nav__logo">TD</span>
            <?= e(APP_NAME) ?>
        </a>
        <nav class="landing-nav__links">
            <a href="<?= e(url('login.php')) ?>">Log in</a>
            <a class="btn btn--primary" href="<?= e(url('register.php')) ?>">Create account</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <p class="hero__eyebrow">Built for small teams</p>
            <h1>Track issues. Move work forward.</h1>
            <p class="hero__lead">
                <?= e(APP_NAME) ?> is a clean ticket and issue management system
                that keeps your team aligned without the complexity of heavyweight tools.
            </p>
            <div class="hero__cta">
                <a class="btn btn--primary btn--lg" href="<?= e(url('register.php')) ?>">
                    Get started &mdash; it's free
                </a>
                <a class="btn btn--secondary btn--lg" href="<?= e(url('login.php')) ?>">
                    I have an account
                </a>
            </div>
        </section>

        <section class="features">
            <article class="feature">
                <div class="feature__icon">&#9776;</div>
                <h3>Organized tickets</h3>
                <p>
                    Create, assign, prioritize, and track every task in one place.
                    Status flows, due dates, and clear ownership built in.
                </p>
            </article>
            <article class="feature">
                <div class="feature__icon">&#10070;</div>
                <h3>Subtasks &amp; threads</h3>
                <p>
                    Break big work into smaller pieces and keep every conversation
                    attached to the ticket it belongs to.
                </p>
            </article>
            <article class="feature">
                <div class="feature__icon">&#9888;</div>
                <h3>Real visibility</h3>
                <p>
                    See who&rsquo;s doing what, what&rsquo;s overdue, and how the
                    team is progressing &mdash; at a glance from the dashboard.
                </p>
            </article>
        </section>
    </main>

    <footer class="landing-footer">
        <small>
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot;
            MIT122 Interactive Web Design and Development project
        </small>
    </footer>
</body>
</html>
