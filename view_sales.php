<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

// Search/filter
$search   = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

$query  = "SELECT s.id as sale_id, COALESCE(p.name, 'Product Deleted') as product_name, COALESCE(p.image, '') as image,
                  u.name as customer_name, u.username,
                  s.quantity, s.subtotal, s.sale_date
           FROM sales s
           JOIN products p ON s.product_id = p.id
           LEFT JOIN users u ON s.user_id = u.id
           WHERE 1";
$params = [];
$types  = "";

if (!empty($search)) {
    $query   .= " AND (p.name LIKE ? OR u.name LIKE ? OR u.username LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= "sss";
}
if (!empty($dateFrom)) {
    $query   .= " AND DATE(s.sale_date) >= ?";
    $params[] = $dateFrom; $types .= "s";
}
if (!empty($dateTo)) {
    $query   .= " AND DATE(s.sale_date) <= ?";
    $params[] = $dateTo; $types .= "s";
}
$query .= " ORDER BY s.sale_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result      = $stmt->get_result();
$sales       = $result->fetch_all(MYSQLI_ASSOC);
$grandTotal  = array_sum(array_column($sales, 'subtotal'));
$totalOrders = count($sales);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sales – RIMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            padding-top: 70px;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffe6f0, #fff0f5);
            min-height: 100vh;
        }
        .navbar {
            position: fixed; top:0; left:0; width:100%;
            background: linear-gradient(90deg, #d63384, #c2185b);
            padding: 12px 20px; z-index:1000;
            display: flex; gap: 8px; align-items: center;
        }
        .navbar a {
            color: white; text-decoration: none;
            padding: 8px 16px; border-radius: 20px;
            font-size: 0.9rem; font-weight: 500;
            transition: background 0.2s;
        }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .navbar a.active { background: white; color: #d63384; font-weight: 700; }
        .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }

        .container { width: 92%; max-width: 1200px; margin: 30px auto; }

        h2 { color: #d63384; font-size: 1.8rem; margin-bottom: 1.5rem; }
        h2 span { color: #555; font-size: 1rem; font-weight: 400; margin-left: 0.5rem; }

        /* Summary cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 1.5rem;
        }
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 4px 15px rgba(214,51,132,0.12);
            border-left: 4px solid #d63384;
        }
        .summary-card .label { font-size: 0.8rem; color: #999; font-weight: 600; text-transform: uppercase; }
        .summary-card .value { font-size: 1.6rem; font-weight: 700; color: #d63384; margin-top: 4px; }

        /* Filter bar */
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 15px rgba(214,51,132,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar input, .filter-bar select {
            padding: 0.5rem 0.9rem;
            border: 1.5px solid #f0d0e0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            outline: none;
            transition: border-color 0.2s;
            background: #fff9f5;
        }
        .filter-bar input:focus { border-color: #d63384; }
        .filter-bar input[type="text"] { flex: 1; min-width: 200px; }
        .filter-bar button {
            padding: 0.5rem 1.2rem;
            background: #d63384; color: white;
            border: none; border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        .filter-bar button:hover { background: #b52b70; }
        .filter-bar a {
            padding: 0.5rem 1rem;
            color: #d63384; font-size: 0.85rem;
            text-decoration: none; font-weight: 500;
        }
        .filter-bar a:hover { text-decoration: underline; }

        /* Table */
        .table-wrap {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(214,51,132,0.1);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: linear-gradient(90deg, #d63384, #c2185b);
            color: white; padding: 14px 16px;
            text-align: left; font-size: 0.85rem;
            font-weight: 600; letter-spacing: 0.03em;
        }
        tbody tr { border-bottom: 1px solid #fce8f2; transition: background 0.15s; }
        tbody tr:hover { background: #fff5fb; }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 12px 16px; font-size: 0.88rem; color: #333; vertical-align: middle; }

        .product-cell { display: flex; align-items: center; gap: 10px; }
        .product-cell img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }
        .product-name { font-weight: 600; color: #333; }

        .customer-cell { display: flex; flex-direction: column; }
        .customer-name { font-weight: 600; color: #333; }
        .customer-username { font-size: 0.78rem; color: #999; }

        .badge-admin {
            background: #ffe0f0; color: #d63384;
            padding: 2px 10px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600;
        }

        .amount { font-weight: 700; color: #d63384; }

        .tfoot-row td {
            background: linear-gradient(90deg, #5563DE, #3742a3);
            color: white; font-weight: 700;
            padding: 14px 16px; font-size: 0.95rem;
        }

        .empty-state {
            text-align: center; padding: 4rem 2rem;
            color: #999;
        }
        .empty-state i { font-size: 3rem; color: #f0d0e0; display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php" class="<?= $page=='admin_dashboard.php'?'active':'' ?>">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php" class="<?= $page=='add_product.php'?'active':'' ?>">Add Flower</a>
    <a href="view_products.php" class="<?= $page=='view_products.php'?'active':'' ?>">View Flowers</a>
    <a href="analytics.php" class="<?= $page=='sales.php'?'active':'' ?>">Analytics</a>
    <a href="view_sales.php" class="<?= $page=='view_sales.php'?'active':'' ?>">View Sales</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <h2>🧾 All Sales <span><?= $totalOrders ?> record(s) found</span></h2>

    <!-- Summary -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Total Orders</div>
            <div class="value"><?= $totalOrders ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Total Revenue</div>
            <div class="value">Rs <?= number_format($grandTotal, 0) ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Avg Order Value</div>
            <div class="value">Rs <?= $totalOrders > 0 ? number_format($grandTotal / $totalOrders, 0) : 0 ?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search product or customer..." value="<?= htmlspecialchars($search) ?>">
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" title="From date">
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" title="To date">
        <button type="submit"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search || $dateFrom || $dateTo): ?>
            <a href="view_sales.php"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-wrap">
        <?php if ($totalOrders > 0): ?>
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
                <?php foreach ($sales as $row): ?>
                <tr>
                    <td><?= str_pad($row['sale_id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="product-cell">
                            <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="">
                            <span class="product-name"><?= htmlspecialchars($row['product_name']) ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if ($row['customer_name']): ?>
                        <div class="customer-cell">
                            <span class="customer-name"><?= htmlspecialchars($row['customer_name']) ?></span>
                            <span class="customer-username">@<?= htmlspecialchars($row['username']) ?></span>
                        </div>
                        <?php else: ?>
                        <span class="badge-admin">Admin Sale</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['quantity'] ?></td>
                    <td class="amount">Rs <?= number_format($row['subtotal'], 2) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($row['sale_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="4">Grand Total Revenue</td>
                    <td colspan="2">Rs <?= number_format($grandTotal, 2) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-receipt"></i>
            <p>No sales found<?= $search || $dateFrom || $dateTo ? ' matching your filters' : ' yet' ?>.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>