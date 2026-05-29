<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Tickets';

$f_status   = isset($_GET['status'])   ? (string)$_GET['status']   : '';
$f_priority = isset($_GET['priority']) ? (string)$_GET['priority'] : '';
$f_assignee = isset($_GET['assignee']) ? (string)$_GET['assignee'] : '';
$f_due      = isset($_GET['due'])      ? (string)$_GET['due']      : '';
$f_sort     = isset($_GET['sort'])     ? (string)$_GET['sort']     : 'created_desc';

$valid_status   = ['open', 'in_progress', 'resolved', 'closed'];
$valid_priority = ['low', 'medium', 'high'];
$valid_due      = ['overdue', 'this_week', 'has_date', 'no_date'];
$valid_sort     = ['created_desc', 'due_asc', 'priority_high'];

if (!in_array($f_sort, $valid_sort, true)) $f_sort = 'created_desc';

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
if (in_array($f_due, $valid_due, true)) {
    $today = date('Y-m-d');
    if ($f_due === 'overdue') {
        $where[]  = "t.due_date IS NOT NULL AND t.due_date < ? AND t.status NOT IN ('resolved','closed')";
        $params[] = $today;
        $types   .= 's';
    } elseif ($f_due === 'this_week') {
        $week_end = date('Y-m-d', strtotime('+7 days'));
        $where[]  = 't.due_date BETWEEN ? AND ?';
        $params[] = $today;
        $params[] = $week_end;
        $types   .= 'ss';
    } elseif ($f_due === 'has_date') {
        $where[] = 't.due_date IS NOT NULL';
    } elseif ($f_due === 'no_date') {
        $where[] = 't.due_date IS NULL';
    }
}

if ($f_sort === 'due_asc') {
    $order_by = ' ORDER BY (t.due_date IS NULL), t.due_date ASC';
} elseif ($f_sort === 'priority_high') {
    $order_by = " ORDER BY FIELD(t.priority,'high','medium','low'), t.created_at DESC";
} else {
    $order_by = ' ORDER BY t.created_at DESC';
}

$sql = "SELECT t.ticket_id, t.title, t.status, t.priority, t.due_date,
               t.created_at, u.name AS assignee_name
          FROM tickets t
     LEFT JOIN users u ON u.user_id = t.assigned_user_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . $order_by;

$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_all = (int)$conn->query('SELECT COUNT(*) AS c FROM tickets')->fetch_assoc()['c'];

$users_result = $conn->query('SELECT user_id, name FROM users ORDER BY name');
$all_users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

// Active filter chips (label + URL with that param removed)
$status_labels   = ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
$priority_labels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
$due_labels      = ['overdue' => 'Overdue', 'this_week' => 'Next 7 days', 'has_date' => 'Has due date', 'no_date' => 'No due date'];

$user_lookup = [];
foreach ($all_users as $u) $user_lookup[(string)$u['user_id']] = $u['name'];

$chip_url = function ($remove_key) {
    $params = $_GET;
    unset($params[$remove_key]);
    return 'tickets.php' . (empty($params) ? '' : '?' . http_build_query($params));
};

$chips = [];
if (isset($status_labels[$f_status])) {
    $chips[] = ['label' => 'Status: ' . $status_labels[$f_status], 'remove' => $chip_url('status')];
}
if (isset($priority_labels[$f_priority])) {
    $chips[] = ['label' => 'Priority: ' . $priority_labels[$f_priority], 'remove' => $chip_url('priority')];
}
if (isset($user_lookup[$f_assignee])) {
    $chips[] = ['label' => 'Assignee: ' . $user_lookup[$f_assignee], 'remove' => $chip_url('assignee')];
}
if (isset($due_labels[$f_due])) {
    $chips[] = ['label' => 'Due: ' . $due_labels[$f_due], 'remove' => $chip_url('due')];
}

$is_filtered = !empty($where);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1>Tickets</h1>
        <p class="text-muted">
            <?php if ($is_filtered): ?>
                Showing <strong><?= count($tickets) ?></strong> of <?= $total_all ?> tickets
            <?php else: ?>
                <strong><?= $total_all ?></strong> ticket<?= $total_all === 1 ? '' : 's' ?> total
            <?php endif; ?>
        </p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('ticket_create.php')) ?>">+ New ticket</a>
</section>

<form class="filter-bar" method="get" action="<?= e(url('tickets.php')) ?>" data-auto-submit>
    <div class="filter-bar__filters">
        <select name="status" aria-label="Filter by status">
            <option value="">All statuses</option>
            <?php foreach ($status_labels as $v => $l): ?>
                <option value="<?= e($v) ?>" <?= $f_status === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" aria-label="Filter by priority">
            <option value="">All priorities</option>
            <?php foreach ($priority_labels as $v => $l): ?>
                <option value="<?= e($v) ?>" <?= $f_priority === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="assignee" aria-label="Filter by assignee">
            <option value="">Any assignee</option>
            <?php foreach ($all_users as $u): ?>
                <option value="<?= (int)$u['user_id'] ?>"
                        <?= $f_assignee === (string)$u['user_id'] ? 'selected' : '' ?>>
                    <?= e($u['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="due" aria-label="Filter by due date">
            <option value="">Any due date</option>
            <?php foreach ($due_labels as $v => $l): ?>
                <option value="<?= e($v) ?>" <?= $f_due === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__sort">
        <label for="filter-sort">Sort</label>
        <select id="filter-sort" name="sort">
            <?php foreach (['created_desc' => 'Newest first', 'due_asc' => 'Due date (soonest)', 'priority_high' => 'Priority (high first)'] as $v => $l): ?>
                <option value="<?= e($v) ?>" <?= $f_sort === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <noscript>
        <button type="submit" class="btn btn--primary btn--sm">Apply</button>
    </noscript>
</form>

<?php if (!empty($chips)): ?>
    <div class="filter-chips">
        <?php foreach ($chips as $chip): ?>
            <a class="filter-chip" href="<?= e(url($chip['remove'])) ?>">
                <span><?= e($chip['label']) ?></span>
                <span class="filter-chip__x" aria-hidden="true">&times;</span>
            </a>
        <?php endforeach; ?>
        <?php if (count($chips) > 1): ?>
            <a class="filter-chips__clear" href="<?= e(url('tickets.php')) ?>">Clear all</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (empty($tickets)): ?>
    <div class="card empty-state">
        <h2><?= $is_filtered ? 'No tickets match those filters' : 'No tickets yet' ?></h2>
        <p class="text-muted">
            <?= $is_filtered
                ? 'Try clearing some filters or creating a new ticket.'
                : 'Get started by creating the first ticket for your team.' ?>
        </p>
        <div class="empty-state__actions">
            <?php if ($is_filtered): ?>
                <a class="btn btn--secondary" href="<?= e(url('tickets.php')) ?>">Clear filters</a>
            <?php endif; ?>
            <a class="btn btn--primary" href="<?= e(url('ticket_create.php')) ?>">+ New ticket</a>
        </div>
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

<script>
(function () {
    var form = document.querySelector('form.filter-bar[data-auto-submit]');
    if (!form) return;
    form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', function () { form.submit(); });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
