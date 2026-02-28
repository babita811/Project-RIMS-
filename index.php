<?php 
session_start();
include 'db.php';

$isLoggedIn = isset($_SESSION['isClientLoggedIn']) && $_SESSION['isClientLoggedIn'] === true;
$clientName = htmlspecialchars($_SESSION['clientName'] ?? '');

$query = "SELECT p.*, c.name as category_name, c.icon as category_icon FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$allProducts = $result->fetch_all(MYSQLI_ASSOC);

// Fetch categories from DB
$categories = $conn->query("SELECT name, icon FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RIMS Homepage</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/homepage.css">
</head>

<body>

<!-- HEADER -->
<header>
  <input type="checkbox" id="menu-bar">
  <label for="menu-bar" class="fas fa-bars"></label>

  <a href="#" class="logo">RIMS<span>.</span></a>

  <nav class="navbar">
    <a href="#home">Home</a>
    <a href="#about">About Us</a>
    <a href="#products">Products</a>
    <a href="#contact">Contact</a>
  </nav>

  <div class="icons">
    <?php if($isLoggedIn): ?>
      <a href="orders.php" title="My Orders" style="color:var(--muted); font-size:1.1rem;"><i class="fas fa-history"></i></a>
      <a href="purchase.php" title="Cart" style="color:var(--muted); font-size:1.1rem;"><i class="fas fa-shopping-cart"></i></a>
      <a href="logout.php" title="Logout" style="
        background:var(--pink);
        color:#fff;
        padding:0.4rem 1rem;
        border-radius:20px;
        font-size:0.85rem;
        font-weight:600;
        text-decoration:none;
        transition: background 0.2s;
      " onmouseover="this.style.background='var(--rose)'" onmouseout="this.style.background='var(--pink)'">
        <i class="fas fa-sign-out-alt" style="margin-right:0.3rem;"></i>Logout
      </a>
    <?php else: ?>
      <a href="login.php" title="Login" style="
        background:var(--pink);
        color:#fff;
        padding:0.4rem 1rem;
        border-radius:20px;
        font-size:0.85rem;
        font-weight:600;
        text-decoration:none;
        transition: background 0.2s;
      " onmouseover="this.style.background='var(--rose)'" onmouseout="this.style.background='var(--pink)'">
        <i class="fas fa-sign-in-alt" style="margin-right:0.3rem;"></i>Login
      </a>
    <?php endif; ?>
  </div>
</header>



<!-- HOME -->
<section class="home" id="home">
  <div class="content">
    <h3>Beautiful <em>Flowers</em><br>for Every Moment</h3>
    <span>Handpicked blooms for every occasion — fresh, vibrant, and delivered with love.</span>
    <a href="#products" class="btn">Shop Now</a>
  </div>
</section>

<!-- ABOUT -->
<section class="about" id="about">
  <h1 class="heading"><span>About</span> Us</h1>
  <div class="row">
    <div class="image">
      <img src="images/About (2).jpg" alt="">
    </div>
    <div class="content">
      <h3>We are RIMS</h3>
      <p>RIMS is a leading company in providing high-quality products for all your needs. Our mission is to deliver excellence in every flower we offer.</p>
      <a href="#products" class="btn">Shop Now</a>
    </div>
  </div>
</section>

<!-- PRODUCTS -->
<section class="products" id="products">
  <h1 class="heading"><span>Our</span> Products</h1>

  <!-- Category Filter Buttons -->
  <div class="category-filters" id="categoryFilters">
    <button class="cat-btn active" onclick="filterByCategory('all', this)">
      🌿 All
    </button>
    <?php foreach ($categories as $cat): ?>
    <button class="cat-btn" onclick="filterByCategory(this.dataset.cat, this)" data-cat="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>">
      <?= !empty($cat['icon']) ? $cat['icon'] : '🌿' ?> <?= htmlspecialchars($cat['name']) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Search box inside products section -->
  <div style="text-align:center; margin: 1rem 0 1.5rem;">
    <input
      type="text"
      id="searchInput"
      placeholder="🔍 Search flowers..."
      oninput="applyFilters()"
      style="
        width: min(400px, 90%);
        padding: 0.65rem 1.3rem;
        border: 1.5px solid #f0d0e0;
        border-radius: 25px;
        font-size: 0.95rem;
        outline: none;
        font-family: inherit;
        color: #333;
        box-shadow: 0 2px 8px rgba(241,125,218,0.12);
      "
    >
  </div>

  <div class="box-container" id="productGrid">
  <?php foreach ($allProducts as $row): ?>
  <?php include "./product.php"; ?>
  <?php endforeach; ?>
  </div>

  <p id="noProductsMsg" style="display:none; text-align:center; color:#aaa; padding:2rem; font-size:1rem;">
    No flowers found in this category.
  </p>
</section>

<!-- CONTACT -->
<section class="contact" id="contact">
  <h1 class="heading"><span>Contact</span> Us</h1>
  <div style="display:flex; justify-content:center; align-items:center; padding: 2rem 1rem;">
    <div class="contact-form-wrapper" style="width:100%; max-width:600px;">

      <div id="contactLoginGuard" style="display:none;">
        <h3>Get in Touch</h3>
        <div style="
          background:#ffe0f0;
          border:1.5px solid #f17dda;
          border-radius:10px;
          padding:1.5rem;
          text-align:center;
          margin-top:1.2rem;
        ">
          <i class="fas fa-lock" style="font-size:2rem;color:#f17dda;margin-bottom:0.8rem;display:block;"></i>
          <p style="color:#555;margin-bottom:1rem;font-size:0.95rem;">
            You must be logged in to send us a message.
          </p>
          <a href="login.php" style="
            display:inline-block;
            background:#f17dda;
            color:#fff;
            padding:0.6rem 1.5rem;
            border-radius:8px;
            text-decoration:none;
            font-weight:600;
            font-size:0.9rem;
            transition:background 0.2s;
          " onmouseover="this.style.background='#aafdfd';this.style.color='#333'"
             onmouseout="this.style.background='#f17dda';this.style.color='#fff'">
            Login to Message Us
          </a>
        </div>
      </div>

      <!-- ── Shown when logged in ── -->
      <form id="contactForm" action="" onsubmit="handleContactSubmit(event)" style="display:none;">
        <h3>Get in Touch</h3>

        <!-- Success banner (hidden until submit) -->
        <div id="contactSuccess" style="
          display:none;
          background:#e0fdf4;
          border:1.5px solid #aafdfd;
          border-radius:10px;
          padding:1rem 1.2rem;
          margin-bottom:1rem;
          text-align:center;
          color:#065f46;
          font-size:0.95rem;
          font-weight:600;
        ">
          ✅ Message sent! We'll get back to you soon.
        </div>

        <!-- Error banner -->
        <div id="contactError" style="
          display:none;
          background:#ffe0f0;
          border:1.5px solid #f17dda;
          border-radius:10px;
          padding:1rem 1.2rem;
          margin-bottom:1rem;
          text-align:center;
          color:#c0392b;
          font-size:0.9rem;
        "></div>

        <input type="text"  id="contactName"    placeholder="Your Name"    class="box" required>
        <input type="email" id="contactEmail"   placeholder="Your Email"   class="box" required>
        <input type="tel"   id="contactPhone"   placeholder="Your Phone"   class="box">
        <textarea           id="contactMessage" placeholder="Your Message" class="box" required></textarea>
        <input type="submit" value="Send Message" class="btn" id="contactSubmitBtn">
      </form>

    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="icons-container">
  <div class="icons">
    <img src="images/quality.jpg" alt="">
    <div class="info">
      <h3>Quality Products</h3>
      <span>We offer a wide range of products that are carefully selected to meet our customers' needs.</span>
    </div>
  </div>

  <div class="icons">
    <img src="images/bus.jpg" alt="">
    <div class="info">
      <h3>Fast Delivery</h3>
      <span>We understand the importance of timely delivery, and we strive to ensure your orders reach you quickly.</span>
    </div>
  </div>

  <div class="icons">
    <img src="images/secure.jpg" alt="">
    <div class="info">
      <h3>Secure Payment</h3>
      <span>We prioritize the security of your information and offer secure payment options for worry-free shopping.</span>
    </div>
  </div>
</section>

<!-- Quantity Picker Modal -->
<div id="qtyModal" style="
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.45);
  z-index:9999;
  justify-content:center;
  align-items:center;
">
  <div style="
    background:#fff;
    border-radius:1.2rem;
    border-top:4px solid #f17dda;
    box-shadow:0 16px 48px rgba(241,125,218,0.25);
    padding:2.5rem;
    text-align:center;
    max-width:380px;
    width:90%;
    animation: popIn 0.25s ease;
  ">
    <img id="modalImage" src="" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:10px;margin-bottom:1rem;">
    <h3 id="modalName" style="margin:0 0 0.3rem;color:#333;font-size:1.2rem;"></h3>
    <p id="modalPrice" style="color:#f17dda;font-weight:700;font-size:1.1rem;margin:0 0 1.2rem;"></p>

    <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-bottom:1.5rem;">
      <button onclick="changeQty(-1)" style="width:36px;height:36px;border-radius:50%;border:2px solid #f17dda;background:#fff;color:#f17dda;font-size:1.2rem;font-weight:700;cursor:pointer;line-height:1;">−</button>
      <span id="modalQty" style="font-size:1.4rem;font-weight:700;color:#333;min-width:30px;">1</span>
      <button onclick="changeQty(1)" style="width:36px;height:36px;border-radius:50%;border:2px solid #f17dda;background:#fff;color:#f17dda;font-size:1.2rem;font-weight:700;cursor:pointer;line-height:1;">+</button>
    </div>
    <p id="modalStock" style="font-size:0.8rem;color:#aaa;margin-bottom:1.2rem;"></p>

    <div style="display:flex;gap:0.8rem;justify-content:center;">
      <button onclick="closeModal()" style="padding:0.7rem 1.5rem;border-radius:8px;border:1.5px solid #f5d0ef;background:#fff;color:#888;font-family:'Poppins',sans-serif;font-weight:600;cursor:pointer;">Cancel</button>
      <button onclick="confirmAddToCart()" style="padding:0.7rem 1.8rem;border-radius:8px;border:none;background:#f17dda;color:#fff;font-family:'Poppins',sans-serif;font-weight:600;cursor:pointer;font-size:0.95rem;">
        <i class="fas fa-shopping-cart" style="margin-right:0.4rem;"></i>Add to Cart
      </button>
    </div>
  </div>
</div>

<style>
@keyframes popIn { from { transform:scale(0.85); opacity:0; } to { transform:scale(1); opacity:1; } }

/* Category filter buttons */
.category-filters {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-bottom: 2rem;
  padding: 0 1rem;
}
.cat-btn {
  padding: 0.5rem 1.2rem;
  border: 2px solid #f17dda;
  border-radius: 30px;
  background: white;
  color: #d63384;
  font-family: 'Poppins', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s;
  white-space: nowrap;
}
.cat-btn:hover { background: #f17dda; color: white; transform: translateY(-2px); }
.cat-btn.active { background: #d63384; color: white; border-color: #d63384; box-shadow: 0 4px 14px rgba(214,51,132,0.3); }
</style>

<script>
  const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
  const CLIENT_NAME  = <?= json_encode($clientName) ?>;

  if (IS_LOGGED_IN) {
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("clientName", CLIENT_NAME);
  } else {
    localStorage.removeItem("isLoggedIn");
    localStorage.removeItem("clientName");
  }

  // ── Modal state ──
  var _modalProduct = {};
  var _modalQty = 1;
  var _modalStock = 1;

  function openCartModal(name, price, image, stock) {
    if (!IS_LOGGED_IN) { window.location.href = "login.php"; return; }

    _modalProduct = { name: name, price: price, image: image };
    _modalStock   = parseInt(stock) || 1;
    _modalQty     = 1;

    document.getElementById("modalImage").src   = "uploads/" + image;
    document.getElementById("modalName").textContent  = name;
    document.getElementById("modalPrice").textContent = "Rs " + parseFloat(price).toLocaleString();
    document.getElementById("modalQty").textContent   = 1;
    document.getElementById("modalStock").textContent = "Available stock: " + _modalStock;

    var modal = document.getElementById("qtyModal");
    modal.style.display = "flex";
  }

  function changeQty(delta) {
    _modalQty = Math.max(1, Math.min(_modalStock, _modalQty + delta));
    document.getElementById("modalQty").textContent = _modalQty;
  }

  function closeModal() {
    document.getElementById("qtyModal").style.display = "none";
  }

  function confirmAddToCart() {
    var url = "purchase.php?name=" + encodeURIComponent(_modalProduct.name) +
              "&price=" + encodeURIComponent(_modalProduct.price) +
              "&image=" + encodeURIComponent(_modalProduct.image) +
              "&qty=" + _modalQty;
    window.location.href = url;
  }

  // Close modal on backdrop click
  document.getElementById("qtyModal").addEventListener("click", function(e) {
    if (e.target === this) closeModal();
  });

  // Attach cart button listeners
  document.querySelectorAll(".cart-btn").forEach(function(btn) {
    btn.addEventListener("click", function(e) {
      e.preventDefault();
      openCartModal(
        this.dataset.name  || "",
        this.dataset.price || "",
        this.dataset.image || "",
        this.dataset.stock || "1"
      );
    });
  });

  document.querySelectorAll(".heart-btn").forEach(function(btn) {
    btn.addEventListener("click", function(e) {
      e.preventDefault();
      if (!IS_LOGGED_IN) window.location.href = "login.php";
    });
  });



  // Active category filter state
  var activeCat = 'all';

  function filterByCategory(cat, btn) {
    activeCat = cat;
    // Update active button
    document.querySelectorAll(".cat-btn").forEach(function(b) { b.classList.remove("active"); });
    btn.classList.add("active");
    // Also reset search
    var searchInput = document.getElementById("searchInput");
    if (searchInput) searchInput.value = "";
    applyFilters();
  }

  function filterProducts(term) {
    if (term !== undefined) document.getElementById("searchInput").value = term;
    applyFilters();
  }

  function applyFilters() {
    var q = (document.getElementById("searchInput") ? document.getElementById("searchInput").value : "").toLowerCase().trim();
    var anyVisible = false;
    document.querySelectorAll(".box-container .box").forEach(function(box) {
      var nameEl = box.querySelector("h3");
      var name = nameEl ? nameEl.textContent.toLowerCase() : "";
      var cat  = (box.dataset.category || "").toLowerCase();
      var matchSearch = q === "" || name.includes(q);
      var matchCat    = activeCat === "all" || cat === activeCat.toLowerCase();
      var show = matchSearch && matchCat;
      box.style.display = show ? "" : "none";
      if (show) anyVisible = true;
    });
    var msg = document.getElementById("noProductsMsg");
    if (msg) msg.style.display = anyVisible ? "none" : "block";
  }

  function handleUser() {
    if (IS_LOGGED_IN) {
      if (window.confirm("Logged in as " + CLIENT_NAME + ".\nClick OK to logout.")) {
        window.location.href = "logout.php";
      }
    } else {
      window.location.href = "login.php";
    }
  }

  window.addEventListener("DOMContentLoaded", function() {
    var icon = document.querySelector(".icons .fa-user");
    if (icon) {
      icon.style.color = IS_LOGGED_IN ? "#f17dda" : "";
      icon.title = IS_LOGGED_IN ? "Logged in as " + CLIENT_NAME : "Click to login";
    }
    var form  = document.getElementById("contactForm");
    var guard = document.getElementById("contactLoginGuard");
    if (IS_LOGGED_IN) {
      form.style.display  = "block";
      guard.style.display = "none";
      if (CLIENT_NAME) document.getElementById("contactName").value = CLIENT_NAME;
    } else {
      form.style.display  = "none";
      guard.style.display = "block";
    }
  });

  function goToCart() {
    if (IS_LOGGED_IN) {
      window.location.href = "purchase.php";
    } else {
      window.location.href = "login.php";
    }
  }

  function handleContactSubmit(e) {
    e.preventDefault();
    var name    = document.getElementById("contactName").value.trim();
    var email   = document.getElementById("contactEmail").value.trim();
    var phone   = document.getElementById("contactPhone").value.trim();
    var message = document.getElementById("contactMessage").value.trim();
    var err = document.getElementById("contactError");
    var ok  = document.getElementById("contactSuccess");
    var btn = document.getElementById("contactSubmitBtn");
    err.style.display = ok.style.display = "none";

    if (!name || !email || !message) {
      err.textContent = "❌ Please fill in your name, email and message.";
      err.style.display = "block"; return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      err.textContent = "❌ Please enter a valid email address.";
      err.style.display = "block"; return;
    }

    // Disable button while sending
    btn.value = "Sending..."; btn.disabled = true;

    var formData = new FormData();
    formData.append("name",    name);
    formData.append("email",   email);
    formData.append("phone",   phone);
    formData.append("message", message);

    fetch("send_message.php", { method: "POST", body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          ok.style.display = "block";
          btn.value = "Sent ✓";
          document.getElementById("contactName").value    = "";
          document.getElementById("contactEmail").value   = "";
          document.getElementById("contactPhone").value   = "";
          document.getElementById("contactMessage").value = "";
          setTimeout(function() {
            ok.style.display = "none";
            btn.value = "Send Message"; btn.disabled = false;
          }, 4000);
        } else {
          err.textContent = "❌ " + (data.error || "Failed to send. Please try again.");
          err.style.display = "block";
          btn.value = "Send Message"; btn.disabled = false;
        }
      })
      .catch(function() {
        err.textContent = "❌ Network error. Please try again.";
        err.style.display = "block";
        btn.value = "Send Message"; btn.disabled = false;
      });
  }
</script>

</body>
</html>