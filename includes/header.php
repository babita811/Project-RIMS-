<?php
// Fetch unread message count for navbar badge
$unreadCount = 0;
if (isset($conn)) {
    $unreadResult = $conn->query("SELECT COUNT(*) as c FROM messages WHERE is_read = 0");
    if ($unreadResult) {
        $unreadCount = $unreadResult->fetch_assoc()['c'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Flower</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { padding-top: 60px; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff0f5);}
        .navbar { position: fixed; top:0; left:0; width:100%; background: linear-gradient(90deg, #d63384, #c2185b); padding:15px; z-index:1000;}
        .navbar a { color:white; text-decoration:none; padding:10px 18px; border-radius:20px;}
        .navbar a.active { background:white; color:#d63384;}
        .container { width:60%; margin:30px auto; background: rgba(255,255,255,0.8); padding:30px; border-radius:15px; box-shadow:0 5px 15px rgba(214,51,132,0.2);}
        h2 { text-align:center; color:#d63384; }
        input, select { width:100%; padding:12px; margin:10px 0; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; }
        input[type="submit"] { background-color:#5563DE; color:white; border:none; cursor:pointer; font-weight:bold; transition:0.3s; }
        input[type="submit"]:hover { background-color:#3742a3; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }

        /* Rs label for price */
        .price-wrapper { display:flex; align-items:center; }
        .price-wrapper span { padding:10px; background:#eee; border-radius:8px 0 0 8px; border:1px solid #ccc; }
        .price-wrapper input { flex:1; border-radius:0 8px 8px 0; border-left:none; padding:12px; }

        /* Unread badge */
        .unread-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            padding: 1px 6px;
            font-weight: 700;
            margin-left: 4px;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php" class="<?= $page=='admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="add_product.php" class="<?= $page=='add_product.php' ? 'active' : '' ?>">Add Flower</a>
    <a href="view_products.php" class="<?= $page=='view_products.php' ? 'active' : '' ?>">View Flowers</a>
    <a href="sales.php" class="<?= $page=='sales.php' ? 'active' : '' ?>">Sales</a>
    <a href="analytics.php" class="<?= $page=='analytics.php' ? 'active' : '' ?>">Analytics</a>
    <a href="view_sales.php" class="<?= $page=='view_sales.php' ? 'active' : '' ?>">View Sales</a>
    <a href="messages.php" class="<?= $page=='messages.php' ? 'active' : '' ?>">
        Messages
        <?php if ($unreadCount > 0): ?>
            <span class="unread-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <a href="logout.php">Logout</a>
</div>