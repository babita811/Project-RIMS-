<?php
// register.php — no duplicate HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – RIMS</title>
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
      background: rgba(255,255,255,0.97);
      border-radius: 1.5rem;
      box-shadow: 0 20px 60px rgba(232,69,122,0.18);
      padding: 2.5rem 2.5rem 2rem;
      width: 100%;
      max-width: 440px;
      animation: slideUp 0.5s ease forwards;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .logo {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 900;
      color: #1a0a12;
      text-align: center;
      margin-bottom: 0.2rem;
    }
    .logo span { color: #e8457a; }
    .subtitle { text-align: center; font-size: 0.85rem; color: #9b7a8a; margin-bottom: 1.8rem; }
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
    .input-group { margin-bottom: 1rem; }
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
    /* Strength bar */
    .strength-bar-wrap { margin-top: 6px; height: 5px; border-radius: 5px; background: #eee; overflow: hidden; }
    .strength-bar { height: 100%; width: 0; border-radius: 5px; transition: 0.3s; }
    .strength-text { font-size: 11px; color: #aaa; margin-top: 3px; display: block; }
    .match-text { font-size: 11px; margin-top: 3px; display: block; }
    .password-wrap { position: relative; }
    .password-wrap input { padding-right: 2.8rem !important; }
    .eye-toggle { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #c4899e; font-size: 1rem; padding: 0; transition: color 0.2s; }
    .eye-toggle:hover { color: #e8457a; }
    .btn-register {
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
    .btn-register:hover {
      background: #c2185b;
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(232,69,122,0.4);
    }
    .bottom-links { text-align: center; font-size: 0.85rem; color: #9b7a8a; margin-top: 1.2rem; }
    .bottom-links a { color: #e8457a; font-weight: 600; text-decoration: none; }
    .bottom-links a:hover { text-decoration: underline; }
    .back-home { text-align: center; margin-top: 0.8rem; font-size: 0.82rem; }
    .back-home a { color: #9b7a8a; text-decoration: none; font-weight: 500; }
    .back-home a:hover { color: #e8457a; }
  </style>
</head>
<body>

<div class="card">

  <div class="logo">RIMS<span>.</span></div>
  <p class="subtitle">Create a new account</p>

  <div class="msg success" id="success-msg">✅ Account created! Redirecting to login...</div>
  <div class="msg error"   id="error-msg"></div>

  <form action="register_function.php" method="POST" id="registerForm">

    <div class="input-group">
      <label>Full Name</label>
      <input type="text" name="name" placeholder="Enter your full name" required>
    </div>

    <div class="input-group">
      <label>Email</label>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>

    <div class="input-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Choose a username" required>
    </div>

    <div class="input-group">
      <label>Password</label>
      <div class="password-wrap">
        <input type="password" id="password" name="password" placeholder="Create a password" required>
        <button type="button" class="eye-toggle" onclick="togglePw('password','eyeIcon1')" title="Show/hide">
          <i class="fas fa-eye" id="eyeIcon1"></i>
        </button>
      </div>
      <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
      <small class="strength-text" id="strengthText"></small>
    </div>

    <div class="input-group">
      <label>Confirm Password</label>
      <div class="password-wrap">
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
        <button type="button" class="eye-toggle" onclick="togglePw('confirm_password','eyeIcon2')" title="Show/hide">
          <i class="fas fa-eye" id="eyeIcon2"></i>
        </button>
      </div>
      <small class="match-text" id="matchText"></small>
    </div>

    <button type="submit" class="btn-register">
      <i class="fas fa-user-plus" style="margin-right:0.4rem;"></i> Create Account
    </button>

  </form>

  <div class="bottom-links">
    Already have an account? <a href="login.php">Login here</a>
  </div>

  <div class="back-home">
    <a href="index.php"><i class="fas fa-arrow-left" style="margin-right:0.3rem;"></i>Back to Homepage</a>
  </div>

</div>

<script>
  var params = new URLSearchParams(window.location.search);
  var errorMsg = document.getElementById('error-msg');
  var errorMap = {
    "exists":   "❌ Username already taken. Please choose another.",
    "email":    "❌ Email already registered. Try logging in.",
    "mismatch": "❌ Passwords do not match.",
    "short":    "❌ Password must be at least 6 characters.",
    "failed":   "❌ Registration failed. Please try again."
  };

  if (params.get('success') === '1') {
    document.getElementById('success-msg').style.display = 'block';
    setTimeout(function() { window.location.href = 'login.php'; }, 2000);
  }

  var errorCode = params.get('error');
  if (errorCode && errorMap[errorCode]) {
    errorMsg.textContent = errorMap[errorCode];
    errorMsg.style.display = 'block';
  }

  // Password strength
  var passwordInput  = document.getElementById('password');
  var strengthBar    = document.getElementById('strengthBar');
  var strengthText   = document.getElementById('strengthText');
  var confirmInput   = document.getElementById('confirm_password');
  var matchText      = document.getElementById('matchText');

  passwordInput.addEventListener('input', function() {
    var val = passwordInput.value;
    var strength = 0;
    if (val.length >= 6)           strength++;
    if (val.length >= 10)          strength++;
    if (/[A-Z]/.test(val))         strength++;
    if (/[0-9]/.test(val))         strength++;
    if (/[^A-Za-z0-9]/.test(val))  strength++;
    var levels = [
      { width:'0%',   color:'#eee',    label:'' },
      { width:'25%',  color:'#e74c3c', label:'Weak' },
      { width:'50%',  color:'#f39c12', label:'Fair' },
      { width:'75%',  color:'#aafdfd', label:'Good' },
      { width:'100%', color:'#e8457a', label:'Strong' }
    ];
    var l = Math.min(strength, 4);
    strengthBar.style.width      = levels[l].width;
    strengthBar.style.background = levels[l].color;
    strengthText.textContent     = levels[l].label;
    strengthText.style.color     = levels[l].color;
  });

  confirmInput.addEventListener('input', function() {
    if (!confirmInput.value) { matchText.textContent = ''; return; }
    if (confirmInput.value === passwordInput.value) {
      matchText.textContent  = '✅ Passwords match';
      matchText.style.color  = '#27ae60';
    } else {
      matchText.textContent  = '❌ Passwords do not match';
      matchText.style.color  = '#e74c3c';
    }
  });

  document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (passwordInput.value.length < 6) {
      e.preventDefault();
      errorMsg.textContent = '❌ Password must be at least 6 characters.';
      errorMsg.style.display = 'block';
      return;
    }
    if (passwordInput.value !== confirmInput.value) {
      e.preventDefault();
      errorMsg.textContent = '❌ Passwords do not match.';
      errorMsg.style.display = 'block';
    }
  });
  function togglePw(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
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