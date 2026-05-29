<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Dashboard';

// Status roll-up.
$status_counts = [
    'open'        => 0,
    'in_progress' => 0,
    'resolved'    => 0,
    'closed'      => 0,
];
$result = $conn->query('SELECT status, COUNT(*) AS c FROM tickets GROUP BY status');
while ($row = $result->fetch_assoc()) {
    if (array_key_exists($row['status'], $status_counts)) {
        $status_counts[$row['status']] = (int)$row['c'];
    }
}
$total_tickets = array_sum($status_counts);

// "My open tickets" — assigned to the current user, not done.
$stmt = $conn->prepare(
    "SELECT ticket_id, title, status, priority, due_date
       FROM tickets
      WHERE assigned_user_id = ?
        AND status IN ('open', 'in_progress')
      ORDER BY (due_date IS NULL), due_date ASC,
               FIELD(priority,'high','medium','low')
      LIMIT 6"
);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$my_tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent activity from the ticket_history table.
$activity = $conn->query(
    "SELECT h.event_type, h.old_value, h.new_value, h.created_at, h.ticket_id,
            t.title AS ticket_title, u.name AS actor_name
       FROM ticket_history h
       JOIN tickets t ON t.ticket_id = h.ticket_id
  LEFT JOIN users   u ON u.user_id   = h.user_id
      ORDER BY h.created_at DESC
      LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

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
    <a class="stat-card" href="<?= e(url('tickets.php')) ?>">
        <span class="stat-card__label">Total tickets</span>
        <span class="stat-card__value"><?= (int)$total_tickets ?></span>
    </a>
    <a class="stat-card stat-card--info" href="<?= e(url('tickets.php?status=open')) ?>">
        <span class="stat-card__label">Open</span>
        <span class="stat-card__value"><?= (int)$status_counts['open'] ?></span>
    </a>
    <a class="stat-card stat-card--warning" href="<?= e(url('tickets.php?status=in_progress')) ?>">
        <span class="stat-card__label">In progress</span>
        <span class="stat-card__value"><?= (int)$status_counts['in_progress'] ?></span>
    </a>
    <a class="stat-card stat-card--success" href="<?= e(url('tickets.php?status=resolved')) ?>">
        <span class="stat-card__label">Resolved</span>
        <span class="stat-card__value"><?= (int)$status_counts['resolved'] ?></span>
    </a>
    <a class="stat-card stat-card--muted" href="<?= e(url('tickets.php?status=closed')) ?>">
        <span class="stat-card__label">Closed</span>
        <span class="stat-card__value"><?= (int)$status_counts['closed'] ?></span>
    </a>
</section>

<div class="dashboard-grid">
    <div class="card">
        <div class="page-intro" style="margin-bottom: var(--space-3);">
            <div>
                <h2 style="margin: 0;">My open tickets</h2>
                <p class="text-muted" style="margin: 4px 0 0;">
                    Assigned to you and still in progress.
                </p>
            </div>
            <a class="btn btn--secondary btn--sm"
               href="<?= e(url('tickets.php?assignee=' . (int)$current_user_id)) ?>">
                View all
            </a>
        </div>
        <?php if (empty($my_tickets)): ?>
            <p class="text-muted" style="margin: 0;">
                You have nothing assigned right now. Nice.
            </p>
        <?php else: ?>
            <ul class="my-tickets">
                <?php foreach ($my_tickets as $t): ?>
                    <?php $is_overdue = ticket_is_overdue($t['due_date'], $t['status']); ?>
                    <li class="my-tickets__item">
                        <div class="my-tickets__main">
                            <a href="<?= e(url('ticket.php?id=' . (int)$t['ticket_id'])) ?>">
                                <?= e($t['title']) ?>
                            </a>
                            <div class="my-tickets__meta">
                                <span class="badge badge--<?= e($t['priority']) ?>">
                                    <?= e($t['priority']) ?>
                                </span>
                                <span class="status-text status-text--<?= e($t['status']) ?>">
                                    <?= e(str_replace('_', ' ', $t['status'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="my-tickets__due">
                            <?php if ($t['due_date']): ?>
                                <span class="<?= $is_overdue ? 'overdue' : 'text-muted' ?>">
                                    <?= e(date('M j', strtotime($t['due_date']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Recent activity</h2>
        <?php if (empty($activity)): ?>
            <p class="text-muted" style="margin: 0;">
                No activity yet. Updates will appear here as the team works.
            </p>
        <?php else: ?>
            <ul class="activity">
                <?php foreach ($activity as $a): ?>
                    <li class="activity__item">
                        <span class="activity__icon activity__icon--<?= e($a['event_type']) ?>" aria-hidden="true"></span>
                        <div class="activity__body">
                            <p class="activity__what">
                                <strong><?= e($a['actor_name'] ?? 'Someone') ?></strong>
                                <?php
                                switch ($a['event_type']) {
                                    case 'created':
                                        echo 'created';
                                        break;
                                    case 'status_changed':
                                        echo 'moved to <em>'
                                            . e(str_replace('_', ' ', $a['new_value'] ?? '?'))
                                            . '</em> on';
                                        break;
                                    case 'priority_changed':
                                        echo 'set priority <em>' . e($a['new_value'] ?? '?') . '</em> on';
                                        break;
                                    case 'assignee_changed':
                                        echo $a['new_value']
                                            ? 'assigned'
                                            : 'unassigned';
                                        break;
                                    default:
                                        echo e($a['event_type']) . ' on';
                                }
                                ?>
                                <a href="<?= e(url('ticket.php?id=' . (int)$a['ticket_id'])) ?>">
                                    <?= e($a['ticket_title']) ?>
                                </a>
                            </p>
                            <p class="activity__when">
                                <?= e(date('M j, g:i A', strtotime($a['created_at']))) ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
