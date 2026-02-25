<?php
$sql = "CREATE DATABASE  IF NOT EXISTS      BCA";
if ($conn->query($sql) === TRUE) {
    echo "Database BCA created successfully";
} else {
    echo "Error creating database: " . $conn->error;
}
