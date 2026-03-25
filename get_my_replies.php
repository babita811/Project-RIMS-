<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$client_id = $_SESSION['clientId'];

// Fetch only parent messages sent by this user
$stmt = $conn->prepare(
    "SELECT id, message, is_read, sent_at
     FROM messages
     WHERE user_id = ? AND reply_to_id IS NULL
     ORDER BY sent_at DESC"
);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $msg_id = $row['id'];

    // Fetch replies for this message (reply_to_id = parent id)
    $rStmt = $conn->prepare(
        "SELECT message as reply_text, sent_at as replied_at,
                CASE WHEN user_id IS NULL THEN 'admin' ELSE 'user' END as sender
         FROM messages
         WHERE reply_to_id = ?
         ORDER BY sent_at ASC"
    );
    $rStmt->bind_param("i", $msg_id);
    $rStmt->execute();
    $rResult = $rStmt->get_result();
    $rStmt->close();

    $replies = [];
    while ($r = $rResult->fetch_assoc()) {
        $replies[] = [
            'text'   => $r['reply_text'],
            'time'   => $r['replied_at'],
            'sender' => $r['sender']
        ];
    }

    $messages[] = [
        'id'      => $msg_id,
        'message' => $row['message'],
        'is_read' => $row['is_read'],
        'sent_at' => $row['sent_at'],
        'replies' => $replies
    ];
}

echo json_encode(['success' => true, 'messages' => $messages]);
$conn->close();
?>