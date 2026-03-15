<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {ESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

if (!isset($_GET['id'])) {
    header("Location: view_products.php");
    exit();
}

$id      = intval($_GET['id']);
$stmt    = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: view_products.php");
    exit();
}

$success = $error = "";

if (isset($_POST['update_product'])) {
    $name     = trim($_POST['product_name']);
    $category_id = intval($_POST['product_category']);
    $price    = floatval($_POST['product_price']);
    $quantity = intval($_POST['product_quantity']);

    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type     = $_FILES['image']['type'];
        $file_size     = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type! Only JPG, PNG, GIF allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error = "File size exceeds 2MB!";
        } else {
            $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('flower_', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name)) {
                $error = "Failed to upload image!";
            } else {
                // Delete old image
                if (!empty($product['image']) && file_exists("uploads/" . $product['image'])) {
                    unlink("uploads/" . $product['image']);
                }
                $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, quantity=?, image=? WHERE id=?");
                $stmt->bind_param("sidisl", $name, $category_id, $price, $quantity, $image_name, $id);
                if ($stmt->execute()) {
                    $success = "Product updated successfully!";
                    $product = array_merge($product, compact('name', 'category', 'price', 'quantity', 'image_name'));
                    $product['image'] = $image_name;
                } else {
                    $error = "Error: " . $conn->error;
                }
                $stmt->close();
            }
        }
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, quantity=? WHERE id=?");
        $stmt->bind_param("sidii", $name, $category_id, $price, $quantity, $id);
        if ($stmt->execute()) {
            $success = "Product updated successfully!";
            $product['name']     = $name;
            $product['category'] = $category;
            $product['price']    = $price;
            $product['quantity'] = $quantity;
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Flower – RIMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff0f5); min-height: 100vh; padding-top: 70px; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; background: linear-gradient(90deg, #d63384, #c2185b); padding: 12px 20px; z-index: 1000; display: flex; gap: 6px; align-items: center; }
        .navbar a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 0.88rem; font-weight: 500; transition: background 0.2s; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .navbar a.active { background: white; color: #d63384; font-weight: 700; }
        .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }

        .container { width: 92%; max-width: 580px; margin: 40px auto; }

        .panel { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 6px 24px rgba(214,51,132,0.12); }

        h2 { color: #d63384; font-size: 1.4rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; padding-bottom: 1rem; border-bottom: 2px solid #fce8f2; }

        .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.88rem; font-weight: 500; }
        .msg.success { background: #e0fdf4; color: #065f46; border: 1px solid #6ee7b7; }
        .msg.error   { background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; }

        .form-group { margin-bottom: 1.1rem; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #555; margin-bottom: 0.4rem; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%; padding: 0.75rem 1rem;
            border: 1.5px solid #f0d0e0; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.92rem;
            color: #333; background: #fff9f5; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #d63384;
            box-shadow: 0 0 0 3px rgba(214,51,132,0.1);
            background: #fff;
        }

        .price-wrapper { display: flex; align-items: center; border: 1.5px solid #f0d0e0; border-radius: 10px; background: #fff9f5; overflow: hidden; transition: border-color 0.2s; }
        .price-wrapper:focus-within { border-color: #d63384; box-shadow: 0 0 0 3px rgba(214,51,132,0.1); }
        .price-wrapper span { padding: 0 1rem; color: #d63384; font-weight: 700; border-right: 1.5px solid #f0d0e0; background: #fce8f2; display: flex; align-items: center; white-space: nowrap; height: 100%; }
        .price-wrapper input { border: none !important; box-shadow: none !important; background: transparent !important; }

        .current-img { margin-bottom: 0.8rem; }
        .current-img img { width: 100%; max-height: 200px; object-fit: cover; border-radius: 10px; border: 2px solid #fce8f2; }
        .current-img p { font-size: 0.78rem; color: #aaa; margin-top: 0.4rem; text-align: center; }

        .file-upload { border: 2px dashed #f0d0e0; border-radius: 10px; padding: 1.2rem; text-align: center; cursor: pointer; transition: border-color 0.2s; background: #fff9f5; }
        .file-upload:hover { border-color: #d63384; background: #fff; }
        .file-upload i { font-size: 1.8rem; color: #f0d0e0; display: block; margin-bottom: 0.4rem; }
        .file-upload p { font-size: 0.78rem; color: #aaa; }
        .file-upload input { display: none; }
        .filename { font-size: 0.82rem; color: #d63384; margin-top: 0.4rem; font-weight: 600; }

        .btn-row { display: flex; gap: 0.8rem; margin-top: 1rem; }
        .btn-submit { flex: 1; padding: 0.9rem; background: #d63384; color: white; border: none; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 6px 18px rgba(214,51,132,0.3); display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-submit:hover { background: #b52b70; transform: translateY(-2px); }
        .btn-back { padding: 0.9rem 1.4rem; background: #f5f5f5; color: #555; border: none; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 0.4rem; transition: background 0.2s; }
        .btn-back:hover { background: #ebebeb; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php" class="active">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <div class="panel">
        <h2><i class="fas fa-edit"></i> Edit Flower</h2>

        <?php if ($success): ?><div class="msg success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="msg error">❌ <?= $error ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label><i class="fas fa-tag"></i> Flower Name</label>
                <input type="text" name="product_name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Category</label>
                <select name="product_category" required>
                    <option value="">Select Category</option>
                    <?php
                    $cats = $conn->query("SELECT id, name, icon FROM categories ORDER BY name ASC");
                    while ($c = $cats->fetch_assoc()):
                    ?>
                    <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['icon'] ?? '') ?> <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <div style="margin-top:0.5rem; font-size:0.78rem; color:#aaa;">
                    <i class="fas fa-info-circle" style="color:#d63384;"></i>
                    Can't find the category? <a href="categories.php" style="color:#d63384; font-weight:600;">Manage Categories</a>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-money-bill"></i> Price</label>
                <div class="price-wrapper">
                    <span>Rs</span>
                    <input type="number" name="product_price" value="<?= $product['price'] ?>" step="0.01" min="0" required>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-cubes"></i> Quantity</label>
                <input type="number" name="product_quantity" value="<?= $product['quantity'] ?>" min="0" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-image"></i> Flower Image</label>
                <div class="current-img">
                    <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="Current Image">
                    <p>Current image — upload a new one to replace it</p>
                </div>
                <div class="file-upload" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload new image (JPG, PNG, GIF — max 2MB)</p>
                    <div class="filename" id="fileName">No file chosen</div>
                    <input type="file" id="imageInput" name="image" accept="image/*"
                           onchange="document.getElementById('fileName').textContent = this.files[0]?.name || 'No file chosen'">
                </div>
            </div>

            <div class="btn-row">
                <a href="view_products.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" name="update_product" class="btn-submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>

        </form>
    </div>
</div>

</body>
</html>