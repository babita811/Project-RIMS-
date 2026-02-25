<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

// ── Summary Stats ──
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(subtotal),0) as r FROM sales")->fetch_assoc()['r'];
$totalOrders    = $conn->query("SELECT COUNT(*) as c FROM sales")->fetch_assoc()['c'];
$totalCustomers = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM sales WHERE user_id IS NOT NULL")->fetch_assoc()['c'];
$avgOrder       = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// ── Revenue last 7 days ──
$last7 = $conn->query("
    SELECT DATE(sale_date) as day, SUM(subtotal) as revenue, COUNT(*) as orders
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY day ASC
")->fetch_all(MYSQLI_ASSOC);

// Fill missing days with 0
$last7Map = [];
foreach ($last7 as $row) $last7Map[$row['day']] = $row;
$days7 = []; $rev7 = []; $ord7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days7[] = date('d M', strtotime($d));
    $rev7[]  = isset($last7Map[$d]) ? floatval($last7Map[$d]['revenue']) : 0;
    $ord7[]  = isset($last7Map[$d]) ? intval($last7Map[$d]['orders'])    : 0;
}

// ── Revenue last 6 months ──
$last6m = $conn->query("
    SELECT DATE_FORMAT(sale_date, '%Y-%m') as month,
           DATE_FORMAT(sale_date, '%b %Y') as label,
           SUM(subtotal) as revenue, COUNT(*) as orders
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
    ORDER BY month ASC
")->fetch_all(MYSQLI_ASSOC);
$months6 = array_column($last6m, 'label');
$rev6m   = array_map('floatval', array_column($last6m, 'revenue'));
$ord6m   = array_map('intval',   array_column($last6m, 'orders'));

// ── Top 5 best selling flowers ──
$topFlowers = $conn->query("
    SELECT p.name, p.image, p.category,
           SUM(s.quantity) as total_sold,
           SUM(s.subtotal) as total_revenue
    FROM sales s
    JOIN products p ON s.product_id = p.id
    GROUP BY s.product_id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── Sales by category ──
$byCategory = $conn->query("
    SELECT p.category, SUM(s.quantity) as total_sold, SUM(s.subtotal) as revenue
    FROM sales s
    JOIN products p ON s.product_id = p.id
    GROUP BY p.category
    ORDER BY total_sold DESC
")->fetch_all(MYSQLI_ASSOC);

$catLabels  = array_column($byCategory, 'category');
$catSold    = array_map('intval', array_column($byCategory, 'total_sold'));
$catRevenue = array_map('floatval', array_column($byCategory, 'revenue'));

// Today stats
$todayRev    = $conn->query("SELECT COALESCE(SUM(subtotal),0) as r FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc()['r'];
$todayOrders = $conn->query("SELECT COUNT(*) as c FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics – RIMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffe6f0, #fff0f5);
            min-height: 100vh;
            padding-top: 70px;
        }
        .navbar {
            position: fixed; top: 0; left: 0; width: 100%;
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

        .container { width: 92%; max-width: 1200px; margin: 30px auto; }

        h2 { color: #d63384; font-size: 1.6rem; margin-bottom: 0.3rem; }
        .subtitle { color: #aaa; font-size: 0.88rem; margin-bottom: 2rem; }

        /* Stat cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 2rem;
        }
        .stat-card {
            background: white; border-radius: 14px;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 4px 18px rgba(214,51,132,0.1);
            border-left: 4px solid #d63384;
            display: flex; align-items: center; gap: 1rem;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card.green  { border-left-color: #27ae60; }
        .stat-card.blue   { border-left-color: #3498db; }
        .stat-card.orange { border-left-color: #f39c12; }
        .stat-icon {
            width: 52px; height: 52px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
            background: #fce8f2; color: #d63384;
        }
        .stat-card.green  .stat-icon { background: #eafaf1; color: #27ae60; }
        .stat-card.blue   .stat-icon { background: #ebf5fb; color: #3498db; }
        .stat-card.orange .stat-icon { background: #fef9e7; color: #f39c12; }
        .stat-label { font-size: 0.78rem; color: #aaa; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #333; margin-top: 2px; }
        .stat-sub   { font-size: 0.75rem; color: #27ae60; margin-top: 2px; }

        /* Charts grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px; margin-bottom: 20px;
        }
        .charts-grid.three { grid-template-columns: 1fr 1fr 1fr; }
        @media (max-width: 900px) {
            .charts-grid, .charts-grid.three { grid-template-columns: 1fr; }
        }

        .chart-card {
            background: white; border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 4px 18px rgba(214,51,132,0.1);
        }
        .chart-title {
            font-size: 0.95rem; font-weight: 700;
            color: #333; margin-bottom: 1.2rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .chart-title i { color: #d63384; }
        .chart-wrap { position: relative; height: 220px; }

        /* Toggle buttons */
        .toggle-group {
            display: flex; gap: 4px;
            background: #fce8f2; border-radius: 8px;
            padding: 3px; margin-left: auto;
        }
        .toggle-btn {
            padding: 4px 12px; border: none; border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600;
            cursor: pointer; background: transparent; color: #d63384;
            transition: all 0.2s;
        }
        .toggle-btn.active { background: #d63384; color: white; }

        /* Top flowers table */
        .top-flower {
            display: grid;
            grid-template-columns: 44px auto 1fr auto auto;
            gap: 0.8rem; align-items: center;
            padding: 0.7rem 0;
            border-bottom: 1px solid #fce8f2;
        }
        .top-flower:last-child { border-bottom: none; }
        .top-flower img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }
        .flower-rank {
            font-size: 1.1rem; font-weight: 700; color: #d63384;
            text-align: center;
        }
        .flower-rank.gold   { color: #f39c12; }
        .flower-rank.silver { color: #95a5a6; }
        .flower-rank.bronze { color: #cd7f32; }
        .flower-name { font-weight: 600; font-size: 0.88rem; color: #333; }
        .flower-cat  { font-size: 0.75rem; color: #aaa; }
        .flower-sold { font-size: 0.82rem; color: #555; text-align: right; }
        .flower-rev  { font-weight: 700; color: #d63384; font-size: 0.9rem; text-align: right; white-space: nowrap; }

        .empty { text-align: center; padding: 3rem; color: #ccc; }
        .empty i { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="analytics.php" class="active">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">

    <h2>📊 Sales Analytics</h2>
    <p class="subtitle">Overview of your flower shop performance</p>

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">Rs <?= number_format($totalRevenue, 0) ?></div>
                <div class="stat-sub"><i class="fas fa-arrow-up"></i> Today: Rs <?= number_format($todayRev, 0) ?></div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-sub">Today: <?= $todayOrders ?> orders</div>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">Customers</div>
                <div class="stat-value"><?= $totalCustomers ?></div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div>
                <div class="stat-label">Avg Order Value</div>
                <div class="stat-value">Rs <?= number_format($avgOrder, 0) ?></div>
            </div>
        </div>
    </div>

    <?php if ($totalOrders === 0): ?>
    <div class="chart-card">
        <div class="empty">
            <i class="fas fa-chart-bar"></i>
            <p>No sales data yet. Analytics will appear once orders are placed.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Revenue & Orders Line Charts -->
    <div class="charts-grid" style="margin-bottom:20px;">
        <div class="chart-card">
            <div class="chart-title" style="display:flex; align-items:center;">
                <i class="fas fa-chart-area"></i> Revenue Trend
                <div class="toggle-group" id="revToggle">
                    <button class="toggle-btn active" onclick="switchChart('rev','7d')">7 Days</button>
                    <button class="toggle-btn" onclick="switchChart('rev','6m')">6 Months</button>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title" style="display:flex; align-items:center;">
                <i class="fas fa-chart-bar"></i> Orders Count
                <div class="toggle-group" id="ordToggle">
                    <button class="toggle-btn active" onclick="switchChart('ord','7d')">7 Days</button>
                    <button class="toggle-btn" onclick="switchChart('ord','6m')">6 Months</button>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="ordersChart"></canvas></div>
        </div>
    </div>

    <!-- Category & Donut -->
    <div class="charts-grid three" style="margin-bottom:20px;">
        <div class="chart-card" style="grid-column: span 2;">
            <div class="chart-title"><i class="fas fa-layer-group"></i> Revenue by Category</div>
            <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-pie"></i> Sales Share</div>
            <div class="chart-wrap"><canvas id="donutChart"></canvas></div>
        </div>
    </div>

    <!-- Top 5 Flowers -->
    <div class="chart-card">
        <div class="chart-title"><i class="fas fa-trophy"></i> Top 5 Best Selling Flowers</div>
        <?php if (!empty($topFlowers)): ?>
            <?php foreach ($topFlowers as $i => $f): ?>
            <div class="top-flower">
                <div class="flower-rank <?= $i===0?'gold':($i===1?'silver':($i===2?'bronze':'')) ?>">
                    <?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':'#'.($i+1))) ?>
                </div>
                <img src="uploads/<?= htmlspecialchars($f['image']) ?>" alt="">
                <div>
                    <div class="flower-name"><?= htmlspecialchars($f['name']) ?></div>
                    <div class="flower-cat"><?= htmlspecialchars($f['category']) ?></div>
                </div>
                <div class="flower-sold"><?= $f['total_sold'] ?> sold</div>
                <div class="flower-rev">Rs <?= number_format($f['total_revenue'], 0) ?></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty"><p>No sales data yet.</p></div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<script>
// Data from PHP
var data = {
    rev: {
        '7d': { labels: <?= json_encode($days7) ?>,  values: <?= json_encode($rev7) ?> },
        '6m': { labels: <?= json_encode($months6) ?>, values: <?= json_encode($rev6m) ?> }
    },
    ord: {
        '7d': { labels: <?= json_encode($days7) ?>,  values: <?= json_encode($ord7) ?> },
        '6m': { labels: <?= json_encode($months6) ?>, values: <?= json_encode($ord6m) ?> }
    }
};

var catLabels  = <?= json_encode($catLabels) ?>;
var catSold    = <?= json_encode($catSold) ?>;
var catRevenue = <?= json_encode($catRevenue) ?>;

var pink   = '#d63384';
var pinkBg = 'rgba(214,51,132,0.12)';
var colors = ['#d63384','#ff85a1','#c77dff','#ffbe0b','#48cae4','#ff7b00','#b565f5','#ff99c8','#9d4edd','#adb5bd'];

// Revenue Chart
var revCtx = document.getElementById('revenueChart').getContext('2d');
var revChart = new Chart(revCtx, {
    type: 'line',
    data: {
        labels: data.rev['7d'].labels,
        datasets: [{
            label: 'Revenue (Rs)',
            data: data.rev['7d'].values,
            borderColor: pink,
            backgroundColor: pinkBg,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: pink,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#fce8f2' },
                 ticks: { callback: v => 'Rs ' + v.toLocaleString() } },
            x: { grid: { display: false } }
        }
    }
});

// Orders Chart
var ordCtx = document.getElementById('ordersChart').getContext('2d');
var ordChart = new Chart(ordCtx, {
    type: 'bar',
    data: {
        labels: data.ord['7d'].labels,
        datasets: [{
            label: 'Orders',
            data: data.ord['7d'].values,
            backgroundColor: colors[0],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#fce8f2' },
                 ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});

// Category bar chart
if (document.getElementById('categoryChart')) {
    var catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Revenue (Rs)',
                data: catRevenue,
                backgroundColor: colors,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#fce8f2' },
                     ticks: { callback: v => 'Rs ' + v.toLocaleString() } },
                x: { grid: { display: false } }
            }
        }
    });
}

// Donut chart
if (document.getElementById('donutChart')) {
    var doCtx = document.getElementById('donutChart').getContext('2d');
    new Chart(doCtx, {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{ data: catSold, backgroundColor: colors, borderWidth: 2 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }
            },
            cutout: '65%'
        }
    });
}

// Switch chart data (7d / 6m)
function switchChart(type, period) {
    var chart  = type === 'rev' ? revChart : ordChart;
    var toggle = document.getElementById(type === 'rev' ? 'revToggle' : 'ordToggle');

    chart.data.labels                     = data[type][period].labels;
    chart.data.datasets[0].data           = data[type][period].values;
    chart.update();

    toggle.querySelectorAll('.toggle-btn').forEach(function(btn) {
        btn.classList.remove('active');
        if ((btn.textContent.trim() === '7 Days' && period === '7d') ||
            (btn.textContent.trim() === '6 Months' && period === '6m')) {
            btn.classList.add('active');
        }
    });
}
</script>

</body>
</html>