<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin'){
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

// Record Sale
if (isset($_POST['record_sale'])) {
    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $error = "Product not found!";
    } elseif ($quantity < 1) {
        $error = "Quantity must be at least 1.";
    } elseif ($quantity > $product['quantity']) {
        $error = "Not enough stock! Only {$product['quantity']} available.";
    } else {
        $subtotal = $product['price'] * $quantity;

        // Deduct stock
        $update = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $update->bind_param("ii", $quantity, $product_id);
        $update->execute();
        $update->close();

        // Record sale
        $insert = $conn->prepare("INSERT INTO sales (product_id, quantity, subtotal, sale_date) VALUES (?, ?, ?, NOW())");
        $insert->bind_param("iid", $product_id, $quantity, $subtotal);
        $insert->execute();
        $insert->close();

        $success = "Sale recorded! {$product['name']} × {$quantity} = Rs " . number_format($subtotal, 2);
    }
}

// Get products with stock
$products = $conn->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Recent sales today
$todaySales = $conn->query("
    SELECT s.id, p.name, p.image, s.quantity, s.subtotal, s.sale_date
    FROM sales s
    JOIN products p ON s.product_id = p.id
    WHERE DATE(s.sale_date) = CURDATE()
    ORDER BY s.sale_date DESC
")->fetch_all(MYSQLI_ASSOC);

$todayTotal = array_sum(array_column($todaySales, 'subtotal'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale – RIMS Admin</title>
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

        .container {
            width: 92%; max-width: 1100px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 24px;
        }
        @media (max-width: 860px) { .container { grid-template-columns: 1fr; } }

        .panel {
            background: white;
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 6px 24px rgba(214,51,132,0.12);
            align-self: start;
        }

        h2 {
            color: #d63384; font-size: 1.3rem;
            margin-bottom: 1.4rem;
            display: flex; align-items: center; gap: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #fce8f2;
        }

        .msg {
            padding: 10px 14px; border-radius: 8px;
            margin-bottom: 1rem; font-size: 0.88rem; font-weight: 500;
        }
        .msg.error   { background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; }
        .msg.success { background: #e0fdf4; color: #065f46; border: 1px solid #6ee7b7; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block; font-size: 0.82rem;
            font-weight: 600; color: #555; margin-bottom: 0.4rem;
        }
        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #f0d0e0;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.92rem; color: #333;
            background: #fff9f5; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group select:focus,
        .form-group input[type="number"]:focus {
            border-color: #d63384;
            box-shadow: 0 0 0 3px rgba(214,51,132,0.1);
            background: #fff;
        }

        /* Stock indicator */
        .stock-indicator {
            font-size: 0.78rem;
            margin-top: 5px;
            font-weight: 600;
            display: none;
        }
        .stock-indicator.low  { color: #e74c3c; }
        .stock-indicator.good { color: #27ae60; }

        /* Live preview */
        .preview {
            background: linear-gradient(135deg, #ffe0f0, #fff0f7);
            border-radius: 10px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.2rem;
            display: none;
        }
        .preview.show { display: block; }
        .preview-row {
            display: flex; justify-content: space-between;
            font-size: 0.85rem; color: #555; margin-bottom: 4px;
        }
        .preview-total {
            display: flex; justify-content: space-between;
            font-size: 1.1rem; font-weight: 700;
            color: #d63384; margin-top: 8px;
            padding-top: 8px; border-top: 1px solid #f0d0e0;
        }

        .btn-record {
            width: 100%;
            padding: 0.9rem;
            background: #d63384; color: white;
            border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s;
            box-shadow: 0 6px 18px rgba(214,51,132,0.3);
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-record:hover { background: #b52b70; transform: translateY(-2px); }

        /* Today's sales */
        .today-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 1rem;
        }
        .today-total {
            background: #fce8f2; color: #d63384;
            padding: 4px 14px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 700;
        }

        .sale-row {
            display: grid;
            grid-template-columns: 44px 1fr auto;
            gap: 0.8rem; align-items: center;
            padding: 0.7rem 0;
            border-bottom: 1px solid #fce8f2;
        }
        .sale-row:last-child { border-bottom: none; }
        .sale-row img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }
        .sale-name { font-weight: 600; font-size: 0.88rem; color: #333; }
        .sale-meta { font-size: 0.75rem; color: #999; margin-top: 2px; }
        .sale-amount { font-weight: 700; color: #d63384; font-size: 0.9rem; white-space: nowrap; }

        .empty { text-align: center; padding: 2rem; color: #ccc; }
        .empty i { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="sales.php" class="active">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">

    <!-- LEFT: Record sale form -->
    <div class="panel">
        <h2><i class="fas fa-cash-register"></i> Record a Sale</h2>

        <?php if (isset($error)):   ?><div class="msg error">❌ <?= $error ?></div><?php endif; ?>
        <?php if (isset($success)): ?><div class="msg success">✅ <?= $success ?></div><?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="empty">
                <i class="fas fa-box-open"></i>
                <p>No flowers in stock.<br>
                <a href="add_product.php" style="color:#d63384;">Add some flowers</a> first.</p>
            </div>
        <?php else: ?>
        <form method="POST" id="saleForm">

            <div class="form-group">
                <label><i class="fas fa-seedling"></i> Flower</label>
                <select name="product_id" id="productSelect" required onchange="updatePreview()">
                    <option value="">-- Select a flower --</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"
                            data-price="<?= $p['price'] ?>"
                            data-stock="<?= $p['quantity'] ?>"
                            data-name="<?= htmlspecialchars($p['name']) ?>">
                        <?= htmlspecialchars($p['name']) ?>
                        (Stock: <?= $p['quantity'] ?>) — Rs <?= number_format($p['price'], 2) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-sort-numeric-up"></i> Quantity</label>
                <input type="number" name="quantity" id="qtyInput"
                       min="1" max="9999" value="1" required
                       onchange="updatePreview()" oninput="updatePreview()">
                <!-- Stock indicator shown after flower is selected -->
                <div class="stock-indicator" id="stockIndicator"></div>
            </div>

            <!-- Live total preview -->
            <div class="preview" id="preview">
                <div class="preview-row">
                    <span>Unit Price</span>
                    <span id="prevPrice">Rs 0</span>
                </div>
                <div class="preview-row">
                    <span>Quantity</span>
                    <span id="prevQty">0</span>
                </div>
                <div class="preview-row">
                    <span>Available Stock</span>
                    <span id="prevStock">0</span>
                </div>
                <div class="preview-total">
                    <span>Total</span>
                    <span id="prevTotal">Rs 0</span>
                </div>
            </div>

            <button type="submit" name="record_sale" class="btn-record">
                <i class="fas fa-check-circle"></i> Record Sale
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Today's sales -->
    <div class="panel">
        <div class="today-header">
            <h2 style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
                <i class="fas fa-calendar-day"></i> Today's Sales
            </h2>
            <?php if (!empty($todaySales)): ?>
            <span class="today-total">Rs <?= number_format($todayTotal, 2) ?></span>
            <?php endif; ?>
        </div>
        <div style="border-bottom: 2px solid #fce8f2; margin-bottom: 1rem;"></div>

        <?php if (!empty($todaySales)): ?>
            <?php foreach ($todaySales as $s): ?>
            <div class="sale-row">
                <img src="uploads/<?= htmlspecialchars($s['image']) ?>" alt="">
                <div>
                    <div class="sale-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="sale-meta">
                        Qty: <?= $s['quantity'] ?> &nbsp;·&nbsp;
                        <?= date('h:i A', strtotime($s['sale_date'])) ?>
                    </div>
                </div>
                <div class="sale-amount">Rs <?= number_format($s['subtotal'], 2) ?></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-receipt"></i>
                <p>No sales recorded today yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function updatePreview() {
    var select   = document.getElementById('productSelect');
    var qtyInput = document.getElementById('qtyInput');
    var qty      = parseInt(qtyInput.value) || 0;
    var preview  = document.getElementById('preview');
    var stockIndicator = document.getElementById('stockIndicator');

    if (!select.value || qty < 1) {
        preview.classList.remove('show');
        stockIndicator.style.display = 'none';
        return;
    }

    var opt   = select.options[select.selectedIndex];
    var price = parseFloat(opt.getAttribute('data-price')) || 0;
    var stock = parseInt(opt.getAttribute('data-stock')) || 1;

    // ── Set max to available stock ──
    qtyInput.max = stock;

    // ── If entered qty exceeds stock, cap it ──
    if (qty > stock) {
        qtyInput.value = stock;
        qty = stock;
    }

    // ── Show stock indicator ──
    stockIndicator.style.display = 'block';
    if (stock <= 5) {
        stockIndicator.textContent = '⚠️ Only ' + stock + ' left in stock!';
        stockIndicator.className = 'stock-indicator low';
    } else {
        stockIndicator.textContent = '✅ ' + stock + ' available in stock';
        stockIndicator.className = 'stock-indicator good';
    }

    var total = price * qty;

    document.getElementById('prevPrice').textContent = 'Rs ' + price.toLocaleString();
    document.getElementById('prevQty').textContent   = qty;
    document.getElementById('prevStock').textContent = stock;
    document.getElementById('prevTotal').textContent = 'Rs ' + total.toLocaleString();
    preview.classList.add('show');
}
</script>

</body>
</html>