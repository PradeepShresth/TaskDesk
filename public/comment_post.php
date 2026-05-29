<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tickets.php');
}

$ticket_id        = isset($_POST['ticket_id'])         ? (int)$_POST['ticket_id']         : 0;
$parent_comment_id = isset($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : 0;
$comment_text     = clean_input($_POST['comment_text'] ?? '');

if ($ticket_id <= 0) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

$stmt = $conn->prepare('SELECT ticket_id FROM tickets WHERE ticket_id = ? LIMIT 1');
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$stmt->store_result();
$ticket_exists = $stmt->num_rows > 0;
$stmt->close();

if (!$ticket_exists) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

if ($comment_text === '') {
    flash_set('error', 'Comment text cannot be empty.');
    redirect('ticket.php?id=' . $ticket_id);
}

if (strlen($comment_text) > 5000) {
    flash_set('error', 'Comment is too long (max 5000 characters).');
    redirect('ticket.php?id=' . $ticket_id);
}

$parent_value = null;
if ($parent_comment_id > 0) {
    $stmt = $conn->prepare(
        'SELECT comment_id FROM comments WHERE comment_id = ? AND ticket_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $parent_comment_id, $ticket_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $parent_value = $parent_comment_id;
    $stmt->close();
}

$stmt = $conn->prepare(
    'INSERT INTO comments (ticket_id, parent_comment_id, user_id, comment_text)
     VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('iiis', $ticket_id, $parent_value, $current_user_id, $comment_text);
$stmt->execute();
$stmt->close();

flash_set('success', 'Comment posted.');
redirect('ticket.php?id=' . $ticket_id);
