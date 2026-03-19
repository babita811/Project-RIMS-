<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit();
}

$data    = json_decode(file_get_contents('php://input'), true);
$phone   = trim($data['phone']   ?? '');
$message = trim($data['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Please write your message.']);
    exit();
}

$client_id = $_SESSION['clientId'] ?? NULL;

$stmt = $conn->prepare(
    "INSERT INTO messages (user_id, phone, message, is_read, sent_at)
     VALUES (?, ?, ?, 0, NOW())"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iss", $client_id, $phone, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed: ' . $stmt->error]);
}
$stmt->close();
?>