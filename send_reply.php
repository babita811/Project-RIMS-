<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data       = json_decode(file_get_contents('php://input'), true);
$message_id = intval($data['message_id'] ?? 0);
$reply_text = trim($data['reply_text']   ?? '');

if (empty($reply_text) || $message_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

// Verify this message belongs to this user
$check = $conn->prepare(
    "SELECT id FROM messages WHERE id = ? AND user_id = ? AND reply_to_id IS NULL"
);
$check->bind_param("ii", $message_id, $_SESSION['clientId']);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}
$check->close();

// Insert reply — user_id set so we know it's a customer reply
$client_id = $_SESSION['clientId'];
$stmt = $conn->prepare(
    "INSERT INTO messages (user_id, phone, message, is_read, sent_at, reply_to_id)
     VALUES (?, '', ?, 1, NOW(), ?)"
);
$stmt->bind_param("isi", $client_id, $reply_text, $message_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
?>