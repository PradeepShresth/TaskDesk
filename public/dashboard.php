<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Dashboard';

// Roll up ticket counts by status. Every status starts at 0 so the
// dashboard renders cleanly even when the database is empty.
$status_counts = [
    'open'        => 0,
    'in_progress' => 0,
    'resolved'    => 0,
    'closed'      => 0,
];

$result = $conn->query(
    'SELECT status, COUNT(*) AS c FROM tickets GROUP BY status'
);
while ($row = $result->fetch_assoc()) {
    if (array_key_exists($row['status'], $status_counts)) {
        $status_counts[$row['status']] = (int)$row['c'];
    }
}
$total_tickets = array_sum($status_counts);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1>Welcome back, <?= e($current_user_name) ?></h1>
        <p class="text-muted">
            Here&rsquo;s a snapshot of where the team&rsquo;s tickets stand today.
        </p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('ticket_create.php')) ?>">+ New ticket</a>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-card__label">Total tickets</span>
        <span class="stat-card__value"><?= (int)$total_tickets ?></span>
    </article>
    <article class="stat-card stat-card--info">
        <span class="stat-card__label">Open</span>
        <span class="stat-card__value"><?= (int)$status_counts['open'] ?></span>
    </article>
    <article class="stat-card stat-card--warning">
        <span class="stat-card__label">In progress</span>
        <span class="stat-card__value"><?= (int)$status_counts['in_progress'] ?></span>
    </article>
    <article class="stat-card stat-card--success">
        <span class="stat-card__label">Resolved</span>
        <span class="stat-card__value"><?= (int)$status_counts['resolved'] ?></span>
    </article>
    <article class="stat-card stat-card--muted">
        <span class="stat-card__label">Closed</span>
        <span class="stat-card__value"><?= (int)$status_counts['closed'] ?></span>
    </article>
</section>

<section class="card">
    <h2>Recent activity</h2>
    <p class="text-muted">
        Once tickets start flowing, the latest updates will show up here.
        For now, head to <a href="<?= e(url('tickets.php')) ?>">Tickets</a>
        to create your first one.
    </p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
