<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

// Stats
$totalProducts  = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(subtotal),0) as r FROM sales")->fetch_assoc()['r'];
$totalSales     = $conn->query("SELECT COUNT(*) as c FROM sales")->fetch_assoc()['c'];
$lowStock       = $conn->query("SELECT COUNT(*) as c FROM products WHERE quantity <= 5 AND quantity > 0")->fetch_assoc()['c'];
$outOfStock     = $conn->query("SELECT COUNT(*) as c FROM products WHERE quantity = 0")->fetch_assoc()['c'];
$totalCustomers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'")->fetch_assoc()['c'];

// ✅ FIXED: Unread messages count for navbar badge
$unreadMessages = $conn->query("SELECT COUNT(*) as c FROM messages WHERE is_read = 0")->fetch_assoc()['c'];

// Recent sales
$recentSales = $conn->query("
    SELECT s.id, p.name as product, p.image, u.name as customer,
           s.quantity, s.subtotal, s.sale_date
    FROM sales s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.sale_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – RIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffe6f0, #fff5f9, #ffe0ec);
            min-height: 100vh;
            padding-top: 70px;
        }

        /* Petals */
        .petal { position: fixed; top: -50px; font-size: 20px; animation: fall linear infinite; pointer-events: none; z-index: 0; }
        @keyframes fall {
            0%   { transform: translateY(-50px) rotate(0deg); opacity: 0.8; }
            100% { transform: translateY(110vh) rotate(360deg); opacity: 0; }
        }

        /* Navbar */
        .navbar {
            position: fixed; top:0; left:0; width:100%;
            background: linear-gradient(90deg, #d63384, #c2185b);
            padding: 12px 20px; z-index: 1000;
            display: flex; gap: 6px; align-items: center;
        }
        .navbar a {
            color: white; text-decoration: none;
            padding: 8px 16px; border-radius: 20px;
            font-size: 0.88rem; font-weight: 500; transition: background 0.2s;
        }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .navbar a.active { background: white; color: #d63384; font-weight: 700; }
        .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }
        .navbar .admin-name {
            color: rgba(255,255,255,0.85);
            font-size: 0.82rem;
            margin-left: auto;
            margin-right: 0.5rem;
        }
        /* ✅ Badge style */
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

        .container {
            width: 92%; max-width: 1200px;
            margin: 30px auto;
            position: relative; z-index: 1;
        }

        .welcome { text-align: center; margin-bottom: 2rem; }
        .welcome h2 { font-size: 2rem; font-weight: 700; color: #c2185b; margin-bottom: 0.3rem; }
        .welcome p { color: #888; font-size: 0.9rem; }

        /* Stat cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px; margin-bottom: 2rem;
        }
        .stat-card {
            background: white; border-radius: 16px; padding: 1.4rem 1.2rem;
            text-align: center; box-shadow: 0 6px 20px rgba(214,51,132,0.12);
            transition: transform 0.2s, box-shadow 0.2s; border-top: 4px solid #d63384;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(214,51,132,0.2); }
        .stat-card.warning { border-top-color: #ff9800; }
        .stat-card.danger  { border-top-color: #e74c3c; }
        .stat-card.green   { border-top-color: #27ae60; }
        .stat-card.blue    { border-top-color: #3498db; }
        .stat-card i { font-size: 1.8rem; color: #d63384; margin-bottom: 0.6rem; display: block; }
        .stat-card.warning i { color: #ff9800; }
        .stat-card.danger i  { color: #e74c3c; }
        .stat-card.green i   { color: #27ae60; }
        .stat-card.blue i    { color: #3498db; }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 700; color: #333; }
        .stat-card .stat-label { font-size: 0.78rem; color: #999; font-weight: 600; text-transform: uppercase; margin-top: 2px; }

        /* Quick actions */
        .section-title {
            font-size: 1.1rem; font-weight: 700; color: #c2185b; margin-bottom: 1rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .actions-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px; margin-bottom: 2rem;
        }
        .action-card {
            display: block; background: white; border-radius: 16px; padding: 1.5rem;
            text-align: center; box-shadow: 0 6px 20px rgba(214,51,132,0.1);
            text-decoration: none; color: inherit;
            transition: transform 0.2s, box-shadow 0.2s; border-bottom: 3px solid transparent;
        }
        .action-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(214,51,132,0.2); border-bottom-color: #d63384; }
        .action-card i { font-size: 2rem; color: #d63384; display: block; margin-bottom: 0.7rem; }
        .action-card h3 { font-size: 1rem; font-weight: 700; color: #333; margin-bottom: 0.3rem; }
        .action-card p { font-size: 0.8rem; color: #999; }

        /* Recent sales */
        .recent-wrap { background: white; border-radius: 16px; box-shadow: 0 6px 20px rgba(214,51,132,0.1); overflow: hidden; }
        .recent-header {
            background: linear-gradient(90deg, #d63384, #c2185b);
            padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;
        }
        .recent-header span { color: white; font-weight: 700; font-size: 1rem; }
        .recent-header a { color: rgba(255,255,255,0.8); font-size: 0.82rem; text-decoration: none; }
        .recent-header a:hover { color: white; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #fce8f2; color: #c2185b; padding: 10px 16px; text-align: left; font-size: 0.82rem; font-weight: 700; }
        tbody tr { border-bottom: 1px solid #fce8f2; transition: background 0.15s; }
        tbody tr:hover { background: #fff5fb; }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 10px 16px; font-size: 0.85rem; color: #333; vertical-align: middle; }
        .product-cell { display: flex; align-items: center; gap: 10px; }
        .product-cell img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; }
        .amount { font-weight: 700; color: #d63384; }
        .no-customer { color: #bbb; font-size: 0.8rem; }
    </style>
</head>
<body>

<!-- Petals -->
<script>
for (let i = 0; i < 18; i++) {
    let p = document.createElement("div");
    p.className = "petal";
    p.innerHTML = ["🌸","🌺","🌷"][Math.floor(Math.random()*3)];
    p.style.left = Math.random() * 100 + "vw";
    p.style.animationDuration = (6 + Math.random() * 6) + "s";
    p.style.animationDelay = (Math.random() * 5) + "s";
    document.body.appendChild(p);
}
</script>

<div class="navbar">
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <span class="admin-name"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin']) ?></span>
    <!-- ✅ FIXED: Messages with unread badge -->
    <a href="messages.php">
        Messages
        <?php if ($unreadMessages > 0): ?>
            <span class="unread-badge"><?= $unreadMessages ?></span>
        <?php endif; ?>
    </a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">

    <div class="welcome">
        <h2>🌸 Welcome back, <?= htmlspecialchars($_SESSION['admin']) ?>!</h2>
        <p><?= date('l, d F Y') ?></p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-boxes"></i>
            <div class="stat-value"><?= $totalProducts ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card green">
            <i class="fas fa-money-bill-wave"></i>
            <div class="stat-value">Rs <?= number_format($totalRevenue, 0) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card blue">
            <i class="fas fa-receipt"></i>
            <div class="stat-value"><?= $totalSales ?></div>
            <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card blue">
            <i class="fas fa-users"></i>
            <div class="stat-value"><?= $totalCustomers ?></div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-card warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="stat-value"><?= $lowStock ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="stat-card danger">
            <i class="fas fa-times-circle"></i>
            <div class="stat-value"><?= $outOfStock ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
    <div class="actions-grid">
        <a class="action-card" href="add_product.php">
            <i class="fas fa-plus-circle"></i>
            <h3>Add Flower</h3>
            <p>Add new flowers to inventory</p>
        </a>
        <a class="action-card" href="view_products.php">
            <i class="fas fa-th-large"></i>
            <h3>View Inventory</h3>
            <p>Check and manage stock</p>
        </a>
        <a class="action-card" href="categories.php">
            <i class="fas fa-layer-group"></i>
            <h3>Categories</h3>
            <p>Add or manage categories</p>
        </a>
        <a class="action-card" href="sales.php">
            <i class="fas fa-cash-register"></i>
            <h3>New Sale</h3>
            <p>Record a walk-in sale</p>
        </a>
        <a class="action-card" href="analytics.php">
            <i class="fas fa-chart-line"></i>
            <h3>Analytics</h3>
            <p>View charts and reports</p>
        </a>
        <a class="action-card" href="view_sales.php">
            <i class="fas fa-receipt"></i>
            <h3>Sales Report</h3>
            <p>View all sales records</p>
        </a>
        <a class="action-card" href="messages.php">
            <i class="fas fa-envelope"></i>
            <h3>Messages</h3>
            <p>View customer messages</p>
        </a>
    </div>

    <!-- Recent Sales -->
    <div class="section-title"><i class="fas fa-history"></i> Recent Sales</div>
    <div class="recent-wrap">
        <div class="recent-header">
            <span>Last 5 Transactions</span>
            <a href="view_sales.php">View All →</a>
        </div>
        <?php if (!empty($recentSales)): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentSales as $s): ?>
                <tr>
                    <td><?= str_pad($s['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="product-cell">
                            <img src="uploads/<?= htmlspecialchars($s['image']) ?>" alt="">
                            <?= htmlspecialchars($s['product']) ?>
                        </div>
                    </td>
                    <td>
                        <?= $s['customer']
                            ? htmlspecialchars($s['customer'])
                            : '<span class="no-customer">Admin Sale</span>' ?>
                    </td>
                    <td><?= $s['quantity'] ?></td>
                    <td class="amount">Rs <?= number_format($s['subtotal'], 2) ?></td>
                    <td><?= date('d M Y', strtotime($s['sale_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center; padding:2rem; color:#bbb;">
            <i class="fas fa-receipt" style="font-size:2rem; display:block; margin-bottom:0.5rem;"></i>
            No sales recorded yet.
        </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>