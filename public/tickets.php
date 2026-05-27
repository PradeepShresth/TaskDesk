<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Tickets';

// Fetch every ticket along with its assignee's name (if any).
$sql = "SELECT t.ticket_id, t.title, t.status, t.priority, t.due_date,
               t.created_at, u.name AS assignee_name
          FROM tickets t
          LEFT JOIN users u ON u.user_id = t.assigned_user_id
         ORDER BY t.created_at DESC";

$result  = $conn->query($sql);
$tickets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1>Tickets</h1>
        <p class="text-muted">
            <?= count($tickets) ?>
            ticket<?= count($tickets) === 1 ? '' : 's' ?> total.
        </p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('ticket_create.php')) ?>">+ New ticket</a>
</section>

<?php if (empty($tickets)): ?>
    <div class="card">
        <h2>No tickets yet</h2>
        <p class="text-muted">
            Get started by creating the first ticket for your team.
        </p>
        <p>
            <a class="btn btn--primary" href="<?= e(url('ticket_create.php')) ?>">
                Create a ticket
            </a>
        </p>
    </div>
<?php else: ?>
    <div class="card card--flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assignee</th>
                    <th>Due</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('ticket.php?id=' . (int)$t['ticket_id'])) ?>">
                                <?= e($t['title']) ?>
                            </a>
                        </td>
                        <td><?= e(str_replace('_', ' ', $t['status'])) ?></td>
                        <td><?= e(ucfirst($t['priority'])) ?></td>
                        <td>
                            <?php if ($t['assignee_name']): ?>
                                <?= e($t['assignee_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['due_date']): ?>
                                <?= e(date('M j, Y', strtotime($t['due_date']))) ?>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?= e(date('M j', strtotime($t['created_at']))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
