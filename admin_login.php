<?php
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === "admin" && $password === "admin123") {
        $_SESSION['admin'] = $username;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flower Shop Login</title>
    <meta charset="UTF-8">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            background: url('https://images.unsplash.com/photo-1490750967868-88aa4486c946') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeBackground 2s ease-in-out;
            position: relative;
        }

        /* Background Fade Animation */
        @keyframes fadeBackground {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Dark overlay */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
        }

        /* Login Box */
        .login-box {
            position: relative;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            width: 320px;
            color: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            animation: slideFade 1.5s ease forwards;
            transform: translateY(40px);
            opacity: 0;
        }

        @keyframes slideFade {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .login-box h2 {
            margin-bottom: 20px;
        }

        input {
            width: 90%;
            padding: 10px 40px 10px 10px;
            margin: 10px 0;
            border-radius: 25px;
            border: none;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            transform: scale(1.05);
        }

        button {
            width: 100%;
            padding: 10px;
            border-radius: 25px;
            border: none;
            background: #ff4da6;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #e60073;
            transform: scale(1.05);
        }

        .error {
            margin-top: 10px;
            color: #ffcccc;
        }

        /* Password eye icon */
        .password-wrapper {
            position: relative;
        }

        .eye-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #ff4da6;
        }
    </style>
</head>

<body>

<div class="login-box">
    <h2>🌸 RIMS Admin</h2>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>

        <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="Password" required>
            <span id="togglePassword" class="eye-icon">&#128065;</span>
        </div>

        <button type="submit" name="login">Login</button>
        
        <!-- Divider -->
        <div class="divider">
          <span>New here?</span>
        </div>

        <!-- Register Now button -->
        <div class="register-btn">
          <a href="register.php">Register Now</a>
        </div>

        <p style="text-align:center; margin-top:16px; font-size:13px;">
          <a href="index.php" style="color:#f17dda; text-decoration:none; font-weight:bold;">
            ← Back to Homepage
    </form>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', () => {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        // Optional: change icon style when toggled
        togglePassword.style.color = type === 'password' ? '#ff4da6' : '#e60073';
    });
</script>



</body>
</html>