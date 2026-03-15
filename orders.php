<?php
session_start();
include 'db.php';

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

$clientName = htmlspecialchars($_SESSION['clientName'] ?? '');
$userId     = intval($_SESSION['clientId']);

// Auto-create sales_details table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS sales_details (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        user_id        INT NOT NULL,
        address        VARCHAR(255) NOT NULL,
        city           VARCHAR(100) NOT NULL,
        note           TEXT DEFAULT NULL,
        payment_method VARCHAR(20) DEFAULT 'COD',
        total_amount   DECIMAL(10,2) NOT NULL,
        status         VARCHAR(30) DEFAULT 'Pending',
        ordered_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Fetch orders with their items grouped
$stmt = $conn->prepare("
    SELECT o.id as order_id,
           u.name as fullname, u.email, u.phone,
           o.address, o.city,
           o.note, o.payment_method, o.total_amount, o.status, o.ordered_at,
           s.quantity, s.subtotal,
           COALESCE(p.name, 'Product Deleted') as product_name, COALESCE(p.image, '') as image
    FROM sales_details o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN sales s ON s.order_id = o.id
    LEFT JOIN products p ON p.id = s.product_id
    WHERE o.user_id = ?
    ORDER BY o.ordered_at DESC, s.id ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by order_id
$orders = [];
foreach ($rows as $row) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'order_id'       => $oid,
            'fullname'       => $row['fullname'],
            'email'          => $row['email'],
            'phone'          => $row['phone'] ?? '',
            'address'        => $row['address'],
            'city'           => $row['city'],
            'note'           => $row['note'],
            'payment_method' => $row['payment_method'],
            'total_amount'   => $row['total_amount'],
            'status'         => $row['status'],
            'ordered_at'     => $row['ordered_at'],
            'items'          => [],
        ];
    }
    if ($row['quantity']) {
        $orders[$oid]['items'][] = [
            'name'     => $row['product_name'],
            'image'    => $row['image'],
            'quantity' => $row['quantity'],
            'subtotal' => $row['subtotal'],
        ];
    }
}

$totalSpent  = array_sum(array_column($orders, 'total_amount'));
$totalOrders = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders – RIMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/homepage.css">
  <style>
    .history-section { max-width: 860px; margin: 120px auto 60px; padding: 0 1.5rem; }
    .page-heading { font-family: 'Playfair Display', serif; font-size: 2.4rem; font-weight: 900; color: var(--dark); margin-bottom: 0.3rem; }
    .page-heading span { color: var(--pink); font-style: italic; }
    .page-sub { color: var(--muted); margin-bottom: 1.8rem; font-size: 0.95rem; }

    .summary-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .pill { background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 0.45rem 1.1rem; font-size: 0.85rem; font-weight: 600; color: var(--pink); }
    .pill span { color: var(--dark); }

    .order-card { background: #fff; border-radius: 1.1rem; box-shadow: 0 4px 20px rgba(232,69,122,0.08); margin-bottom: 1.4rem; overflow: hidden; border: 1.5px solid var(--border); transition: box-shadow 0.2s; }
    .order-card:hover { box-shadow: 0 8px 28px rgba(232,69,122,0.14); }

    .order-head { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.4rem; background: #fff9fc; border-bottom: 1.5px solid var(--border); flex-wrap: wrap; gap: 0.8rem; cursor: pointer; user-select: none; }
    .order-head-left { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .order-num { font-weight: 800; font-size: 0.92rem; color: var(--pink); }
    .order-date { font-size: 0.8rem; color: var(--muted); }
    .order-total-badge { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 900; color: var(--pink); }
    .status-badge { padding: 0.25rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: #fef3cd; color: #856404; border: 1px solid #ffd96a; }
    .status-badge.delivered { background: #d1f5e0; color: #155724; border-color: #74c98a; }
    .chevron { color: var(--muted); font-size: 0.85rem; transition: transform 0.3s; }
    .order-card.open .chevron { transform: rotate(180deg); }

    .order-body { display: none; padding: 1.2rem 1.4rem; }
    .order-card.open .order-body { display: block; }

    .order-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1rem; }
    @media (max-width: 600px) { .order-grid { grid-template-columns: 1fr; } }

    .info-box, .items-box { background: #fff9f5; border-radius: 10px; padding: 1rem 1.1rem; border: 1.5px solid var(--border); }
    .info-box h4, .items-box h4 { font-size: 0.82rem; font-weight: 700; color: #aaa; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.7rem; }
    .info-row { display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.88rem; color: #444; margin-bottom: 0.45rem; }
    .info-row i { color: var(--pink); width: 16px; margin-top: 2px; flex-shrink: 0; }
    .cod-tag { display: inline-flex; align-items: center; gap: 0.4rem; background: #d1f5e0; color: #155724; border-radius: 20px; padding: 0.25rem 0.8rem; font-size: 0.78rem; font-weight: 700; margin-top: 0.5rem; }

    .item-row { display: flex; align-items: center; gap: 0.8rem; padding: 0.5rem 0; border-bottom: 1px solid var(--border); }
    .item-row:last-child { border-bottom: none; padding-bottom: 0; }
    .item-row img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
    .item-name { font-size: 0.88rem; font-weight: 600; color: var(--dark); }
    .item-qty { font-size: 0.78rem; color: var(--muted); }
    .item-price { margin-left: auto; font-size: 0.9rem; font-weight: 700; color: var(--pink); white-space: nowrap; }

    .order-foot { display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem; padding-top: 0.8rem; margin-top: 0.6rem; border-top: 1.5px solid var(--border); font-size: 1rem; font-weight: 700; color: var(--dark); }
    .order-foot span:last-child { font-family: 'Playfair Display', serif; font-size: 1.2rem; color: var(--pink); }

    .grand-total { display: flex; justify-content: space-between; align-items: center; background: #fff; border-radius: 1rem; padding: 1.2rem 1.6rem; margin-top: 0.5rem; box-shadow: 0 4px 20px rgba(232,69,122,0.08); font-size: 1.05rem; font-weight: 700; color: var(--dark); border: 1.5px solid var(--border); }
    .grand-total span:last-child { font-family: 'Playfair Display', serif; color: var(--pink); font-size: 1.35rem; }

    .empty-state { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 1rem; box-shadow: 0 4px 20px rgba(232,69,122,0.06); }
    .empty-state i { font-size: 4rem; color: var(--border); display: block; margin-bottom: 1rem; }
    .empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.4rem; color: var(--muted); margin-bottom: 0.5rem; }
    .empty-state p { color: var(--muted); margin-bottom: 1.5rem; }
  </style>
</head>
<body>

<header>
  <a href="index.php" class="logo">RIMS<span>.</span></a>
  <nav class="navbar">
    <a href="index.php#home">Home</a>
    <a href="index.php#about">About Us</a>
    <a href="index.php#products">Products</a>
    <a href="index.php#contact">Contact</a>
    <a href="orders.php" style="color:var(--pink); font-weight:600;">My Orders</a>
    <a href="my_messages.php" style="color:#f17dda;">My Messages</a>
  </nav>
  <div class="icons">
    <i class="fas fa-user" style="color:var(--pink);" title="<?= $clientName ?>"></i>
    <a href="purchase.php" title="Cart" style="color:var(--muted); font-size:1.1rem;"><i class="fas fa-shopping-cart"></i></a>
    <a href="logout.php" style="background:var(--pink);color:#fff;padding:0.4rem 1rem;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
      <i class="fas fa-sign-out-alt" style="margin-right:0.3rem;"></i>Logout
    </a>
  </div>
</header>

<div class="history-section">

  <h1 class="page-heading">My <span>Orders</span></h1>
  <p class="page-sub">Welcome back, <?= $clientName ?>! Here are all your past orders with delivery details.</p>

  <?php if ($totalOrders > 0): ?>

  <div class="summary-bar">
    <div class="pill">Total Orders: <span><?= $totalOrders ?></span></div>
    <div class="pill">Total Spent: <span>Rs <?= number_format($totalSpent, 2) ?></span></div>
  </div>

  <?php foreach ($orders as $order): ?>
  <div class="order-card" id="order-<?= $order['order_id'] ?>">

    <div class="order-head" onclick="toggleOrder(<?= $order['order_id'] ?>)">
      <div class="order-head-left">
        <span class="order-num">Order #<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT) ?></span>
        <span class="order-date">
          <i class="fas fa-clock" style="margin-right:0.3rem;"></i>
          <?= date('d M Y, h:i A', strtotime($order['ordered_at'])) ?>
        </span>
        <span class="status-badge <?= strtolower($order['status']) === 'delivered' ? 'delivered' : '' ?>">
          <?= htmlspecialchars($order['status']) ?>
        </span>
      </div>
      <div style="display:flex; align-items:center; gap:1rem;">
        <span class="order-total-badge">Rs <?= number_format($order['total_amount'], 2) ?></span>
        <i class="fas fa-chevron-down chevron"></i>
      </div>
    </div>

    <div class="order-body">
      <div class="order-grid">

        <!-- Delivery Info -->
        <div class="info-box">
          <h4><i class="fas fa-truck" style="color:var(--pink); margin-right:0.4rem;"></i>Delivery Details</h4>
          <div class="info-row"><i class="fas fa-user"></i><?= htmlspecialchars($order['fullname']) ?></div>
          <div class="info-row"><i class="fas fa-envelope"></i><?= htmlspecialchars($order['email']) ?></div>
          <div class="info-row"><i class="fas fa-phone"></i><?= !empty($order['phone']) ? htmlspecialchars($order['phone']) : '<em style="color:#bbb;">Not set</em>' ?></div>
          <div class="info-row"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></div>
          <?php if (!empty($order['note'])): ?>
          <div class="info-row"><i class="fas fa-sticky-note"></i><?= htmlspecialchars($order['note']) ?></div>
          <?php endif; ?>
          <div><span class="cod-tag"><i class="fas fa-money-bill-wave"></i> Cash on Delivery</span></div>
        </div>

        <!-- Items -->
        <div class="items-box">
          <h4><i class="fas fa-box" style="color:var(--pink); margin-right:0.4rem;"></i>Items Ordered</h4>
          <?php if (empty($order['items'])): ?>
            <p style="color:var(--muted); font-size:0.85rem;">No item details found.</p>
          <?php else: ?>
            <?php foreach ($order['items'] as $item): ?>
            <div class="item-row">
              <?php if(!empty($item['image'])): ?><img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"><?php else: ?><span style="font-size:2rem;">🌸</span><?php endif; ?>
              <div>
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="item-qty">Qty: <?= $item['quantity'] ?></div>
              </div>
              <div class="item-price">Rs <?= number_format($item['subtotal'], 2) ?></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <div class="order-foot">
            <span><i class="fas fa-receipt" style="margin-right:0.3rem;"></i>Order Total</span>
            <span>Rs <?= number_format($order['total_amount'], 2) ?></span>
          </div>
        </div>

      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="grand-total">
    <span><i class="fas fa-wallet" style="color:var(--pink); margin-right:0.5rem;"></i>Grand Total Spent</span>
    <span>Rs <?= number_format($totalSpent, 2) ?></span>
  </div>

  <?php else: ?>

  <div class="empty-state">
    <i class="fas fa-box-open"></i>
    <h3>No orders yet</h3>
    <p>You haven't placed any orders. Start shopping!</p>
    <a href="index.php#products" class="btn"><i class="fas fa-shopping-bag"></i> Browse Flowers</a>
  </div>

  <?php endif; ?>

</div>

<script>
  // Auto-open latest order
  window.addEventListener('DOMContentLoaded', function() {
    var first = document.querySelector('.order-card');
    if (first) first.classList.add('open');
  });

  function toggleOrder(id) {
    document.getElementById('order-' + id).classList.toggle('open');
  }
</script>

</body>
</html>