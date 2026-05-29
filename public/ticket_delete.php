<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('error', 'Invalid request.');
    redirect('tickets.php');
}

$id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
if ($id <= 0) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

$stmt = $conn->prepare('SELECT ticket_id FROM tickets WHERE ticket_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

if (!$exists) {
    flash_set('error', 'Ticket not found.');
    redirect('tickets.php');
}

$stmt = $conn->prepare('DELETE FROM tickets WHERE ticket_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

flash_set('success', 'Ticket #' . $id . ' deleted.');
redirect('tickets.php');
