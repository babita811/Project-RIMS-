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
  <link rel="stylesheet" href="css/admin_orders.css">
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
