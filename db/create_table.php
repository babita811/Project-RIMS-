<?php

$create = "CREATE TABLE IF NOT EXISTS Students (
id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
fname VARCHAR(50) NOT NULL UNIQUE, 
lname VARCHAR(50) NOT NULL,
email VARCHAR(50) NOT NULL UNIQUE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->select_db("BCA");
    
// if ($conn->query($create) ) {
//     echo "table student created successfully";
// } else{
//     echo "Error creating table: " . $conn->error;
// }

// $create1 = "CREATE TABLE IF NOT EXISTS class (
// id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
// cname VARCHAR(50) NOT NULL UNIQUE, 
// room_no VARCHAR(50) NULL,
// created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// )";

// //$conn select_db("BCA")
// if ($conn->query($create1) ) {
//     echo "table student created successfully";
// } else{
//     echo "Error creating table: " . $conn->error;
// }

// $alter = "ALTER TABLE students ADD class_id INT(11) FOREIGN KEY REFERENCES class
// (id)";

// if ($conn->query($alter) ) {
//     echo "table student updated successfully";
// } else{
//     echo "Error updating table: " . $conn->error;
// }