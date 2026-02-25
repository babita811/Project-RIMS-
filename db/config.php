
<?php
$server = "localhost";
$username = "root";
$password = "";

$conn = new mysqli($server, $username, $password, "BCA");
if ($conn ->connect_error) {
  var_dump($conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}