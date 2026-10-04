<?php
// Database credentials
$host = 'localhost';
$db   = 'schoolar_db';
$user = 'root';
$pass = '';

function getDbConnection(): PDO
{
    global $host, $db, $user, $pass;

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed. Check if MySQL is running in XAMPP.']);
        exit;
    }
}