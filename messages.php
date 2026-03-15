<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'db.php';
$page = basename($_SERVER['PHP_SELF']);

if (isset($_POST['mark_read'])) {
    $id = intval($_POST['message_id']);
    $conn->query("UPDATE messages SET is_read = 1 WHERE id = $id");
    header("Location: messages.php"); exit();
}
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE messages SET is_read = 1");
    header("Location: messages.php"); exit();
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM message_replies WHERE message_id = $id");
    $conn->query("DELETE FROM messages WHERE id = $id");
    header("Location: messages.php"); exit();
}

$messages = $conn->query(
    "SELECT m.*, (SELECT COUNT(*) FROM message_replies r WHERE r.message_id = m.id) AS reply_count
     FROM messages m ORDER BY m.sent_at DESC"
);
$unread = $conn->query("SELECT COUNT(*) as c FROM messages WHERE is_read = 0")->fetch_assoc()['c'];

$status = '';
if (isset($_GET['replied'])) {
    $status = $_GET['replied'] == 1
        ? '<div class="alert success">✅ Reply sent successfully!</div>'
        : '<div class="alert warning">⚠️ Reply saved but email could not be sent.</div>';
}
if (isset($_GET['error']) && $_GET['error'] === 'empty_reply') {
    $status = '<div class="alert error">❌ Reply cannot be empty.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages – RIMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff5f9); padding-top: 70px; min-height: 100vh; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; background: linear-gradient(90deg, #d63384, #c2185b); padding: 12px 20px; z-index: 1000; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .navbar a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 0.88rem; font-weight: 500; transition: 0.2s; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .navbar a.active { background: white; color: #d63384; font-weight: 700; }
        .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }
        .unread-badge { background: #ff4444; color: white; border-radius: 50%; font-size: 0.7rem; padding: 1px 6px; font-weight: 700; margin-left: 3px; vertical-align: middle; }
        .page-wrap { width: 92%; max-width: 1000px; margin: 28px auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .page-header h2 { color: #c2185b; font-size: 1.6rem; font-weight: 700; }
        .btn { padding: 8px 18px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-gray  { background: #eee; color: #555; } .btn-gray:hover  { background: #ddd; }
        .btn-green { background: #43a047; color: white; } .btn-green:hover { background: #2e7d32; }
        .btn-blue  { background: #1565c0; color: white; } .btn-blue:hover  { background: #0d47a1; }
        .btn-red   { background: #e53935; color: white; } .btn-red:hover   { background: #c62828; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 600; }
        .alert.success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #43a047; }
        .alert.warning { background: #fff8e1; color: #e65100; border-left: 4px solid #ffa000; }
        .alert.error   { background: #fce4ec; color: #880e4f; border-left: 4px solid #e53935; }
        .msg-card { background: white; border-radius: 14px; box-shadow: 0 4px 16px rgba(214,51,132,0.1); margin-bottom: 18px; overflow: hidden; border-left: 5px solid #d63384; }
        .msg-card.unread { border-left-color: #e53935; background: #fff8f9; }
        .msg-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 16px 20px 10px; flex-wrap: wrap; gap: 10px; }
        .sender-name { font-size: 1.05rem; font-weight: 700; color: #c2185b; }
        .sender-info { font-size: 13px; color: #777; margin-top: 3px; }
        .sender-info span { margin-right: 14px; }
        .msg-badges { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .badge-unread  { background: #e53935; color: white; font-size: 11px; padding: 2px 10px; border-radius: 20px; font-weight: 700; }
        .badge-read    { background: #e8f5e9; color: #2e7d32; font-size: 11px; padding: 2px 10px; border-radius: 20px; font-weight: 600; }
        .badge-replied { background: #e3f2fd; color: #0d47a1; font-size: 11px; padding: 2px 10px; border-radius: 20px; font-weight: 600; }
        .msg-time { font-size: 12px; color: #aaa; }
        .msg-body { padding: 4px 20px 14px; color: #333; font-size: 14.5px; line-height: 1.6; border-top: 1px solid #f8e0ec; }
        .msg-actions { display: flex; gap: 8px; padding: 10px 20px 14px; flex-wrap: wrap; border-top: 1px solid #f8e0ec; }
        .reply-panel { display: none; background: #f0f7ff; border-top: 1px solid #c8e6ff; padding: 16px 20px; }
        .reply-panel.open { display: block; }
        .reply-panel h4 { color: #0d47a1; font-size: 14px; margin-bottom: 8px; }
        .reply-to-info { font-size: 12px; color: #555; margin-bottom: 10px; }
        .reply-panel textarea { width: 100%; min-height: 90px; padding: 10px 14px; border: 1.5px solid #90caf9; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; resize: vertical; outline: none; }
        .reply-panel textarea:focus { border-color: #1565c0; }
        .reply-actions { display: flex; gap: 8px; margin-top: 10px; }
        .prev-replies { background: #f9f9f9; border-top: 1px solid #eee; padding: 12px 20px; }
        .prev-replies h4 { font-size: 13px; color: #777; margin-bottom: 8px; font-weight: 600; }
        .prev-reply-item { background: #e3f2fd; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; font-size: 13.5px; color: #333; line-height: 1.5; }
        .prev-reply-item .reply-time { font-size: 11px; color: #888; margin-top: 5px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #bbb; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; }
        
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="view_sales.php">View Sales</a>
    <a href="messages.php" class="active">
        Messages <?php if ($unread > 0): ?><span class="unread-badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="page-wrap">
    <div class="page-header">
        <h2>💬 Customer Messages <?php if ($unread > 0): ?><span style="font-size:14px;color:#e53935;font-weight:normal;">(<?= $unread ?> unread)</span><?php endif; ?></h2>
        <form method="POST" style="display:inline;">
            <button type="submit" name="mark_all_read" class="btn btn-gray">✓ Mark All as Read</button>
        </form>
    </div>

    <?= $status ?>

    <?php if ($messages->num_rows === 0): ?>
        <div class="empty-state"><i class="fas fa-inbox"></i><p>No messages yet.</p></div>
    <?php else: ?>
    <?php while ($msg = $messages->fetch_assoc()):
        $replies  = $conn->query("SELECT * FROM message_replies WHERE message_id = {$msg['id']} ORDER BY replied_at ASC");
        $isUnread = $msg['is_read'] == 0;
        $hasReply = $msg['reply_count'] > 0;
    ?>
    <div class="msg-card <?= $isUnread ? 'unread' : '' ?>">
        <div class="msg-header">
            <div>
                <div class="sender-name"><?= htmlspecialchars($msg['name']) ?></div>
                <div class="sender-info">
                    <span><i class="fas fa-envelope" style="margin-right:4px;"></i><?= htmlspecialchars($msg['email']) ?></span>
                    <?php if (!empty($msg['phone'])): ?><span><i class="fas fa-phone" style="margin-right:4px;"></i><?= htmlspecialchars($msg['phone']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="msg-badges">
                <?php if ($isUnread): ?><span class="badge-unread">● Unread</span><?php else: ?><span class="badge-read">✓ Read</span><?php endif; ?>
                <?php if ($hasReply): ?><span class="badge-replied">↩ Replied (<?= $msg['reply_count'] ?>)</span><?php endif; ?>
                <span class="msg-time"><i class="fas fa-clock" style="margin-right:3px;"></i><?= date('d M Y, h:i A', strtotime($msg['sent_at'])) ?></span>
            </div>
        </div>

        <div class="msg-body"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>

        <div class="msg-actions">
            <button class="btn btn-blue" onclick="toggleReply(<?= $msg['id'] ?>)">
                <i class="fas fa-reply" style="margin-right:5px;"></i> Reply
            </button>
            <?php if ($isUnread): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                <button type="submit" name="mark_read" class="btn btn-green"><i class="fas fa-check" style="margin-right:5px;"></i> Mark as Read</button>
            </form>
            <?php endif; ?>
            <a href="messages.php?delete=<?= $msg['id'] ?>" class="btn btn-red" onclick="return confirm('Delete this message and all replies?')">
                <i class="fas fa-trash" style="margin-right:5px;"></i> Delete
            </a>
        </div>

        <div class="reply-panel" id="reply-<?= $msg['id'] ?>">
            <h4><i class="fas fa-pen" style="margin-right:6px;"></i>Write a Reply</h4>
            <p class="reply-to-info">To: <strong><?= htmlspecialchars($msg['name']) ?></strong> &lt;<?= htmlspecialchars($msg['email']) ?>&gt;</p>
            <form method="POST" action="reply_message.php">
                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                <input type="hidden" name="to_email"   value="<?= htmlspecialchars($msg['email']) ?>">
                <input type="hidden" name="to_name"    value="<?= htmlspecialchars($msg['name']) ?>">
                <textarea name="reply_text" placeholder="Type your reply here..." required></textarea>
                <div class="reply-actions">
                    <button type="submit" class="btn btn-blue"><i class="fas fa-paper-plane" style="margin-right:5px;"></i> Send Reply</button>
                    <button type="button" class="btn btn-gray" onclick="toggleReply(<?= $msg['id'] ?>)">Cancel</button>
                </div>
            </form>
        </div>

        <?php if ($hasReply): ?>
        <div class="prev-replies">
            <h4><i class="fas fa-history" style="margin-right:5px;"></i>Previous Replies (<?= $msg['reply_count'] ?>)</h4>
            <?php while ($r = $replies->fetch_assoc()): ?>
            <div class="prev-reply-item">
                <?= nl2br(htmlspecialchars($r['reply_text'])) ?>
                <div class="reply-time"><i class="fas fa-clock" style="margin-right:3px;"></i><?= date('d M Y, h:i A', strtotime($r['replied_at'])) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <?php endif; ?>
</div>

<script>
function toggleReply(id) {
    const panel = document.getElementById('reply-' + id);
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) panel.querySelector('textarea').focus();
}
</script>
</body>
</html>