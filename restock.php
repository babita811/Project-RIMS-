<?php
include 'db.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: view_products.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch product details
$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();

if (!$product) {
    echo "Product not found!";
    exit();
}

// Handle Restock
if (isset($_POST['restock'])) {
    $restock_qty = intval($_POST['restock_qty']);

    if ($restock_qty <= 0) {
        $error = "Restock quantity must be greater than 0!";
    } else {
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $restock_qty, $id);

        if ($stmt->execute()) {
            $success = "Stock updated successfully!";
            // Refresh product data
            $product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
        } else {
            $error = "Error updating stock: " . $conn->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Restock Product</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffe6f0, #fff0f5);
            padding: 50px;
        }

        .container {
            width: 40%;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(214,51,132,0.2);
            text-align: center;
        }

        h2 {
            color: #d63384;
        }

        input[type="number"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            background-color: #5563DE;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #3742a3;
        }

        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }

        a { text-decoration: none; color: #d63384; }
    </style>
</head>
<body>

<div class="container">
    <h2>Restock: <?= htmlspecialchars($product['name']) ?></h2>
    <p><strong>Current Stock:</strong> <?= $product['quantity'] ?></p>

    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>

    <form method="POST">
        <input type="number" name="restock_qty" value="1" min="1" required>
        <input type="submit" name="restock" value="Restock">
    </form>

    <br>
    <a href="view_products.php">← Back to Products</a>
</div>

</body>
</html>
