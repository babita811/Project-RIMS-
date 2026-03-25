<?php
session_start();

// 1. Security Check
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// 2. Validate Input
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

// 3. Insert Reply into the MESSAGES table (Self-referencing)
// We set reply_to_id to the ID of the original message.
// Note: We use 'message' column instead of 'reply_text' to match your table schema.
$stmt = $conn->prepare(
    "INSERT INTO messages (message, reply_to_id, sent_at, is_read) 
     VALUES (?, ?, NOW(), 1)"
);

$stmt->bind_param("si", $reply_text, $message_id);
$stmt->execute();
$stmt->close();

// 4. Mark the original message as Read
$upd = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
$upd->bind_param("i", $message_id);
$upd->execute();
$upd->close();

// 5. Email Notification
$subject   = "Reply from RIMS Flower Shop";
$body      = "Dear $to_name,\n\n$reply_text\n\n---\nThank you,\nRIMS Flower Shop\n";
$headers   = "From: noreply@rims.com\r\nReply-To: noreply@rims.com\r\nX-Mailer: PHP/" . phpversion();

// Use @mail to suppress warnings if your local XAMPP isn't configured for mail yet
$mail_sent = @mail($to_email, $subject, $body, $headers);

// 6. Redirect back with status
header("Location: messages.php?replied=" . ($mail_sent ? "1" : "2"));
exit();
?>