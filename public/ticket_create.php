<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'New ticket';

$errors      = [];
$title       = '';
$description = '';
$priority    = 'medium';
$due_date    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = clean_input($_POST['title']       ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $priority    = clean_input($_POST['priority']    ?? 'medium');
    $due_date    = clean_input($_POST['due_date']    ?? '');

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

    if (empty($errors)) {
        $description_value = $description !== '' ? $description : null;
        $due_value         = $due_date    !== '' ? $due_date    : null;

        $stmt = $conn->prepare(
            'INSERT INTO tickets (title, description, priority, due_date, created_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssi',
            $title,
            $description_value,
            $priority,
            $due_value,
            $current_user_id
        );
        $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();

        flash_set('success', 'Ticket #' . $new_id . ' created.');
        redirect('dashboard.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <h1>New ticket</h1>
        <p class="text-muted">Capture an issue or task for your team to pick up.</p>
    </div>
    <a class="btn btn--secondary" href="<?= e(url('dashboard.php')) ?>">Cancel</a>
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
        </div>
        <p>
            <button type="submit" class="btn btn--primary">Create ticket</button>
        </p>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
