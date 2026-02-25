<?php
session_start();
include 'db.php';

if(!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true){
    header("Location: login.php");
    exit;
}

$clientName  = $_SESSION['clientName'] ?? '';
$clientEmail = $_SESSION['clientEmail'] ?? '';
$cartItems = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cartItems as $item) { $total += floatval($item['price']); }

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name  = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    if($name && $email && count($cartItems) > 0){
       $_SESSION['cart'][] = [
    'name'  => $_GET['name'],
    'price' => floatval($_GET['price']),
    'image' => $_GET['image'] ?? 'default.jpg'  // store image filename
];
        $success = "✅ Thank you $name! Your order has been placed. Total: ₹ " . number_format($total,2);
    } else {
        $error = "❌ Please fill in all details and ensure your cart is not empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - RIMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family:'Poppins',sans-serif; margin:0; padding:0; background:#f0f4f8; }
header { background:#f17dda; color:#fff; padding:1rem; text-align:center; }
header a { color:#fff; text-decoration:none; font-weight:600; }
.checkout-container { max-width:1200px; margin:2rem auto; display:flex; flex-wrap:wrap; gap:2rem; }
.cart-summary, .checkout-form { background:#fff; padding:2rem; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.08); }
.cart-summary { flex:1 1 500px; }
.checkout-form { flex:1 1 400px; }
h1 { width:100%; text-align:center; margin-bottom:2rem; color:#333; }
.cart-item { display:flex; gap:1rem; padding:1rem; border-radius:10px; border:1px solid #eee; margin-bottom:1rem; transition:0.3s; align-items:center; }
.cart-item:hover { box-shadow:0 8px 20px rgba(0,0,0,0.1); }
.cart-item img { width:80px; height:80px; object-fit:cover; border-radius:10px; }
.cart-item-details { flex:1; }
.cart-item-details h3 { margin:0; font-weight:500; color:#444; }
.cart-item-details p { margin:0.3rem 0 0; font-weight:600; color:#f17dda; }
.remove-btn { background:#ff4d4d; color:#fff; border:none; padding:0.4rem 0.8rem; border-radius:6px; cursor:pointer; transition:0.3s; }
.remove-btn:hover { background:#e03e3e; }
.total { font-size:1.3rem; font-weight:600; color:#065f46; text-align:right; margin-top:1rem; }
.checkout-form form { display:flex; flex-direction: column; gap:1rem; }
.checkout-form input { padding:0.8rem; border-radius:8px; border:1px solid #ccc; font-size:1rem; }
.checkout-form button { padding:0.8rem; border-radius:10px; border:none; background:#f17dda; color:#fff; font-weight:600; font-size:1rem; cursor:pointer; transition:0.3s; }
.checkout-form button:hover { background:#d856c7; }
.success { background:#e0fdf4; border:1.5px solid #aafdfd; padding:1rem; border-radius:8px; color:#065f46; font-weight:600; text-align:center; margin-bottom:1rem; }
.error { background:#ffe0e0; border:1.5px solid #ff4d4d; padding:1rem; border-radius:8px; color:#c0392b; font-weight:600; text-align:center; margin-bottom:1rem; }
.empty-cart { text-align:center; padding:2rem; font-size:1.1rem; color:#555; }
@media(max-width:900px){ .checkout-container { flex-direction:column; } }
</style>
</head>
<body>

<header>
<a href="#" class="cart-btn"
   data-name="<?= htmlspecialchars($row['name']) ?>"
   data-price="<?= $row['price'] ?>"
   data-image="<?= htmlspecialchars($row['image']) ?>">
   <i class="fas fa-shopping-cart"></i>
</a>
</header>

<div class="checkout-container">
<h1>Checkout</h1>

<?php if(!empty($cartItems)): ?>

<div class="cart-summary" id="cartSummary">
    <h2>Order Summary</h2>
    <?php foreach($cartItems as $index => $item): ?>
    <div class="cart-item" data-index="<?php echo $index; ?>">
        <!-- Example: Add a product image, replace 'default.jpg' with your DB image -->
        <img src="images/<?php echo htmlspecialchars($item['image'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
        <div class="cart-item-details">
            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
            <p>₹ <?php echo number_format($item['price'],2); ?></p>
        </div>
        <button class="remove-btn" onclick="removeItem(<?php echo $index; ?>)">Remove</button>
    </div>
    <?php endforeach; ?>
    <div class="total" id="total">Total: ₹ <?php echo number_format($total,2); ?></div>
</div>

<div class="checkout-form">
    <h2>Billing Details</h2>
    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="post" id="checkoutForm">
        <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($clientName); ?>" required>
        <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($clientEmail); ?>" required>
        <input type="tel" name="phone" placeholder="Phone Number">
        <button type="submit">Place Order</button>
    </form>
</div>

<?php else: ?>
<div class="empty-cart">
    <p>Your cart is empty 😔</p>
    <a href="index.php" class="checkout-btn">Shop Now</a>
</div>
<?php endif; ?>
</div>

<script>
// Remove item dynamically
function removeItem(index){
    fetch("purchase.php", {
        method:"POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "action=remove&index="+index
    })
    .then(r => r.json())
    .then(data => {
        const summary = document.getElementById("cartSummary");
        if(data.cart.length > 0){
            let html = "<h2>Order Summary</h2>";
            data.cart.forEach((item,i)=>{
                html += `<div class="cart-item" data-index="${i}">
                            <img src="images/${item.image ?? 'default.jpg'}" alt="${item.name}">
                            <div class="cart-item-details">
                                <h3>${item.name}</h3>
                                <p>₹ ${item.price.toFixed(2)}</p>
                            </div>
                            <button class="remove-btn" onclick="removeItem(${i})">Remove</button>
                         </div>`;
            });
            html += `<div class="total" id="total">Total: ₹ ${data.total.toFixed(2)}</div>`;
            summary.innerHTML = html;
        } else {
            summary.innerHTML = `<div class="empty-cart">
                <p>Your cart is empty 😔</p>
                <a href="index.php" class="checkout-btn">Shop Now</a>
            </div>`;
        }
    });
}
</script>

</body>
</html>