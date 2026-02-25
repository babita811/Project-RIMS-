<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

if (isset($_POST['add_product'])) {
    $name     = trim($_POST['product_name']);
    $price    = floatval($_POST['product_price']);
    $qty      = intval($_POST['product_quantity']);
    $category = $_POST['product_category'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size      = 2 * 1024 * 1024;
        $file_type     = $_FILES['image']['type'];
        $file_size     = $_FILES['image']['size'];
        $tmp_name      = $_FILES['image']['tmp_name'];

        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type! Only JPG, PNG, GIF allowed.";
        } elseif ($file_size > $max_size) {
            $error = "File size exceeds 2MB!";
        } else {
            $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('flower_', true) . '.' . $ext;
            if (!move_uploaded_file($tmp_name, "uploads/" . $image_name)) {
                $error = "Failed to upload image!";
            } else {
                $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, category, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sdiss", $name, $price, $qty, $category, $image_name);
                if ($stmt->execute()) {
                    $success = "Flower added successfully!";
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } else {
        $error = "Please upload a valid image!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Flower - RIMS Admin</title>
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
        .container { width: 92%; max-width: 560px; margin: 40px auto; }
        .panel { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 6px 24px rgba(214,51,132,0.12); }
        h2 { color: #d63384; font-size: 1.4rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; padding-bottom: 1rem; border-bottom: 2px solid #fce8f2; }
        .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.88rem; font-weight: 500; }
        .msg.success { background: #e0fdf4; color: #065f46; border: 1px solid #6ee7b7; }
        .msg.error   { background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; }
        .form-group { margin-bottom: 1.1rem; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #555; margin-bottom: 0.4rem; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group select {
            width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #f0d0e0; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.92rem; color: #333;
            background: #fff9f5; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, .form-group select:focus { border-color: #d63384; box-shadow: 0 0 0 3px rgba(214,51,132,0.1); background: #fff; }
        .price-wrapper { display: flex; align-items: center; border: 1.5px solid #f0d0e0; border-radius: 10px; background: #fff9f5; overflow: hidden; transition: border-color 0.2s; }
        .price-wrapper:focus-within { border-color: #d63384; box-shadow: 0 0 0 3px rgba(214,51,132,0.1); }
        .price-wrapper span { padding: 0 1rem; color: #d63384; font-weight: 700; border-right: 1.5px solid #f0d0e0; background: #fce8f2; display: flex; align-items: center; white-space: nowrap; }
        .price-wrapper input { border: none !important; box-shadow: none !important; background: transparent !important; }
        .file-upload { border: 2px dashed #f0d0e0; border-radius: 10px; padding: 1.5rem; text-align: center; cursor: pointer; transition: border-color 0.2s; background: #fff9f5; }
        .file-upload:hover { border-color: #d63384; background: #fff; }
        .file-upload i { font-size: 2rem; color: #f0d0e0; display: block; margin-bottom: 0.5rem; }
        .file-upload p { font-size: 0.82rem; color: #aaa; }
        .file-upload input { display: none; }
        .filename { font-size: 0.85rem; color: #d63384; margin-top: 0.5rem; font-weight: 600; }
        .btn-submit { width: 100%; padding: 0.9rem; background: #d63384; color: white; border: none; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 6px 18px rgba(214,51,132,0.3); display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.5rem; }
        .btn-submit:hover { background: #b52b70; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php" class="active">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <div class="panel">
        <h2><i class="fas fa-plus-circle"></i> Add New Flower</h2>

        <?php if (isset($success)): ?><div class="msg success">✅ <?= $success ?></div><?php endif; ?>
        <?php if (isset($error)):   ?><div class="msg error">❌ <?= $error ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Flower Name</label>
                <input type="text" name="product_name" placeholder="e.g. Red Rose Bouquet" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-money-bill"></i> Price</label>
                <div class="price-wrapper">
                    <span>Rs</span>
                    <input type="number" name="product_price" placeholder="0.00" min="0" step="0.01" required>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-cubes"></i> Quantity</label>
                <input type="number" name="product_quantity" placeholder="0" min="0" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Category</label>
                <select name="product_category" required>
                    <option value="">Select Category</option>
                    <?php
                    $cats = $conn->query("SELECT name FROM categories ORDER BY name ASC");
                    while ($c = $cats->fetch_assoc()):
                    ?>
                    <option value="<?= $c['name'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <div style="margin-top:0.5rem; font-size:0.78rem; color:#aaa;">
                    <i class="fas fa-info-circle" style="color:#d63384;"></i>
                    Can't find the category? <a href="categories.php" style="color:#d63384; font-weight:600;">Manage Categories</a>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-image"></i> Flower Image</label>
                <div class="file-upload" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload (JPG, PNG, GIF — max 2MB)</p>
                    <div class="filename" id="fileName">No file chosen</div>
                    <input type="file" id="imageInput" name="image" accept="image/*" required
                           onchange="document.getElementById('fileName').textContent = this.files[0]?.name || 'No file chosen'">
                </div>
            </div>

            <button type="submit" name="add_product" class="btn-submit">
                <i class="fas fa-plus-circle"></i> Add Flower
            </button>
        </form>
    </div>
</div>

</body>
</html>