<?php
session_start();
if (isset($_SESSION['isClientLoggedIn']) && $_SESSION['isClientLoggedIn'] === true) {
    header("Location: " . ($_SESSION['clientRole'] === 'admin' ? 'admin_dashboard.php' : 'index.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – RIMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background:
        radial-gradient(ellipse at 70% 30%, rgba(232,69,122,0.15) 0%, transparent 60%),
        radial-gradient(ellipse at 20% 80%, rgba(241,125,218,0.12) 0%, transparent 50%),
        linear-gradient(150deg, #fff9f5 0%, #fce8f2 60%, #fff0f7 100%);
      padding: 2rem;
    }
    .card {
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: 0 20px 60px rgba(232,69,122,0.18);
      padding: 2.5rem;
      width: 100%;
      max-width: 420px;
      animation: slideUp 0.5s ease forwards;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .logo {
      font-family: 'Playfair Display', serif;
      font-size: 2.4rem;
      font-weight: 900;
      color: #1a0a12;
      text-align: center;
      margin-bottom: 0.2rem;
    }
    .logo span { color: #e8457a; }
    .subtitle {
      text-align: center;
      font-size: 0.85rem;
      color: #9b7a8a;
      margin-bottom: 2rem;
    }
    .msg {
      border-radius: 8px;
      padding: 10px 14px;
      margin-bottom: 1rem;
      font-size: 0.85rem;
      text-align: center;
      display: none;
    }
    .msg.success { background: #e0fdf4; color: #065f46; border: 1px solid #aafdfd; }
    .msg.error   { background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; }
    .input-group { margin-bottom: 1.1rem; }
    .input-group label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: #3a1a28;
      margin-bottom: 0.4rem;
    }
    .input-group input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1.5px solid #f0d0e0;
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.95rem;
      color: #3a1a28;
      background: #fff9f5;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-group input:focus {
      border-color: #e8457a;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(232,69,122,0.1);
    }
    .password-wrap { position: relative; }
    .password-wrap input { padding-right: 2.8rem; }
    .eye-toggle {
      position: absolute;
      right: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #c4899e;
      font-size: 1rem;
      padding: 0;
      transition: color 0.2s;
    }
    .eye-toggle:hover { color: #e8457a; }
    .btn-login {
      width: 100%;
      padding: 0.85rem;
      background: #e8457a;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 8px 24px rgba(232,69,122,0.3);
      margin-top: 0.5rem;
    }
    .btn-login:hover {
      background: #c2185b;
      transform: translateY(-2px);
    }
    .divider {
      text-align: center;
      margin: 1.2rem 0;
      font-size: 0.82rem;
      color: #9b7a8a;
      position: relative;
    }
    .divider::before, .divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 38%;
      height: 1px;
      background: #f0d0e0;
    }
    .divider::before { left: 0; }
    .divider::after  { right: 0; }
    .bottom-links { text-align: center; font-size: 0.85rem; color: #9b7a8a; }
    .bottom-links a { color: #e8457a; font-weight: 600; text-decoration: none; }
    .bottom-links a:hover { text-decoration: underline; }
    .back-home { text-align: center; margin-top: 1.2rem; font-size: 0.82rem; }
    .back-home a { color: #9b7a8a; text-decoration: none; font-weight: 500; }
    .back-home a:hover { color: #e8457a; }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">RIMS<span>.</span></div>
  <p class="subtitle">Retail Inventory Management System</p>
  <div class="msg success" id="success-msg">✅ Account created! Please login.</div>
  <div class="msg error" id="error-msg">❌ Invalid username or password.</div>
  <form action="login_function.php" method="POST">
    <div class="input-group">
      <label><i class="fas fa-user" style="color:#e8457a; margin-right:0.4rem;"></i>Username</label>
      <input type="text" name="username" placeholder="Enter your username" required autofocus>
    </div>
    <div class="input-group">
      <label><i class="fas fa-lock" style="color:#e8457a; margin-right:0.4rem;"></i>Password</label>
      <div class="password-wrap">
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
        <button type="button" class="eye-toggle" onclick="togglePassword()">
          <i class="fas fa-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn-login">
      <i class="fas fa-sign-in-alt" style="margin-right:0.4rem;"></i> Login
    </button>
  </form>
  <div class="divider">New here?</div>
  <div class="bottom-links">
    <a href="register.php">Create an Account</a>
  </div>
  <div class="back-home">
    <a href="index.php"><i class="fas fa-arrow-left" style="margin-right:0.3rem;"></i>Back to Homepage</a>
  </div>
</div>
<script>
  var params = new URLSearchParams(window.location.search);
  if (params.get('error') === '1') document.getElementById('error-msg').style.display = 'block';
  if (params.get('success') === '1') document.getElementById('success-msg').style.display = 'block';
  function togglePassword() {
    var input = document.getElementById('password');
    var icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  }
</script>
</body>
</html>