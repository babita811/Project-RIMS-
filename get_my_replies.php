<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$client_id = $_SESSION['clientId'];

$stmt = $conn->prepare(
    "SELECT id, message, is_read, sent_at
     FROM messages
     WHERE user_id = ?
     ORDER BY sent_at DESC"
);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $msg_id = $row['id'];

    $rStmt = $conn->prepare(
        "SELECT reply_text, replied_at, sender FROM message_replies
         WHERE message_id = ? ORDER BY replied_at ASC"
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