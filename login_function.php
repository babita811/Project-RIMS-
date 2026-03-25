<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['username'], $_POST['password'])) {
        header("Location: login.php");
        exit();
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT id, username, password, name, email, role 
         FROM users 
         WHERE username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // Set session variables — email added so client pages can use it
            $_SESSION['isClientLoggedIn'] = true;
            $_SESSION['clientId']         = $user['id'];
            $_SESSION['clientName']       = $user['name'];
            $_SESSION['clientEmail']      = $user['email'];   
            $_SESSION['clientUsername']   = $user['username'];
            $_SESSION['clientRole']       = $user['role'];

            $redirect = ($user['role'] === 'admin')
                ? 'admin_dashboard.php'
                : 'client_landing.php';

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

} else {
    header("Location: login.php");
    exit();
}
?>