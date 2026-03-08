<?php
session_start();
include 'db.php';

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

$clientName = htmlspecialchars($_SESSION['clientName'] ?? '');
$userId     = intval($_SESSION['clientId'] ?? 0);

// Ensure users table has phone/address/city columns (normalization)
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone   VARCHAR(20)  DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS city    VARCHAR(100) DEFAULT NULL");

// Fetch full user data from DB for pre-filling the delivery form
$userStmt = $conn->prepare("SELECT name, email, phone, address, city FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
$userData = $userData ?: [];

// Sanitize cart
if (isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) {
        return isset($item['name'], $item['price'], $item['quantity'], $item['image']);
    }));
}

// Add item to cart from GET params
if (isset($_GET['name']) && isset($_GET['price'])) {
    $name  = htmlspecialchars($_GET['name']);
    $price = floatval($_GET['price']);
    $image = htmlspecialchars($_GET['image'] ?? '');
    $qty   = max(1, intval($_GET['qty'] ?? 1));

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] === $name) {
            $item['quantity'] += $qty;
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['cart'][] = ['name' => $name, 'price' => $price, 'image' => $image, 'quantity' => $qty];
    }

    header("Location: purchase.php");
    exit();
}

// Remove item
if (isset($_POST['remove'])) {
    $idx = intval($_POST['remove']);
    if (isset($_SESSION['cart'][$idx])) {
        array_splice($_SESSION['cart'], $idx, 1);
    }
    header("Location: purchase.php");
    exit();
}

// Update quantity
if (isset($_POST["update_qty"])) {
    if (isset($_POST["quantities"]) && is_array($_POST["quantities"])) {
        foreach ($_POST["quantities"] as $idx => $qty) {
            $idx = intval($idx);
            $qty = intval($qty);
            if (isset($_SESSION["cart"][$idx]) && $qty >= 1) {
                $_SESSION["cart"][$idx]["quantity"] = $qty;
            }
        }
    }
    header("Location: purchase.php");
    exit();
}

// ── Final Checkout with delivery details ──
if (isset($_POST['checkout'])) {
    $address  = trim($_POST['address'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $note     = trim($_POST['note'] ?? '');

    $phone    = trim($_POST['phone'] ?? $userData['phone'] ?? '');

    // Save phone/address/city back to users table for future pre-fill
    if (!empty($address) && !empty($city)) {
        $saveUser = $conn->prepare("UPDATE users SET phone = ?, address = ?, city = ? WHERE id = ?");
        $saveUser->bind_param("sssi", $phone, $address, $city, $userId);
        $saveUser->execute();
        $saveUser->close();
    }
    if (empty($address) || empty($city) || empty($phone)) {
        $error = "Please fill in your phone, address and city.";
    } elseif (!empty($_SESSION['cart'])) {

        // Stock check
        $allOk = true;
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("SELECT id, quantity FROM products WHERE name = ?");
            $stmt->bind_param("s", $item['name']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$result || $result['quantity'] < $item['quantity']) {
                $error  = "Not enough stock for " . $item['name'];
                $allOk  = false;
                break;
            }
        }

        if ($allOk) {
            $userId      = $_SESSION['clientId'];
            $totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $_SESSION['cart']));

            // Auto-create normalized sales_details table
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

            // Add order_id to sales if missing
            $conn->query("ALTER TABLE sales ADD COLUMN IF NOT EXISTS order_id INT DEFAULT NULL");

            // Insert normalized order — user_id links to users table (name/email/phone fetched via JOIN)
            $ins_order = $conn->prepare("INSERT INTO sales_details (user_id, address, city, note, payment_method, total_amount) VALUES (?, ?, ?, ?, 'COD', ?)");
            $ins_order->bind_param("isssd", $userId, $address, $city, $note, $totalAmount);
            $ins_order->execute();
            $orderId = $conn->insert_id;
            $ins_order->close();

            // Insert each sale linked to this order
            foreach ($_SESSION['cart'] as $item) {
                $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
                $stmt->bind_param("s", $item['name']);
                $stmt->execute();
                $product    = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $product_id = $product['id'];
                $subtotal   = $item['price'] * $item['quantity'];

                $upd = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $upd->bind_param("ii", $item['quantity'], $product_id);
                $upd->execute();
                $upd->close();

                $ins = $conn->prepare("INSERT INTO sales (product_id, user_id, order_id, quantity, subtotal, sale_date) VALUES (?, ?, ?, ?, ?, NOW())");
                $ins->bind_param("iiiid", $product_id, $userId, $orderId, $item['quantity'], $subtotal);
                $ins->execute();
                $ins->close();
            }

            // Save for success page display — pull name/phone from users table
            $_SESSION['last_cart']     = $_SESSION['cart'];
            $_SESSION['delivery_info'] = [
                'fullname' => $userData['name'] ?? $clientName,
                'phone'    => $userData['phone'] ?? '',
                'email'    => $userData['email'] ?? '',
                'address'  => $address,
                'city'     => $city,
                'note'     => $note,
            ];
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['cart']          = [];
            header("Location: purchase.php?success=1");
            exit();
        }
    } else {
        $error = "Your cart is empty!";
    }
}

$cart    = $_SESSION['cart'] ?? [];
$total   = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
$success = isset($_GET['success']);
$delivery = $_SESSION['delivery_info'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $success ? 'Order Confirmed' : 'My Cart' ?> – RIMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/homepage.css">
  <style>
    .cart-section {
      max-width: 1000px;
      margin: 120px auto 60px;
      padding: 0 1.5rem;
    }
    .page-heading {
      font-family: 'Playfair Display', serif;
      font-size: 2.4rem; font-weight: 900;
      color: var(--dark); margin-bottom: 2rem;
    }
    .page-heading span { color: var(--pink); font-style: italic; }

    /* Two column layout */
    .checkout-grid {
      display: grid;
      grid-template-columns: 1fr 420px;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 820px) { .checkout-grid { grid-template-columns: 1fr; } }

    /* Cart items */
    .cart-item {
      background: var(--white); border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(232,69,122,0.08);
      padding: 1.2rem 1.5rem; margin-bottom: 1rem;
      display: grid;
      grid-template-columns: 80px 1fr auto auto;
      gap: 1rem; align-items: center;
      transition: box-shadow 0.2s;
    }
    .cart-item:hover { box-shadow: var(--shadow); }
    .cart-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
    .cart-item-info h4 { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; color: var(--dark); margin-bottom: 0.2rem; }
    .cart-item-info p  { font-size: 0.85rem; color: var(--muted); }
    .cart-item-info .item-price { font-size: 1rem; font-weight: 700; color: var(--pink); margin-top: 0.2rem; }

    .qty-form { display: flex; align-items: center; gap: 0.5rem; }
    .qty-form button { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid var(--border); background: var(--white); color: var(--pink); font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; transition: all 0.2s; }
    .qty-form button:hover { background: var(--pink); color: var(--white); border-color: var(--pink); }
    .qty-form input[type=number] { width: 45px; text-align: center; border: 1.5px solid var(--border); border-radius: 8px; padding: 0.3rem; font-size: 0.95rem; color: var(--dark); background: var(--pink-soft); }
    .btn-remove { background: none; border: none; color: #ccc; font-size: 1.1rem; cursor: pointer; padding: 0.3rem; transition: color 0.2s; width: auto; }
    .btn-remove:hover { color: #e74c3c; background: none; transform: none; }

    /* Right panel */
    .right-panel { position: sticky; top: 90px; }

    /* Delivery form */
    .delivery-card {
      background: var(--white);
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(232,69,122,0.08);
      padding: 1.8rem;
      margin-bottom: 1rem;
    }
    .delivery-card h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.2rem; font-weight: 700;
      color: var(--dark); margin-bottom: 1.2rem;
      padding-bottom: 0.8rem;
      border-bottom: 2px solid var(--border);
      display: flex; align-items: center; gap: 0.5rem;
    }
    .delivery-card h3 i { color: var(--pink); }

    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-size: 0.8rem; font-weight: 700; color: #555; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.03em; }
    .form-group label .req { color: var(--pink); }
    .form-group input,
    .form-group textarea {
      width: 100%; padding: 0.7rem 1rem;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.92rem; color: var(--dark);
      background: var(--pink-soft);
      outline: none; transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      border-color: var(--pink);
      box-shadow: 0 0 0 3px rgba(232,69,122,0.1);
      background: #fff;
    }
    .form-group textarea { resize: vertical; min-height: 70px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }

    /* COD badge */
    .cod-badge {
      background: #eafaf1;
      border: 1.5px solid #a9dfbf;
      border-radius: 10px;
      padding: 0.9rem 1.1rem;
      display: flex; align-items: center; gap: 0.8rem;
      margin-bottom: 1rem;
    }
    .cod-badge i { font-size: 1.5rem; color: #27ae60; }
    .cod-badge div { font-size: 0.88rem; color: #1e8449; }
    .cod-badge strong { font-size: 0.95rem; display: block; color: #1a5e36; }

    /* Order summary */
    .summary-card {
      background: var(--white);
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(232,69,122,0.08);
      padding: 1.5rem 1.8rem;
      margin-bottom: 1rem;
    }
    .summary-card h3 { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 1rem; padding-bottom: 0.7rem; border-bottom: 2px solid var(--border); }
    .summary-row { display: flex; justify-content: space-between; font-size: 0.88rem; color: var(--muted); padding: 0.3rem 0; }
    .summary-row.total { font-size: 1.1rem; font-weight: 700; color: var(--dark); border-top: 2px solid var(--border); margin-top: 0.5rem; padding-top: 0.8rem; }
    .summary-row.total span:last-child { color: var(--pink); font-size: 1.3rem; }

    .btn-checkout {
      width: 100%; padding: 1rem;
      background: var(--pink); color: var(--white);
      border: none; border-radius: 50px;
      font-family: 'DM Sans', sans-serif;
      font-size: 1rem; font-weight: 700;
      cursor: pointer; transition: all 0.3s;
      box-shadow: 0 8px 24px rgba(232,69,122,0.3);
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    .btn-checkout:hover { background: var(--rose); transform: translateY(-2px); }

    /* Empty / Error */
    .empty-cart { text-align: center; padding: 4rem 2rem; background: var(--white); border-radius: 1rem; box-shadow: 0 4px 20px rgba(232,69,122,0.06); }
    .empty-cart i { font-size: 4rem; color: var(--border); display: block; margin-bottom: 1rem; }
    .empty-cart h3 { font-family: 'Playfair Display', serif; font-size: 1.4rem; color: var(--muted); margin-bottom: 0.5rem; }
    .empty-cart p { color: var(--muted); margin-bottom: 1.5rem; font-size: 0.95rem; }
    .error-msg { background: #ffe0f0; border: 1.5px solid var(--pink); border-radius: 8px; padding: 0.8rem 1.2rem; color: #c0392b; margin-bottom: 1rem; font-size: 0.9rem; }

    /* Success */
    .success-card { background: var(--white); border-radius: 1.2rem; box-shadow: var(--shadow-lg); padding: 3rem; text-align: center; border-top: 4px solid #27ae60; }
    .success-card i.big { font-size: 4rem; color: #27ae60; display: block; margin-bottom: 1rem; }
    .success-card h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; color: var(--dark); margin-bottom: 0.5rem; }
    .success-card p { color: var(--muted); margin-bottom: 1rem; }

    .delivery-summary {
      background: #f9f9f9; border-radius: 10px;
      padding: 1rem 1.2rem; text-align: left;
      margin: 1.2rem 0;
    }
    .delivery-summary h4 { font-size: 0.9rem; font-weight: 700; color: #555; margin-bottom: 0.6rem; }
    .delivery-summary p { font-size: 0.88rem; color: #333; margin-bottom: 0.3rem; }
    .delivery-summary p i { color: var(--pink); width: 18px; }
    .cod-confirm { display: inline-flex; align-items: center; gap: 0.5rem; background: #eafaf1; border-radius: 20px; padding: 0.4rem 1rem; font-size: 0.85rem; color: #27ae60; font-weight: 700; margin-bottom: 1.5rem; }

    @media (max-width: 600px) {
      .cart-item { grid-template-columns: 60px 1fr; }
      .form-row { grid-template-columns: 1fr; }
    }

    /* ── Override readonly-field "Not set" from homepage.css ── */
    .readonly-field {
      padding: 0.7rem 1rem;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 0.92rem;
      color: var(--dark);
      background: var(--pink-soft);
    }
    .readonly-field::after { content: none !important; }

    /* ── Stock Alert Modal ── */
    #stockModal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.4);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }
    #stockModal.show { display: flex; }
    .stock-modal-box {
      background: #fff;
      border-radius: 1.5rem;
      padding: 2.5rem 2rem;
      text-align: center;
      max-width: 340px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(232,69,122,0.25);
      border-top: 4px solid var(--pink);
      animation: popIn 0.25s ease;
    }
    @keyframes popIn {
      from { transform: scale(0.8); opacity: 0; }
      to   { transform: scale(1);   opacity: 1; }
    }
    .stock-modal-icon {
      width: 70px; height: 70px;
      background: #fff0f8;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.2rem;
      border: 2px solid #f5d0ef;
    }
    .stock-modal-icon i { font-size: 2rem; color: var(--pink); }
    .stock-modal-box h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.3rem; font-weight: 900;
      color: #333; margin-bottom: 0.5rem;
    }
    .stock-modal-box p {
      color: #888; font-size: 0.92rem; margin-bottom: 1.5rem;
    }
    .stock-modal-box p span {
      color: var(--pink); font-weight: 700; font-size: 1rem;
    }
    .btn-modal-ok {
      background: var(--pink);
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: 0.7rem 2.5rem;
      font-size: 0.95rem;
      font-weight: 700;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      box-shadow: 0 6px 20px rgba(232,69,122,0.3);
    }
    .btn-modal-ok:hover { background: var(--rose); transform: translateY(-2px); }
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
    <a href="orders.php">My Orders</a>
  </nav>
  <div class="icons">
    <i class="fas fa-user" style="color:var(--pink);" title="<?= $clientName ?>"></i>
    <a href="orders.php" title="My Orders" style="color:var(--muted); font-size:1.1rem;"><i class="fas fa-history"></i></a>
    <a href="logout.php" style="background:var(--pink);color:#fff;padding:0.4rem 1rem;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
      <i class="fas fa-sign-out-alt" style="margin-right:0.3rem;"></i>Logout
    </a>
  </div>
</header>

<div class="cart-section">

<?php if ($success): ?>
  <!-- SUCCESS -->
  <h1 class="page-heading">Order <span>Confirmed!</span></h1>
  <div class="success-card">
    <i class="fas fa-check-circle big"></i>
    <h2>Thank you, <?= $clientName ?>!</h2>
    <p>Your order has been placed. Our team will deliver it to you soon.</p>

    <!-- COD reminder -->
    <div class="cod-confirm">
      <i class="fas fa-money-bill-wave"></i> Cash on Delivery — pay when your order arrives
    </div>

    <!-- Delivery info -->
    <?php if (!empty($delivery)): ?>
    <div class="delivery-summary">
      <h4><i class="fas fa-truck" style="color:var(--pink); margin-right:0.4rem;"></i> Delivery Details</h4>
      <p><i class="fas fa-user"></i> <?= htmlspecialchars($delivery['fullname']) ?></p>
      <p><i class="fas fa-phone"></i> <?= htmlspecialchars($delivery['phone']) ?></p>
      <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($delivery['address']) ?>, <?= htmlspecialchars($delivery['city']) ?></p>
      <?php if (!empty($delivery['note'])): ?>
      <p><i class="fas fa-sticky-note"></i> <?= htmlspecialchars($delivery['note']) ?></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Order items -->
    <?php if (!empty($_SESSION['last_cart'])): ?>
      <?php foreach ($_SESSION['last_cart'] as $item): ?>
        <div style="display:flex;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid var(--border);font-size:0.92rem;">
          <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
          <span style="color:var(--pink);font-weight:700;">Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
        </div>
      <?php endforeach; ?>
      <div style="text-align:right;margin-top:1rem;font-size:1.2rem;font-weight:700;color:var(--pink);">
        Total: Rs <?= number_format(array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $_SESSION['last_cart'])), 2) ?>
      </div>
    <?php endif; ?>

    <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="index.php#products" class="btn"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
      <a href="orders.php" class="btn" style="background:var(--rose);"><i class="fas fa-history"></i> View Orders</a>
    </div>
  </div>

<?php else: ?>
  <!-- CART + DELIVERY -->
  <h1 class="page-heading">My <span>Cart</span></h1>

  <?php if (isset($error)): ?>
    <div class="error-msg"><i class="fas fa-exclamation-circle" style="margin-right:0.5rem;"></i><?= $error ?></div>
  <?php endif; ?>

  <?php if (empty($cart)): ?>
    <div class="empty-cart">
      <i class="fas fa-shopping-cart"></i>
      <h3>Your cart is empty</h3>
      <p>Browse our flowers and add some to your cart!</p>
      <a href="index.php#products" class="btn"><i class="fas fa-shopping-bag"></i> Browse Flowers</a>
    </div>

  <?php else: ?>
  <form method="POST" id="checkoutForm">
  <div class="checkout-grid">

    <!-- LEFT: Cart items -->
    <div>
      <?php
      // Fetch current stock for each item to enforce limit in JS
      foreach ($cart as $idx => &$item) {
          $s = $conn->prepare("SELECT quantity FROM products WHERE name = ?");
          $s->bind_param("s", $item["name"]);
          $s->execute();
          $row2 = $s->get_result()->fetch_assoc();
          $s->close();
          $item["stock"] = $row2 ? intval($row2["quantity"]) : 9999;
      }
      unset($item);
      foreach ($cart as $idx => $item): ?>
        <div class="cart-item">
          <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          <div class="cart-item-info">
            <h4><?= htmlspecialchars($item['name']) ?></h4>
            <p>Rs <?= number_format($item['price'], 2) ?> each</p>
            <p class="item-price">Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></p>
          </div>
          <div class="qty-form" id="qtyform_<?= $idx ?>">
            <input type="hidden" name="item_index" value="<?= $idx ?>">
            <button type="button" onclick="changeItemQty(<?= $idx ?>, -1)"
              <?= intval($item['quantity']) <= 1 ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : '' ?>>−</button>
            <input type="number" name="quantities[<?= $idx ?>]" id="qty_<?= $idx ?>"
              value="<?= intval($item['quantity']) ?>" min="1" readonly style="pointer-events:none;">
            <button type="button" onclick="changeItemQty(<?= $idx ?>, 1)" data-max="<?= $item['stock'] ?? 9999 ?>">+</button>
          </div>
          <button type="button" class="btn-remove" onclick="removeItem(<?= $idx ?>)" title="Remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      <?php endforeach; ?>

      <p style="margin-top:0.5rem;">
        <a href="index.php#products" style="color:var(--muted);font-size:0.9rem;">
          <i class="fas fa-arrow-left" style="margin-right:0.3rem;"></i> Continue Shopping
        </a>
      </p>
    </div>

    <!-- RIGHT: Delivery + Summary -->
    <div class="right-panel">

      <!-- Delivery Form -->
      <div class="delivery-card">
        <h3><i class="fas fa-truck"></i> Delivery Details</h3>

        <!-- User info pulled from users table (read-only, normalized) -->
        <div class="form-group">
          <label>Full Name</label>
          <div class="readonly-field">
            <i class="fas fa-user"></i>
            <?= htmlspecialchars($userData['name'] ?? $clientName) ?>
          </div>
        </div>

        <div class="form-group">
          <label>Email</label>
          <div class="readonly-field">
            <i class="fas fa-envelope"></i>
            <?= htmlspecialchars($userData['email'] ?? '') ?>
          </div>
        </div>

        <div class="form-group">
          <label>Phone <span class="req">*</span></label>
          <input type="tel" name="phone" id="phoneInput"
                 placeholder="e.g. 9800000000"
                 value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"
                 pattern="[0-9+\-\s]{7,15}"
                 required>
        </div>

        <p style="font-size:0.78rem; color:#aaa; margin: -0.3rem 0 1rem; padding: 0.5rem 0.8rem; background:#fff9f5; border-radius:8px; border-left: 3px solid #f0d0e0;">
          <i class="fas fa-info-circle" style="color:#f17dda; margin-right:0.3rem;"></i>
          Name and email are taken from your account. Phone, address and city are editable.
        </p>

        <div class="form-group">
          <label>Delivery Address <span class="req">*</span></label>
          <input type="text" name="address" placeholder="Street / Area / Landmark"
                 value="<?= htmlspecialchars($userData['address'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label>City <span class="req">*</span></label>
          <input type="text" name="city" placeholder="e.g. Kathmandu"
                 value="<?= htmlspecialchars($userData['city'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label>Order Note <span style="color:#aaa;font-weight:400;">(optional)</span></label>
          <textarea name="note" placeholder="Any special instructions for delivery..."></textarea>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="delivery-card" style="padding:1.2rem 1.5rem;">
        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
        <div class="cod-badge">
          <i class="fas fa-money-bill-wave"></i>
          <div>
            <strong>Cash on Delivery (COD)</strong>
            Pay in cash when your order arrives at your door.
          </div>
        </div>
        <input type="hidden" name="payment_method" value="COD">
      </div>

      <!-- Order Summary -->
      <div class="summary-card">
        <h3>Order Summary</h3>
        <?php foreach ($cart as $item): ?>
          <div class="summary-row">
            <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
            <span>Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
          </div>
        <?php endforeach; ?>
        <div class="summary-row" style="margin-top:0.5rem;">
          <span>Delivery</span>
          <span style="color:#27ae60; font-weight:600;">Free</span>
        </div>
        <div class="summary-row total">
          <span>Total</span>
          <span>Rs <?= number_format($total, 2) ?></span>
        </div>
      </div>

      <!-- Place Order -->
      <button type="submit" name="checkout" class="btn-checkout">
        <i class="fas fa-check-circle"></i> Place Order (COD)
      </button>

    </div>
  </div>
  </form>

  <!-- Hidden forms for remove/qty -->
  <form method="POST" id="removeForm" style="display:none;">
    <input type="hidden" name="remove" id="removeIndex">
  </form>
  <form method="POST" id="qtyUpdateForm" style="display:none;">
    <input type="hidden" name="update_qty" value="1">
    <div id="qtyHiddenInputs"></div>
  </form>

  <?php endif; ?>
<?php endif; ?>

<!-- Stock Alert Modal -->
<div id="stockModal">
  <div class="stock-modal-box">
    <div class="stock-modal-icon">
      <i class="fas fa-box-open"></i>
    </div>
    <h3>Limited Stock!</h3>
    <p id="stockModalMsg">Only <span id="stockModalQty">0</span> units available.</p>
    <button class="btn-modal-ok" onclick="closeStockModal()">Got it!</button>
  </div>
</div>

</div>

<script>
function changeItemQty(idx, delta) {
  var input   = document.getElementById("qty_" + idx);
  var current = parseInt(input.value) || 1;
  var newVal  = current + delta;
  if (newVal < 1) return;

  // Get stock limit from + button's data-max attribute
  var plusBtn = input.nextElementSibling;
  var maxStock = parseInt(plusBtn.getAttribute("data-max")) || 9999;
  if (newVal > maxStock) {
    showStockModal(maxStock);
    return;
  }

  // Update displayed value
  input.value = newVal;

  // Update minus button state
  var minusBtn = input.previousElementSibling;
  minusBtn.disabled      = (newVal <= 1);
  minusBtn.style.opacity = (newVal <= 1) ? "0.3" : "1";
  minusBtn.style.cursor  = (newVal <= 1) ? "not-allowed" : "pointer";

  // Collect all current quantities and submit via dedicated form
  var container = document.getElementById("qtyHiddenInputs");
  container.innerHTML = "";
  document.querySelectorAll("input[id^='qty_']").forEach(function(el) {
    var i = el.id.replace("qty_", "");
    var hidden = document.createElement("input");
    hidden.type  = "hidden";
    hidden.name  = "quantities[" + i + "]";
    hidden.value = el.value;
    container.appendChild(hidden);
  });

  document.getElementById("qtyUpdateForm").submit();
}

function removeItem(idx) {
  document.getElementById("removeIndex").value = idx;
  document.getElementById("removeForm").submit();
}
function showStockModal(max) {
  document.getElementById("stockModalQty").textContent = max;
  document.getElementById("stockModal").classList.add("show");
}
function closeStockModal() {
  document.getElementById("stockModal").classList.remove("show");
}
// Close on backdrop click
document.getElementById("stockModal").addEventListener("click", function(e) {
  if (e.target === this) closeStockModal();
});

</script>

</body>
</html>