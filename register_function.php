<?php
session_start();
require_once 'db.php';

if (!isset($_POST['name'], $_POST['email'], $_POST['username'], $_POST['password'], $_POST['confirm_password'])) {
    header("Location: register.php");
    exit();
}

$name     = trim($_POST['name']);
$email    = trim($_POST['email']);
$username = trim($_POST['username']);
$password = $_POST['password'];
$confirm  = $_POST['confirm_password'];

if (strlen($password) < 6) {
    header("Location: register.php?error=short");
    exit();
}

if ($password !== $confirm) {
    header("Location: register.php?error=mismatch");
    exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: register.php?error=exists");
    exit();
}
$stmt->close();

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: register.php?error=email");
    exit();
}
$stmt->close();

$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt   = $conn->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $username, $hashed);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: login.php?success=1");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: register.php?error=failed");
    exit();
}
?>