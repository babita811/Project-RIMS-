<?php
session_start();

// Guard: must be logged in via session
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

$clientName  = htmlspecialchars($_SESSION['clientName']  ?? '');
$clientEmail = htmlspecialchars($_SESSION['clientEmail'] ?? '');  // ← added
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loading – RIMS</title>
</head>
<body>
  <script>
    // Set localStorage so all HTML pages can read login state
    localStorage.setItem("isLoggedIn",   "true");
    localStorage.setItem("clientName",   <?php echo json_encode($clientName); ?>);
    localStorage.setItem("clientEmail",  <?php echo json_encode($clientEmail); ?>);  // ← added
    // Now go to the homepage
    window.location.href = "index.php";
  </script>
  <p style="font-family:sans-serif;text-align:center;margin-top:3rem;">Redirecting...</p>
</body>
</html>