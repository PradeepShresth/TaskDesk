<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'New ticket';

$errors           = [];
$title            = '';
$description      = '';
$priority         = 'medium';
$due_date         = '';
$assigned_user_id = '';

$parent_ticket_id = '';
if (isset($_GET['parent_id']))           $parent_ticket_id = (string)(int)$_GET['parent_id'];
elseif (isset($_POST['parent_ticket_id'])) $parent_ticket_id = clean_input($_POST['parent_ticket_id']);

$parent_ticket = null;
if ($parent_ticket_id !== '' && ctype_digit($parent_ticket_id)) {
    $stmt = $conn->prepare('SELECT ticket_id, title FROM tickets WHERE ticket_id = ? LIMIT 1');
    $pid = (int)$parent_ticket_id;
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $parent_ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$parent_ticket) $parent_ticket_id = '';
}

$users_result = $conn->query('SELECT user_id, name FROM users ORDER BY name');
$all_users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title            = clean_input($_POST['title']            ?? '');
    $description      = clean_input($_POST['description']      ?? '');
    $priority         = clean_input($_POST['priority']         ?? 'medium');
    $due_date         = clean_input($_POST['due_date']         ?? '');
    $assigned_user_id = clean_input($_POST['assigned_user_id'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) > 200) {
        $errors[] = 'Title must be 200 characters or fewer.';
    }

    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        $errors[] = 'Please choose a valid priority.';
    }

    if ($due_date !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $due_date);
        if (!$d || $d->format('Y-m-d') !== $due_date) {
            $errors[] = 'Due date must be a valid date (YYYY-MM-DD).';
        }
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
        $parent_value      = ($parent_ticket && $parent_ticket_id !== '')
            ? (int)$parent_ticket_id : null;

        $stmt = $conn->prepare(
            'INSERT INTO tickets
                (title, description, priority, due_date,
                 assigned_user_id, parent_ticket_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssiii',
            $title,
            $description_value,
            $priority,
            $due_value,
            $assignee_value,
            $parent_value,
            $current_user_id
        );
        $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();

        flash_set(
            'success',
            ($parent_value ? 'Subtask' : 'Ticket') . ' #' . $new_id . ' created.'
        );
        redirect($parent_value ? 'ticket.php?id=' . $parent_value : 'dashboard.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1><?= $parent_ticket ? 'New subtask' : 'New ticket' ?></h1>
        <?php if ($parent_ticket): ?>
            <p class="text-muted">
                Adding under
                <a href="<?= e(url('ticket.php?id=' . (int)$parent_ticket['ticket_id'])) ?>">
                    #<?= (int)$parent_ticket['ticket_id'] ?> <?= e($parent_ticket['title']) ?>
                </a>
            </p>
        <?php else: ?>
            <p class="text-muted">Capture an issue or task for your team to pick up.</p>
        <?php endif; ?>
    </div>
    <a class="btn btn--secondary" href="<?= e(url($parent_ticket ? 'ticket.php?id=' . (int)$parent_ticket['ticket_id'] : 'dashboard.php')) ?>">Cancel</a>
</section>

<?php if (!empty($errors)): ?>
    <div class="flash flash--error">
        <ul style="margin:0; padding-left: 20px;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <form class="data-form" method="post" action="<?= e(url('ticket_create.php')) ?>" novalidate>
        <?php if ($parent_ticket): ?>
            <input type="hidden" name="parent_ticket_id" value="<?= (int)$parent_ticket['ticket_id'] ?>">
        <?php endif; ?>
        <p>
            <label for="title">Title <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                   value="<?= e($title) ?>" required maxlength="200" autofocus>
        </p>
        <p>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?= e($description) ?></textarea>
        </p>
        <div class="form-row">
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
            <button type="submit" class="btn btn--primary">Create ticket</button>
        </p>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
