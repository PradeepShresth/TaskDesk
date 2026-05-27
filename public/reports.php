<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Reports';

$status_counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
$res = $conn->query('SELECT status, COUNT(*) AS c FROM tickets GROUP BY status');
while ($row = $res->fetch_assoc()) {
    if (array_key_exists($row['status'], $status_counts)) {
        $status_counts[$row['status']] = (int)$row['c'];
    }
}
$total = array_sum($status_counts);

$priority_counts = ['low' => 0, 'medium' => 0, 'high' => 0];
$res = $conn->query('SELECT priority, COUNT(*) AS c FROM tickets GROUP BY priority');
while ($row = $res->fetch_assoc()) {
    if (array_key_exists($row['priority'], $priority_counts)) {
        $priority_counts[$row['priority']] = (int)$row['c'];
    }
}

$today = date('Y-m-d');
$stmt = $conn->prepare(
    "SELECT t.ticket_id, t.title, t.due_date, t.status, t.priority,
            u.name AS assignee_name
       FROM tickets t
  LEFT JOIN users u ON u.user_id = t.assigned_user_id
      WHERE t.due_date IS NOT NULL
        AND t.due_date < ?
        AND t.status NOT IN ('resolved', 'closed')
      ORDER BY t.due_date ASC"
);
$stmt->bind_param('s', $today);
$stmt->execute();
$overdue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$user_stats = $conn->query(
    "SELECT u.user_id, u.name,
            (SELECT COUNT(*) FROM tickets t WHERE t.created_by = u.user_id) AS created_count,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_user_id = u.user_id) AS assigned_count,
            (SELECT COUNT(*) FROM tickets t
              WHERE t.assigned_user_id = u.user_id
                AND t.status IN ('resolved', 'closed')) AS resolved_count
       FROM users u
       ORDER BY u.name"
)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1>Reports</h1>
        <p class="text-muted">
            How the team's tickets are distributed across status and priority.
        </p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-card__label">Total tickets</span>
        <span class="stat-card__value"><?= (int)$total ?></span>
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

<div class="card">
    <h2>Per-user contributions</h2>
    <?php if (empty($user_stats)): ?>
        <p class="text-muted" style="margin: 0;">No users yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Created</th>
                    <th>Assigned</th>
                    <th>Resolved / closed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_stats as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= (int)$u['created_count'] ?></td>
                        <td><?= (int)$u['assigned_count'] ?></td>
                        <td><?= (int)$u['resolved_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Status distribution</h2>
    <?php if ($total === 0): ?>
        <p class="text-muted" style="margin: 0;">Create a ticket to see the chart.</p>
    <?php else: ?>
        <div class="bar-chart">
            <?php foreach ($status_counts as $s => $c): ?>
                <?php $pct = (int)round(($c / $total) * 100); ?>
                <div class="bar-chart__row">
                    <span class="bar-chart__label"><?= e(str_replace('_', ' ', $s)) ?></span>
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill bar-chart__fill--<?= e($s) ?>"
                             style="width: <?= $pct ?>%;"></div>
                    </div>
                    <span class="bar-chart__value"><?= (int)$c ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>By priority</h2>
    <table class="data-table">
        <thead>
            <tr><th>Priority</th><th>Count</th></tr>
        </thead>
        <tbody>
            <?php foreach ($priority_counts as $p => $c): ?>
                <tr>
                    <td><span class="badge badge--<?= e($p) ?>"><?= e($p) ?></span></td>
                    <td><?= (int)$c ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Overdue (<?= count($overdue) ?>)</h2>
    <?php if (empty($overdue)): ?>
        <p class="text-muted" style="margin: 0;">Nothing is overdue. Nice.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assignee</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdue as $t): ?>
                    <tr class="row--overdue">
                        <td>
                            <a href="<?= e(url('ticket.php?id=' . (int)$t['ticket_id'])) ?>">
                                <?= e($t['title']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="overdue">
                                <?= e(date('M j, Y', strtotime($t['due_date']))) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-text status-text--<?= e($t['status']) ?>">
                                <?= e(str_replace('_', ' ', $t['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge--<?= e($t['priority']) ?>">
                                <?= e($t['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($t['assignee_name']): ?>
                                <?= e($t['assignee_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
