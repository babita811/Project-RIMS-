<?php
session_start();

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

$clientName = htmlspecialchars($_SESSION['clientName'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loading – RIMS</title>
</head>
<body>
  <script>
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("clientName", <?php echo json_encode($clientName); ?>);
    window.location.href = "index.php";
  </script>
  <p style="font-family:sans-serif;text-align:center;margin-top:3rem;">Redirecting...</p>
</body>
</html>