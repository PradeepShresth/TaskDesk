<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if (!function_exists('render_comment')) {
    function render_comment($c, $children_by_parent, $ticket_id) {
        $cid = (int)$c['comment_id'];
        $children = $children_by_parent[$cid] ?? [];
        ?>
        <li class="comment" id="comment-<?= $cid ?>">
            <div class="comment__meta">
                <span class="comment__author"><?= e($c['author_name']) ?></span>
                <span class="comment__time">
                    &middot; <?= e(date('M j, Y g:i A', strtotime($c['created_at']))) ?>
                </span>
            </div>
            <p class="comment__body"><?= e($c['comment_text']) ?></p>

            <details style="margin-top: 6px;">
                <summary class="text-muted" style="cursor: pointer; font-size: 0.875rem;">Reply</summary>
                <form class="data-form" method="post" action="<?= e(url('comment_post.php')) ?>" style="margin-top: var(--space-2);">
                    <input type="hidden" name="ticket_id" value="<?= (int)$ticket_id ?>">
                    <input type="hidden" name="parent_comment_id" value="<?= $cid ?>">
                    <p>
                        <textarea name="comment_text" rows="2" placeholder="Write a reply…" required></textarea>
                    </p>
                    <p>
                        <button type="submit" class="btn btn--secondary btn--sm">Post reply</button>
                    </p>
                </form>
            </details>

            <?php if (!empty($children)): ?>
                <ul class="comment__replies">
                    <?php foreach ($children as $child) {
                        render_comment($child, $children_by_parent, $ticket_id);
                    } ?>
                </ul>
            <?php endif; ?>
        </li>
        <?php
    }
}

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : (isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0);

if ($id <= 0) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'set_status') {

    $new_status = clean_input($_POST['status'] ?? '');
    if (in_array($new_status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
        $stmt = $conn->prepare('SELECT status FROM tickets WHERE ticket_id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $old_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $old_status = $old_row['status'] ?? null;

        $stmt = $conn->prepare('UPDATE tickets SET status = ? WHERE ticket_id = ?');
        $stmt->bind_param('si', $new_status, $id);
        $stmt->execute();
        $stmt->close();

        if ($old_status !== null && $old_status !== $new_status) {
            log_ticket_event($conn, $id, $current_user_id, 'status_changed', $old_status, $new_status);
        }

        flash_set('success', 'Status updated to ' . str_replace('_', ' ', $new_status) . '.');
    } else {
        flash_set('error', 'Invalid status.');
    }
    redirect('ticket.php?id=' . $id);
}

$stmt = $conn->prepare(
    'SELECT t.*,
            ua.name AS assignee_name,
            uc.name AS creator_name
       FROM tickets t
  LEFT JOIN users ua ON ua.user_id = t.assigned_user_id
  LEFT JOIN users uc ON uc.user_id = t.created_by
      WHERE t.ticket_id = ?
      LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

$stmt = $conn->prepare(
    'SELECT ticket_id, title, status, priority, due_date
       FROM tickets
      WHERE parent_ticket_id = ?
      ORDER BY created_at ASC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$subtasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$subtasks_total = count($subtasks);
$subtasks_done  = 0;
foreach ($subtasks as $s) {
    if (in_array($s['status'], ['resolved', 'closed'], true)) $subtasks_done++;
}
$subtasks_pct = $subtasks_total > 0
    ? (int)round(($subtasks_done / $subtasks_total) * 100)
    : 0;

$stmt = $conn->prepare(
    'SELECT c.comment_id, c.parent_comment_id, c.user_id, c.comment_text,
            c.created_at, u.name AS author_name
       FROM comments c
       JOIN users u ON u.user_id = c.user_id
      WHERE c.ticket_id = ?
      ORDER BY c.created_at ASC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$all_comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$children_by_parent = [];
$top_comments = [];
foreach ($all_comments as $c) {
    if ($c['parent_comment_id'] === null) {
        $top_comments[] = $c;
    } else {
        $children_by_parent[(int)$c['parent_comment_id']][] = $c;
    }
}

// Ticket history (audit log) — newest first.
$stmt = $conn->prepare(
    'SELECT h.event_type, h.old_value, h.new_value, h.created_at, u.name AS actor_name
       FROM ticket_history h
  LEFT JOIN users u ON u.user_id = h.user_id
      WHERE h.ticket_id = ?
      ORDER BY h.created_at DESC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Ticket #' . $ticket['ticket_id'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <div>
        <p class="text-muted" style="margin: 0 0 4px;">
            Ticket #<?= (int)$ticket['ticket_id'] ?>
        </p>
        <h1><?= e($ticket['title']) ?></h1>
        <p class="text-muted">
            <span class="status-text status-text--<?= e($ticket['status']) ?>">
                <?= e(str_replace('_', ' ', $ticket['status'])) ?>
            </span>
            &middot;
            <span class="badge badge--<?= e($ticket['priority']) ?>"><?= e($ticket['priority']) ?></span>
            <?php if ($ticket['due_date']): ?>
                &middot;
                <?php $is_overdue = ticket_is_overdue($ticket['due_date'], $ticket['status']); ?>
                <span class="<?= $is_overdue ? 'overdue' : '' ?>">
                    Due <?= e(date('M j, Y', strtotime($ticket['due_date']))) ?>
                </span>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-intro__actions">
        <a class="btn btn--secondary" href="<?= e(url('tickets.php')) ?>">&larr; Back</a>
        <a class="btn btn--secondary" href="<?= e(url('ticket_edit.php?id=' . (int)$ticket['ticket_id'])) ?>">Edit</a>
        <?php if (is_admin()): ?>
            <form method="post" action="<?= e(url('ticket_delete.php')) ?>"
                  style="display: inline;"
                  onsubmit="return confirm('Delete this ticket? This also removes its subtasks and comments.');">
                <input type="hidden" name="ticket_id" value="<?= (int)$ticket['ticket_id'] ?>">
                <button type="submit" class="btn btn--danger">Delete</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<div class="card">
    <h2>Quick status update</h2>
    <form method="post" action="<?= e(url('ticket.php')) ?>" class="status-form">
        <input type="hidden" name="action" value="set_status">
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['ticket_id'] ?>">
        <?php foreach (['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $label): ?>
            <button type="submit" name="status" value="<?= e($val) ?>"
                    class="btn btn--sm <?= $ticket['status'] === $val ? 'btn--primary' : 'btn--secondary' ?>">
                <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </form>
</div>

<div class="card">
    <h2>Description</h2>
    <?php if (!empty($ticket['description'])): ?>
        <p style="white-space: pre-wrap; margin: 0;"><?= e($ticket['description']) ?></p>
    <?php else: ?>
        <p class="text-muted" style="margin: 0;">No description provided.</p>
    <?php endif; ?>
</div>

<div class="card">
    <div class="page-intro" style="margin-bottom: var(--space-3);">
        <div>
            <h2 style="margin: 0;">Subtasks</h2>
            <?php if ($subtasks_total > 0): ?>
                <p class="text-muted" style="margin: 4px 0 0;">
                    <?= $subtasks_done ?> of <?= $subtasks_total ?> done
                </p>
            <?php endif; ?>
        </div>
        <a class="btn btn--secondary btn--sm"
           href="<?= e(url('ticket_create.php?parent_id=' . (int)$ticket['ticket_id'])) ?>">
            + Add subtask
        </a>
    </div>

    <?php if ($subtasks_total === 0): ?>
        <p class="text-muted" style="margin: 0;">No subtasks yet.</p>
    <?php else: ?>
        <div class="progress" style="margin-bottom: var(--space-4);">
            <div class="progress__bar" style="width: <?= (int)$subtasks_pct ?>%;"></div>
        </div>
        <ul class="subtask-list">
            <?php foreach ($subtasks as $s): ?>
                <li>
                    <a href="<?= e(url('ticket.php?id=' . (int)$s['ticket_id'])) ?>">
                        <?= e($s['title']) ?>
                    </a>
                    <span>
                        <span class="status-text status-text--<?= e($s['status']) ?>">
                            <?= e(str_replace('_', ' ', $s['status'])) ?>
                        </span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Details</h2>
    <dl class="detail-list">
        <dt>Assignee</dt>
        <dd>
            <?php if (!empty($ticket['assignee_name'])): ?>
                <?= e($ticket['assignee_name']) ?>
            <?php else: ?>
                <span class="text-muted">Unassigned</span>
            <?php endif; ?>
        </dd>

        <dt>Created by</dt>
        <dd><?= e($ticket['creator_name'] ?? 'Unknown') ?></dd>

        <dt>Created</dt>
        <dd><?= e(date('M j, Y g:i A', strtotime($ticket['created_at']))) ?></dd>

        <dt>Last updated</dt>
        <dd><?= e(date('M j, Y g:i A', strtotime($ticket['updated_at']))) ?></dd>
    </dl>
</div>

<div class="card">
    <h2>History (<?= count($history) ?>)</h2>
    <?php if (empty($history)): ?>
        <p class="text-muted" style="margin: 0;">No changes recorded yet.</p>
    <?php else: ?>
        <ul class="history-list">
            <?php foreach ($history as $h): ?>
                <li class="history-item">
                    <span class="history-item__when">
                        <?= e(date('M j, Y g:i A', strtotime($h['created_at']))) ?>
                    </span>
                    <span class="history-item__what">
                        <?php
                        $actor = $h['actor_name'] ?? 'Someone';
                        switch ($h['event_type']) {
                            case 'created':
                                echo e($actor) . ' created the ticket';
                                break;
                            case 'status_changed':
                                echo e($actor) . ' changed status from <strong>'
                                    . e(str_replace('_', ' ', $h['old_value'] ?? '?')) . '</strong> to <strong>'
                                    . e(str_replace('_', ' ', $h['new_value'] ?? '?')) . '</strong>';
                                break;
                            case 'priority_changed':
                                echo e($actor) . ' changed priority from <strong>'
                                    . e($h['old_value'] ?? '?') . '</strong> to <strong>'
                                    . e($h['new_value'] ?? '?') . '</strong>';
                                break;
                            case 'assignee_changed':
                                $from = $h['old_value'] ? '#' . $h['old_value'] : 'Unassigned';
                                $to   = $h['new_value'] ? '#' . $h['new_value'] : 'Unassigned';
                                echo e($actor) . ' changed assignee from <strong>'
                                    . e($from) . '</strong> to <strong>' . e($to) . '</strong>';
                                break;
                            case 'edited':
                                echo e($actor) . ' edited the ticket';
                                break;
                            default:
                                echo e($actor) . ' ' . e($h['event_type']);
                        }
                        ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Add a comment</h2>
    <form class="data-form" method="post" action="<?= e(url('comment_post.php')) ?>">
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['ticket_id'] ?>">
        <p>
            <label for="comment_text" class="sr-only">Comment</label>
            <textarea id="comment_text" name="comment_text" rows="3"
                      placeholder="Share an update, ask a question…" required></textarea>
        </p>
        <p>
            <button type="submit" class="btn btn--primary">Post comment</button>
        </p>
    </form>
</div>

<div class="card">
    <h2>Comments (<?= count($all_comments) ?>)</h2>

    <?php if (empty($top_comments)): ?>
        <p class="text-muted" style="margin: 0;">No comments yet.</p>
    <?php else: ?>
        <ul class="comment-list">
            <?php foreach ($top_comments as $c) {
                render_comment($c, $children_by_parent, (int)$ticket['ticket_id']);
            } ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
