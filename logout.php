<?php
session_start();

$isAdmin = isset($_SESSION['admin']);

session_destroy();

if ($isAdmin) {
    header("Location: login.php");
} else {
    header("Location: index.php");
}
exit();
?>