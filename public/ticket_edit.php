<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$id = 0;
if (isset($_GET['id']))            $id = (int)$_GET['id'];
elseif (isset($_POST['ticket_id'])) $id = (int)$_POST['ticket_id'];

if ($id <= 0) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

$stmt = $conn->prepare('SELECT * FROM tickets WHERE ticket_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

$errors           = [];
$title            = $ticket['title'];
$description      = $ticket['description'] ?? '';
$priority         = $ticket['priority'];
$status           = $ticket['status'];
$due_date         = $ticket['due_date'] ?? '';
$assigned_user_id = $ticket['assigned_user_id'] !== null ? (string)$ticket['assigned_user_id'] : '';

$users_result = $conn->query('SELECT user_id, name FROM users ORDER BY name');
$all_users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title            = clean_input($_POST['title']            ?? '');
    $description      = clean_input($_POST['description']      ?? '');
    $priority         = clean_input($_POST['priority']         ?? 'medium');
    $status           = clean_input($_POST['status']           ?? 'open');
    $due_date         = clean_input($_POST['due_date']         ?? '');
    $assigned_user_id = clean_input($_POST['assigned_user_id'] ?? '');

    if ($title === '')                   $errors[] = 'Title is required.';
    elseif (strlen($title) > 200)        $errors[] = 'Title must be 200 characters or fewer.';

    if (!in_array($priority, ['low', 'medium', 'high'], true))
        $errors[] = 'Please choose a valid priority.';

    if (!in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true))
        $errors[] = 'Please choose a valid status.';

    if ($due_date !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $due_date);
        if (!$d || $d->format('Y-m-d') !== $due_date)
            $errors[] = 'Due date must be a valid date (YYYY-MM-DD).';
    }

    $assignee_value = null;
    if ($assigned_user_id !== '') {
        $assignee_int = (int)$assigned_user_id;
        $assignee_valid = false;
        foreach ($all_users as $u) {
            if ((int)$u['user_id'] === $assignee_int) { $assignee_valid = true; break; }
        }
        if (!$assignee_valid) $errors[] = 'Selected assignee is invalid.';
        else $assignee_value = $assignee_int;
    }

    if (empty($errors)) {
        $description_value = $description !== '' ? $description : null;
        $due_value         = $due_date    !== '' ? $due_date    : null;

        $stmt = $conn->prepare(
            'UPDATE tickets
                SET title = ?, description = ?, priority = ?, status = ?,
                    due_date = ?, assigned_user_id = ?
              WHERE ticket_id = ?'
        );
        $stmt->bind_param(
            'sssssii',
            $title,
            $description_value,
            $priority,
            $status,
            $due_value,
            $assignee_value,
            $id
        );
        $stmt->execute();
        $stmt->close();

        flash_set('success', 'Ticket #' . $id . ' updated.');
        redirect('ticket.php?id=' . $id);
    }
}

$page_title = 'Edit ticket #' . $id;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <p class="text-muted" style="margin: 0 0 4px;">Ticket #<?= (int)$id ?></p>
        <h1>Edit ticket</h1>
    </div>
    <div class="page-intro__actions">
        <a class="btn btn--secondary" href="<?= e(url('ticket.php?id=' . $id)) ?>">Cancel</a>
    </div>
</section>

<?php if (!empty($errors)): ?>
    <div class="flash flash--error">
        <ul style="margin:0; padding-left:20px;">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <form class="data-form" method="post" action="<?= e(url('ticket_edit.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="ticket_id" value="<?= (int)$id ?>">
        <p>
            <label for="title">Title <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                   value="<?= e($title) ?>" required maxlength="200">
        </p>
        <p>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?= e($description) ?></textarea>
        </p>
        <div class="form-row">
            <p>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $priority === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="due_date">Due date</label>
                <input type="date" id="due_date" name="due_date" value="<?= e($due_date) ?>">
            </p>
            <p>
                <label for="assigned_user_id">Assignee</label>
                <select id="assigned_user_id" name="assigned_user_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($all_users as $u): ?>
                        <option value="<?= (int)$u['user_id'] ?>"
                                <?= (string)$assigned_user_id === (string)$u['user_id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>
        <p>
            <button type="submit" class="btn btn--primary">Save changes</button>
        </p>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
