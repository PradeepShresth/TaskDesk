<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Tickets';

$f_status   = isset($_GET['status'])   ? (string)$_GET['status']   : '';
$f_priority = isset($_GET['priority']) ? (string)$_GET['priority'] : '';
$f_assignee = isset($_GET['assignee']) ? (string)$_GET['assignee'] : '';

$valid_status   = ['open', 'in_progress', 'resolved', 'closed'];
$valid_priority = ['low', 'medium', 'high'];

$where  = [];
$params = [];
$types  = '';

if (in_array($f_status, $valid_status, true)) {
    $where[]  = 't.status = ?';
    $params[] = $f_status;
    $types   .= 's';
}
if (in_array($f_priority, $valid_priority, true)) {
    $where[]  = 't.priority = ?';
    $params[] = $f_priority;
    $types   .= 's';
}
if ($f_assignee !== '' && ctype_digit($f_assignee)) {
    $where[]  = 't.assigned_user_id = ?';
    $params[] = (int)$f_assignee;
    $types   .= 'i';
}

$sql = "SELECT t.ticket_id, t.title, t.status, t.priority, t.due_date,
               t.created_at, u.name AS assignee_name
          FROM tickets t
     LEFT JOIN users u ON u.user_id = t.assigned_user_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY t.created_at DESC';

$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$users_result = $conn->query('SELECT user_id, name FROM users ORDER BY name');
$all_users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1>Tickets</h1>
        <p class="text-muted">
            <?= count($tickets) ?>
            ticket<?= count($tickets) === 1 ? '' : 's' ?>
            <?= ($where ? 'matching filters' : 'total') ?>.
        </p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('ticket_create.php')) ?>">+ New ticket</a>
</section>

<div class="filter-bar">
    <form method="get" action="<?= e(url('tickets.php')) ?>">
        <div>
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status">
                <option value="">All</option>
                <?php foreach (['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $v => $l): ?>
                    <option value="<?= e($v) ?>" <?= $f_status === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter-priority">Priority</label>
            <select id="filter-priority" name="priority">
                <option value="">All</option>
                <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $v => $l): ?>
                    <option value="<?= e($v) ?>" <?= $f_priority === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter-assignee">Assignee</label>
            <select id="filter-assignee" name="assignee">
                <option value="">Anyone</option>
                <?php foreach ($all_users as $u): ?>
                    <option value="<?= (int)$u['user_id'] ?>"
                            <?= $f_assignee === (string)$u['user_id'] ? 'selected' : '' ?>>
                        <?= e($u['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn--primary">Apply</button>
            <?php if ($where): ?>
                <a href="<?= e(url('tickets.php')) ?>" class="btn btn--secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (empty($tickets)): ?>
    <div class="card">
        <h2><?= $where ? 'No tickets match those filters' : 'No tickets yet' ?></h2>
        <p class="text-muted">
            <?= $where
                ? 'Try clearing some filters or creating a new ticket.'
                : 'Get started by creating the first ticket for your team.' ?>
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
                    <?php $is_overdue = ticket_is_overdue($t['due_date'], $t['status']); ?>
                    <tr<?= $is_overdue ? ' class="row--overdue"' : '' ?>>
                        <td>
                            <a href="<?= e(url('ticket.php?id=' . (int)$t['ticket_id'])) ?>">
                                <?= e($t['title']) ?>
                            </a>
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
                        <td>
                            <?php if ($t['due_date']): ?>
                                <span class="<?= $is_overdue ? 'overdue' : '' ?>">
                                    <?= e(date('M j, Y', strtotime($t['due_date']))) ?>
                                </span>
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
