<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Auto-create messages table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(20) DEFAULT '',
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Mark as read
if (isset($_POST['mark_read'])) {
    $id = intval($_POST['msg_id']);
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: messages.php");
    exit();
}

// Delete message
if (isset($_POST['delete_msg'])) {
    $id = intval($_POST['msg_id']);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: messages.php");
    exit();
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE messages SET is_read = 1");
    header("Location: messages.php");
    exit();
}

$messages    = $conn->query("SELECT * FROM messages ORDER BY sent_at DESC")->fetch_all(MYSQLI_ASSOC);
$unreadCount = $conn->query("SELECT COUNT(*) as c FROM messages WHERE is_read = 0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages – RIMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff0f5); min-height: 100vh; padding-top: 70px; }

        .navbar { position: fixed; top: 0; left: 0; width: 100%; background: linear-gradient(90deg, #d63384, #c2185b); padding: 12px 20px; z-index: 1000; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .navbar a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 0.88rem; font-weight: 500; transition: background 0.2s; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .navbar a.active { background: white; color: #d63384; font-weight: 700; }
        .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }
        .unread-badge { background: #ff4444; color: white; border-radius: 50%; font-size: 0.7rem; padding: 1px 6px; font-weight: 700; margin-left: 4px; vertical-align: middle; }

        .container { width: 92%; max-width: 900px; margin: 30px auto; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        h2 { color: #d63384; font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .badge-count { background: #fce8f2; color: #d63384; border-radius: 20px; padding: 3px 12px; font-size: 0.82rem; font-weight: 700; }

        .btn-mark-all { padding: 0.6rem 1.2rem; background: white; color: #d63384; border: 1.5px solid #d63384; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-mark-all:hover { background: #d63384; color: white; }

        .msg-card { background: white; border-radius: 14px; padding: 1.4rem 1.6rem; margin-bottom: 1rem; box-shadow: 0 4px 18px rgba(214,51,132,0.08); border-left: 4px solid #f0d0e0; transition: border-color 0.2s; }
        .msg-card.unread { border-left-color: #d63384; background: #fff9fc; }
        .msg-card:hover { border-left-color: #d63384; }

        .msg-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; gap: 1rem; }
        .msg-sender { display: flex; align-items: center; gap: 0.8rem; }
        .msg-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #d63384, #c2185b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; flex-shrink: 0; }
        .msg-name { font-weight: 700; font-size: 0.95rem; color: #333; }
        .msg-contact { font-size: 0.78rem; color: #aaa; margin-top: 2px; }
        .msg-contact a { color: #d63384; text-decoration: none; }
        .msg-contact a:hover { text-decoration: underline; }

        .msg-date { font-size: 0.78rem; color: #aaa; white-space: nowrap; }
        .unread-dot { display: inline-block; width: 8px; height: 8px; background: #d63384; border-radius: 50%; margin-left: 6px; vertical-align: middle; }

        .msg-body { font-size: 0.9rem; color: #444; line-height: 1.6; background: #fff9f5; border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 0.8rem; }

        .msg-actions { display: flex; gap: 0.6rem; justify-content: flex-end; }
        .btn-read { padding: 0.4rem 0.9rem; background: #e0fdf4; color: #065f46; border: 1px solid #6ee7b7; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-read:hover { background: #27ae60; color: white; border-color: #27ae60; }
        .btn-del { padding: 0.4rem 0.9rem; background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-del:hover { background: #e74c3c; color: white; border-color: #e74c3c; }

        .empty { text-align: center; padding: 4rem 2rem; background: white; border-radius: 16px; box-shadow: 0 4px 18px rgba(214,51,132,0.08); color: #ccc; }
        .empty i { font-size: 3.5rem; display: block; margin-bottom: 1rem; }
        .empty p { font-size: 1rem; color: #aaa; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="messages.php" class="active">
        Messages
        <?php if ($unreadCount > 0): ?>
        <span class="unread-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">

    <div class="top-bar">
        <h2>
            <i class="fas fa-envelope"></i> Messages
            <?php if ($unreadCount > 0): ?>
            <span class="badge-count"><?= $unreadCount ?> unread</span>
            <?php endif; ?>
        </h2>
        <?php if ($unreadCount > 0): ?>
        <form method="POST">
            <button type="submit" name="mark_all_read" class="btn-mark-all">
                <i class="fas fa-check-double"></i> Mark All as Read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($messages)): ?>
        <div class="empty">
            <i class="fas fa-inbox"></i>
            <p>No messages yet.<br>Messages from customers will appear here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
        <div class="msg-card <?= !$msg['is_read'] ? 'unread' : '' ?>">
            <div class="msg-header">
                <div class="msg-sender">
                    <div class="msg-avatar"><?= strtoupper(substr($msg['name'], 0, 1)) ?></div>
                    <div>
                        <div class="msg-name">
                            <?= htmlspecialchars($msg['name']) ?>
                            <?php if (!$msg['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                        </div>
                        <div class="msg-contact">
                            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($msg['email']) ?>
                            </a>
                            <?php if (!empty($msg['phone'])): ?>
                            &nbsp;·&nbsp;
                            <a href="tel:<?= htmlspecialchars($msg['phone']) ?>">
                                <i class="fas fa-phone"></i> <?= htmlspecialchars($msg['phone']) ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="msg-date">
                    <i class="fas fa-clock"></i>
                    <?= date('d M Y, h:i A', strtotime($msg['sent_at'])) ?>
                </div>
            </div>

            <div class="msg-body"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>

            <div class="msg-actions">
                <?php if (!$msg['is_read']): ?>
                <form method="POST">
                    <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                    <button type="submit" name="mark_read" class="btn-read">
                        <i class="fas fa-check"></i> Mark as Read
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirm('Delete this message?')">
                    <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                    <button type="submit" name="delete_msg" class="btn-del">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

</body>
</html>