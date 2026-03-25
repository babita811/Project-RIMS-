<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id  = intval($_POST['order_id']);
    $newStatus = $_POST['new_status'];
    $allowed   = ['Pending', 'Processing', 'Delivered', 'Cancelled'];

    // Fetch current status to prevent going backwards
    $curStmt = $conn->prepare("SELECT status FROM sales_details WHERE id = ?");
    $curStmt->bind_param("i", $order_id);
    $curStmt->execute();
    $currentStatus = $curStmt->get_result()->fetch_assoc()['status'] ?? '';
    $curStmt->close();

    $lockedStatuses = ['Delivered', 'Cancelled'];

    if (in_array($newStatus, $allowed) && !in_array($currentStatus, $lockedStatuses)) {
        $stmt = $conn->prepare("UPDATE sales_details SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $order_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_orders.php");
    exit();
}

// Fetch all orders with user info and items
$rows = $conn->query("
    SELECT 
        sd.id         AS order_id,
        sd.address,
        sd.city,
        sd.note,
        sd.payment_method,
        sd.total_amount,
        sd.status,
        sd.ordered_at,
        u.name        AS fullname,
        u.email,
        u.phone,
        s.quantity,
        s.subtotal,
        COALESCE(p.name,  'Product Deleted') AS product_name,
        COALESCE(p.image, '')                AS image
    FROM sales_details sd
    JOIN users u    ON u.id  = sd.user_id
    LEFT JOIN sales s    ON s.order_id  = sd.id
    LEFT JOIN products p ON p.id = s.product_id
    ORDER BY sd.ordered_at DESC, s.id ASC
")->fetch_all(MYSQLI_ASSOC);

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
    if (!empty($row['quantity'])) {
        $orders[$oid]['items'][] = [
            'name'     => $row['product_name'],
            'image'    => $row['image'],
            'quantity' => $row['quantity'],
            'subtotal' => $row['subtotal'],
        ];
    }
}

$totalRevenue = array_sum(array_column($orders, 'total_amount'));
$totalOrders  = count($orders);
$pendingCount = count(array_filter($orders, fn($o) => $o['status'] === 'Pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Orders – RIMS Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #ffe6f0, #fff0f5);
      min-height: 100vh;
      padding-top: 70px;
    }

    /* ── Navbar ── */
    .navbar {
      position: fixed; top: 0; left: 0; width: 100%;
      background: linear-gradient(90deg, #d63384, #c2185b);
      padding: 12px 20px; z-index: 1000;
      display: flex; gap: 6px; align-items: center; flex-wrap: wrap;
    }
    .navbar a {
      color: white; text-decoration: none;
      padding: 8px 16px; border-radius: 20px;
      font-size: 0.88rem; font-weight: 500; transition: background 0.2s;
    }
    .navbar a:hover  { background: rgba(255,255,255,0.2); }
    .navbar a.active { background: white; color: #d63384; font-weight: 700; }
    .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }

    /* ── Page ── */
    .page { width: 94%; max-width: 1100px; margin: 30px auto; }

    h1 {
      font-size: 1.7rem; font-weight: 700; color: #d63384;
      margin-bottom: 1.4rem;
      display: flex; align-items: center; gap: 0.6rem;
    }

    /* ── Summary pills ── */
    .summary-bar {
      display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.6rem;
    }
    .pill {
      background: white; border-radius: 12px;
      padding: 0.9rem 1.4rem;
      box-shadow: 0 4px 16px rgba(214,51,132,0.1);
      font-size: 0.85rem; color: #777;
      display: flex; flex-direction: column; gap: 0.2rem;
    }
    .pill strong { font-size: 1.3rem; color: #d63384; font-weight: 700; }

    /* ── Order cards ── */
    .order-card {
      background: white; border-radius: 14px;
      box-shadow: 0 4px 18px rgba(214,51,132,0.09);
      margin-bottom: 1.2rem; overflow: hidden;
      border: 1.5px solid #fce8f2;
      transition: box-shadow 0.2s;
    }
    .order-card:hover { box-shadow: 0 8px 28px rgba(214,51,132,0.15); }

    /* Card header — clickable */
    .order-head {
      display: flex; justify-content: space-between; align-items: center;
      padding: 1rem 1.4rem; background: #fff9fc;
      border-bottom: 1.5px solid #fce8f2;
      cursor: pointer; flex-wrap: wrap; gap: 0.8rem;
      user-select: none;
    }
    .order-head-left { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .order-num  { font-weight: 800; font-size: 0.92rem; color: #d63384; }
    .order-meta { font-size: 0.8rem; color: #aaa; }
    .chevron    { color: #bbb; font-size: 0.85rem; transition: transform 0.3s; }
    .order-card.open .chevron { transform: rotate(180deg); }

    /* Status badge */
    .badge {
      padding: 0.25rem 0.85rem; border-radius: 20px;
      font-size: 0.75rem; font-weight: 700;
    }
    .badge-pending    { background: #fef3cd; color: #856404; border: 1px solid #ffd96a; }
    .badge-processing { background: #cfe2ff; color: #084298; border: 1px solid #9ec5fe; }
    .badge-delivered  { background: #d1f5e0; color: #155724; border: 1px solid #74c98a; }
    .badge-cancelled  { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

    /* Card body */
    .order-body { display: none; padding: 1.4rem; }
    .order-card.open .order-body { display: block; }

    .order-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 1.2rem; margin-bottom: 1rem;
    }
    @media (max-width: 640px) { .order-grid { grid-template-columns: 1fr; } }

    .info-box, .items-box {
      background: #fff9f5; border-radius: 10px;
      padding: 1rem 1.1rem; border: 1.5px solid #fce8f2;
    }
    .box-title {
      font-size: 0.78rem; font-weight: 700; color: #bbb;
      text-transform: uppercase; letter-spacing: 0.05em;
      margin-bottom: 0.7rem;
    }
    .info-row {
      display: flex; align-items: flex-start; gap: 0.6rem;
      font-size: 0.87rem; color: #444; margin-bottom: 0.4rem;
    }
    .info-row i { color: #d63384; width: 16px; flex-shrink: 0; margin-top: 2px; }

    .item-row {
      display: flex; align-items: center; gap: 0.8rem;
      padding: 0.5rem 0; border-bottom: 1px solid #fce8f2;
    }
    .item-row:last-child { border-bottom: none; }
    .item-row img { width: 42px; height: 42px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
    .item-name  { font-size: 0.87rem; font-weight: 600; color: #333; }
    .item-qty   { font-size: 0.76rem; color: #aaa; }
    .item-price { margin-left: auto; font-size: 0.88rem; font-weight: 700; color: #d63384; white-space: nowrap; }

    .order-total {
      display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem;
      padding-top: 0.8rem; margin-top: 0.6rem;
      border-top: 1.5px solid #fce8f2;
      font-size: 1rem; font-weight: 700; color: #333;
    }
    .order-total span:last-child { color: #d63384; font-size: 1.15rem; }

    /* Status update form */
    .status-form {
      display: flex; align-items: center; gap: 0.7rem;
      padding-top: 1rem; margin-top: 0.5rem;
      border-top: 1.5px solid #fce8f2; flex-wrap: wrap;
    }
    .status-form label { font-size: 0.82rem; font-weight: 600; color: #777; }
    .status-form select {
      padding: 0.5rem 0.9rem; border: 1.5px solid #f0d0e0;
      border-radius: 8px; font-family: 'Poppins', sans-serif;
      font-size: 0.88rem; color: #333;
      background: #fff9f5; outline: none;
      transition: border-color 0.2s;
    }
    .status-form select:focus { border-color: #d63384; }
    .btn-update {
      padding: 0.5rem 1.2rem; background: #d63384; color: white;
      border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
      font-size: 0.88rem; font-weight: 600; cursor: pointer;
      transition: background 0.2s;
    }
    .btn-update:hover { background: #b52b70; }

    /* Empty state */
    .empty {
      text-align: center; padding: 4rem 2rem;
      background: white; border-radius: 14px;
      box-shadow: 0 4px 18px rgba(214,51,132,0.07);
    }
    .empty i { font-size: 3.5rem; color: #f0d0e0; display: block; margin-bottom: 1rem; }
    .empty p { color: #bbb; }
  </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <a href="admin_dashboard.php">Dashboard</a>
  <a href="categories.php">Categories</a>
  <a href="add_product.php">Add Flower</a>
  <a href="view_products.php">View Flowers</a>
  <a href="sales.php">Sales</a>
  <a href="analytics.php">Analytics</a>
  <a href="view_sales.php">View Sales</a>
  <a href="admin_orders.php" class="active">Customer Orders</a>
  <a href="messages.php">Messages</a>
  <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="page">

  <h1><i class="fas fa-clipboard-list"></i> Customer Orders</h1>

  <!-- Summary -->
  <div class="summary-bar">
    <div class="pill"><strong><?= $totalOrders ?></strong> Total Orders</div>
    <div class="pill"><strong><?= $pendingCount ?></strong> Pending</div>
    <div class="pill"><strong>Rs <?= number_format($totalRevenue, 2) ?></strong> Total Revenue</div>
  </div>

  <?php if (empty($orders)): ?>
    <div class="empty">
      <i class="fas fa-inbox"></i>
      <p>No customer orders yet.</p>
    </div>

  <?php else: ?>
    <?php foreach ($orders as $order): ?>

    <?php
      $statusClass = match(strtolower($order['status'])) {
          'pending'    => 'badge-pending',
          'processing' => 'badge-processing',
          'delivered'  => 'badge-delivered',
          'cancelled'  => 'badge-cancelled',
          default      => 'badge-pending',
      };
    ?>

    <div class="order-card" id="order-<?= $order['order_id'] ?>">

      <!-- Header -->
      <div class="order-head" onclick="toggleOrder(<?= $order['order_id'] ?>)">
        <div class="order-head-left">
          <span class="order-num">Order #<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT) ?></span>
          <span class="order-meta">
            <i class="fas fa-user" style="margin-right:0.3rem;"></i><?= htmlspecialchars($order['fullname']) ?>
          </span>
          <span class="order-meta">
            <i class="fas fa-clock" style="margin-right:0.3rem;"></i><?= date('d M Y, h:i A', strtotime($order['ordered_at'])) ?>
          </span>
          <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($order['status']) ?></span>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
          <strong style="color:#d63384; font-size:1.05rem;">Rs <?= number_format($order['total_amount'], 2) ?></strong>
          <i class="fas fa-chevron-down chevron"></i>
        </div>
      </div>

      <!-- Body -->
      <div class="order-body">
        <div class="order-grid">

          <!-- Customer & Delivery Info -->
          <div class="info-box">
            <div class="box-title"><i class="fas fa-truck"></i> Delivery Details</div>
            <div class="info-row"><i class="fas fa-user"></i><?= htmlspecialchars($order['fullname']) ?></div>
            <div class="info-row"><i class="fas fa-envelope"></i><?= htmlspecialchars($order['email']) ?></div>
            <div class="info-row">
              <i class="fas fa-phone"></i>
              <?= !empty($order['phone']) ? htmlspecialchars($order['phone']) : '<em style="color:#ccc;">Not set</em>' ?>
            </div>
            <div class="info-row">
              <i class="fas fa-map-marker-alt"></i>
              <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>
            </div>
            <?php if (!empty($order['note'])): ?>
            <div class="info-row"><i class="fas fa-sticky-note"></i><?= htmlspecialchars($order['note']) ?></div>
            <?php endif; ?>
            <div class="info-row">
              <i class="fas fa-money-bill-wave"></i>
              <span style="background:#d1f5e0; color:#155724; padding:2px 10px; border-radius:20px; font-size:0.78rem; font-weight:700;">
                <?= htmlspecialchars($order['payment_method']) ?>
              </span>
            </div>
          </div>

          <!-- Items -->
          <div class="items-box">
            <div class="box-title"><i class="fas fa-box"></i> Items Ordered</div>
            <?php if (empty($order['items'])): ?>
              <p style="color:#ccc; font-size:0.85rem;">No items found.</p>
            <?php else: ?>
              <?php foreach ($order['items'] as $item): ?>
              <div class="item-row">
                <?php if (!empty($item['image'])): ?>
                  <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="">
                <?php else: ?>
                  <span style="font-size:1.8rem;">🌸</span>
                <?php endif; ?>
                <div>
                  <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="item-qty">Qty: <?= $item['quantity'] ?></div>
                </div>
                <div class="item-price">Rs <?= number_format($item['subtotal'], 2) ?></div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <div class="order-total">
              <span><i class="fas fa-receipt" style="margin-right:0.3rem;"></i> Order Total</span>
              <span>Rs <?= number_format($order['total_amount'], 2) ?></span>
            </div>
          </div>

        </div>

        <!-- Update Status -->
        <?php
          $locked = in_array($order['status'], ['Delivered', 'Cancelled']);
          // Only allow forward progression
          $allowedNext = match($order['status']) {
              'Pending'    => ['Pending', 'Processing', 'Cancelled'],
              'Processing' => ['Processing', 'Delivered', 'Cancelled'],
              'Delivered'  => ['Delivered'],
              'Cancelled'  => ['Cancelled'],
              default      => ['Pending', 'Processing', 'Delivered', 'Cancelled'],
          };
        ?>
        <form method="POST" class="status-form">
          <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
          <label><i class="fas fa-edit" style="margin-right:0.3rem;"></i>Update Status:</label>

          <?php if ($locked): ?>
            <span style="
              padding: 0.45rem 1.1rem;
              border-radius: 8px;
              font-size: 0.88rem;
              font-weight: 700;
              <?= $order['status'] === 'Delivered'
                  ? 'background:#d1f5e0; color:#155724; border:1.5px solid #74c98a;'
                  : 'background:#f8d7da; color:#842029; border:1.5px solid #f5c2c7;' ?>
            ">
              <i class="fas <?= $order['status'] === 'Delivered' ? 'fa-check-circle' : 'fa-ban' ?>" style="margin-right:0.3rem;"></i>
              <?= $order['status'] ?>
            </span>
            <i class="fas fa-lock" style="color:#bbb; font-size:0.85rem;" title="Status is locked"></i>

          <?php else: ?>
            <select name="new_status">
              <?php foreach ($allowedNext as $s): ?>
                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="update_status" class="btn-update">
              <i class="fas fa-save" style="margin-right:0.3rem;"></i>Save
            </button>
          <?php endif; ?>

        </form>

      </div>
    </div>

    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
  // Auto-open latest order
  window.addEventListener('DOMContentLoaded', function () {
    var first = document.querySelector('.order-card');
    if (first) first.classList.add('open');
  });

  function toggleOrder(id) {
    document.getElementById('order-' + id).classList.toggle('open');
  }
</script>

</body>
</html>