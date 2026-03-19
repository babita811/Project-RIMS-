<?php
session_start();

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

if (!isset($_POST['message_id'], $_POST['reply_text'], $_POST['to_email'])) {
    header("Location: messages.php");
    exit();
}

$message_id = intval($_POST['message_id']);
$reply_text = trim($_POST['reply_text']);
$to_email   = trim($_POST['to_email']);
$to_name    = trim($_POST['to_name'] ?? 'Customer');

if (empty($reply_text)) {
    header("Location: messages.php?error=empty_reply");
    exit();
}

$stmt = $conn->prepare(
    "INSERT INTO message_replies (message_id, reply_text, replied_at, sender)
     VALUES (?, ?, NOW(), 'admin')"
);

$stmt->bind_param("is", $message_id, $reply_text);
$stmt->execute();
$stmt->close();

$upd = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
$upd->bind_param("i", $message_id);
$upd->execute();
$upd->close();

$subject   = "Reply from RIMS Flower Shop";
$body      = "Dear $to_name,\n\n$reply_text\n\n---\nRIMS Flower Shop\n";
$headers   = "From: noreply@rims.com\r\nContent-Type: text/plain; charset=UTF-8";
$mail_sent = mail($to_email, $subject, $body, $headers);

header("Location: messages.php?replied=" . ($mail_sent ? "1" : "2"));
exit();
?>