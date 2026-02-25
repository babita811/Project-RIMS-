<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: login.php?error=1");
    exit();
}

// Fetch user by username
$stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user   = $result->fetch_assoc();
    $dbRole = $user['role'];

    if (password_verify($password, $user['password'])) {

        // Set session variables
        $_SESSION['isClientLoggedIn'] = true;
        $_SESSION['clientId']         = $user['id'];
        $_SESSION['clientName']       = $user['name'];
        $_SESSION['clientUsername']   = $user['username'];
        $_SESSION['clientRole']       = $dbRole;

        // Keep legacy admin session key for admin pages
        if ($dbRole === 'admin') {
            $_SESSION['admin'] = $user['username'];
        }

        // Auto-redirect based on role stored in DB
        $redirect = ($dbRole === 'admin') ? 'admin_dashboard.php' : 'client_landing.php';
        header("Location: $redirect");
        exit();

    } else {
        header("Location: login.php?error=1");
        exit();
    }
} else {
    header("Location: login.php?error=1");
    exit();
}
?>