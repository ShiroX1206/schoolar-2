<?php
// This file connects SCHOOlar to the MySQL database.

$host = "localhost";
$database = "schoolar_db";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(array("error" => "Database connection failed. Make sure MySQL is running in XAMPP."));
    exit;
}

$conn->set_charset("utf8mb4");
?>
