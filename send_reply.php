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

// Make sure this message belongs to this user
$check = $conn->prepare("SELECT id FROM messages WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $message_id, $_SESSION['clientId']);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}
$check->close();

$stmt = $conn->prepare(
    "INSERT INTO message_replies (message_id, reply_text, replied_at, sender)
     VALUES (?, ?, NOW(), 'user')"
);
$stmt->bind_param("is", $message_id, $reply_text);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
?>