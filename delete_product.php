<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: view_products.php");
    exit();
}

$id = intval($_GET['id']);

// Get image name safely
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: view_products.php");
    exit();
}

// Delete image file if exists
if (!empty($row['image']) && file_exists("uploads/" . $row['image'])) {
    unlink("uploads/" . $row['image']);
}

// Delete product safely
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: view_products.php");
exit();
?>