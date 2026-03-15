<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';
$page = basename($_SERVER['PHP_SELF']);

// Load category icons from DB
$catIconMap = [];
$catRes = $conn->query("SELECT name, icon FROM categories");
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $catIconMap[$c['name']] = $c['icon'] ?: '🌿';
    }
}
$low_stock_threshold = 5;

// Handle restock directly
if(isset($_POST['restock'])){
    $id = intval($_POST['product_id']);
    $qty = intval($_POST['restock_qty']);

    if($qty > 0){
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $qty, $id);
        $stmt->execute();
        $stmt->close();
        $restock_msg = "Product restocked successfully!";
    } else {
        $restock_msg = "Quantity must be greater than 0!";
    }
}

// Handle search/filter/sort
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$query = "SELECT p.*, c.name as category_name, c.icon as category_icon FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1";
$params = [];
$types = "";

if(!empty($search)){ 
    $query .= " AND (LOWER(p.name) LIKE LOWER(?) OR LOWER(c.name) LIKE LOWER(?))"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Category filter

if (!empty($category)) {
    $query .= " AND LOWER(c.name) LIKE LOWER(?)";
    $params[] = "%$category%";
    $types .= "s";
}



if(!empty($sort)){
    switch($sort){
        case 'name_asc': $query.=" ORDER BY p.name ASC"; break;
        case 'name_desc': $query.=" ORDER BY p.name DESC"; break;
        case 'price_asc': $query.=" ORDER BY p.price ASC"; break;
        case 'price_desc': $query.=" ORDER BY p.price DESC"; break;
        case 'quantity_asc': $query.=" ORDER BY p.quantity ASC"; break;
        case 'quantity_desc': $query.=" ORDER BY p.quantity DESC"; break;
    }
}



$stmt = $conn->prepare($query);
if(!empty($params)){ $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Flowers</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { padding-top: 60px; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#ffe6f0,#fff0f5);}
        .navbar { position: fixed; top:0; left:0; width:100%; background: linear-gradient(90deg,#d63384,#c2185b); padding:15px; z-index:1000; }
        .navbar a { color:white; text-decoration:none; padding:10px 18px; border-radius:20px; }
        .navbar a.active { background:white; color:#d63384; }
        .container { width:90%; margin:30px auto; }
        h2 { text-align:center; color:#d63384; }

        .product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:20px; margin-top:20px; }
        .product-card { background: rgba(255,255,255,0.3); padding:15px; border-radius:15px; box-shadow:0 5px 15px rgba(214,51,132,0.2); text-align:center; transition:0.3s; }
        .product-card:hover { transform:translateY(-5px); }
        .product-card img { width:100%; height:180px; object-fit:cover; border-radius:12px; }
        .low-stock-label { color:red; font-weight:bold; margin-top:5px; }

        .restock-form input[type='number'] { width:50px; padding:5px; margin-right:5px; border-radius:8px; border:1px solid #ccc; }
        .restock-form input[type='submit'] { padding:5px 10px; border-radius:8px; border:none; background:#ff66a3; color:white; cursor:pointer; }
        .restock-form input[type='submit']:hover { background:#ff3385; }

        form input, form select { padding:8px 12px; border-radius:8px; border:1px solid #ccc; margin:5px; }
        form input[type="submit"] { background:#d63384; color:white; border:none; cursor:pointer; }
        form input[type="submit"]:hover { background:#b52b70; }

        .success { color:green; font-weight:bold; }
        .error { color:red; font-weight:bold; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php" class="<?= $page=='admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="add_product.php" class="<?= $page=='add_product.php' ? 'active' : '' ?>">Add Flower</a>
    <a href="view_products.php" class="<?= $page=='view_products.php' ? 'active' : '' ?>">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <h2>Flower Catalog</h2>

    <?php if(isset($restock_msg)) echo "<div class='".(strpos($restock_msg,'success')!==false?'success':'error')."'>$restock_msg</div>"; ?>

    <!-- Search/Filter/Sort -->
    <form method="GET" id="filterForm" style="text-align:center; margin-bottom:20px;">
        <input type="text" name="search" placeholder="Search flowers" value="<?= htmlspecialchars($search) ?>">
        <select name="category">
            <option value="">All Categories</option>
           <option value="Rose" <?= $category=='Rose' ? 'selected' : '' ?>>Roses</option>
           <option value="Lotus" <?= $category=='Lotus' ? 'selected' : '' ?>>Lotus</option>
           <option value="Lily" <?= $category=='Lily'?'selected':'' ?>>Lilies</option>
            <option value="Tulip" <?= $category=='Tulip'?'selected':'' ?>>Tulips</option>
            <option value="Orchid" <?= $category=='Orchid'?'selected':'' ?>>Orchids</option>
            <option value="Gerbera" <?= $category=='Gerbera'?'selected':'' ?>>Gerberas</option>
            <option value="Sunflower" <?= $category=='Sunflower'?'selected':'' ?>>Sunflowers</option>
            <option value="Lavender" <?= $category=='Lavender'?'selected':'' ?>>Lavender</option>
            <option value="Carnation" <?= $category=='Carnation'?'selected':'' ?>>Carnations</option>
            <option value="Baby" <?= $category=='Baby'?'selected':'' ?>>Baby’s Breath</option>
        </select>

        <select name="sort">
            <option value="">Sort By</option>
            <option value="name_asc" <?= $sort=='name_asc'?'selected':'' ?>>Name A-Z</option>
            <option value="name_desc" <?= $sort=='name_desc'?'selected':'' ?>>Name Z-A</option>
            <option value="price_asc" <?= $sort=='price_asc'?'selected':'' ?>>Price Low → High</option>
            <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>>Price High → Low</option>
            <option value="quantity_asc" <?= $sort=='quantity_asc'?'selected':'' ?>>Stock Low → High</option>
            <option value="quantity_desc" <?= $sort=='quantity_desc'?'selected':'' ?>>Stock High → Low</option>
        </select>

       
    </form>

    <div class="product-grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="product-card">
                <img src="uploads/<?= $row['image'] ?>" alt="<?= $row['name'] ?>">
                <h3><?= $row['name'] ?></h3>
                <?php
$category_name = $row['category_name'] ?? 'Unknown';
$catIcon = $catIconMap[$category_name] ?? '🌿';
?>
<p>
    <span style="background:#fce8f2; color:#d63384; padding:4px 12px; border-radius:20px; font-size:13px; font-weight:600; border:1px solid #f0c0e0;">
       <?= $catIcon ?> <?= htmlspecialchars($category_name) ?>
    </span>
</p>

                <p>Price: Rs <?= number_format($row['price'], 2) ?></p>
                <p>Stock: <?= $row['quantity'] ?></p>

                <?php if($row['quantity'] <= $low_stock_threshold): ?>
                    <div class="low-stock-label">Low Stock!</div>
                <?php endif; ?>

                <!-- Restock Form -->
                <form class="restock-form" method="POST">
                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                    <input type="number" name="restock_qty" value="1" min="1">
                    <input type="submit" name="restock" value="Restock">
                </form>

                <a href="edit_product.php?id=<?= $row['id'] ?>">Edit</a> |
                <a href="delete_product.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
const form = document.getElementById("filterForm");
const inputs = form.querySelectorAll("input, select");

inputs.forEach(input => {
    input.addEventListener("change", () => form.submit());
    input.addEventListener("keyup", () => {
        clearTimeout(window.searchTimer);
        window.searchTimer = setTimeout(() => form.submit(), 500);
    });
});
</script>


</body>
</html>