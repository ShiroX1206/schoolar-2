<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'error' => 'Login required.']);
    exit;
}

$pdo = getDbConnection();

// Fetch user data
$stmt = $pdo->prepare('SELECT id, full_name, email, role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// If user no longer exists in DB, destroy session
if (!$user) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'error' => 'Session user not found.']);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'user_id'       => (int)$user['id'],
    'full_name'     => $user['full_name'],
    'email'         => $user['email'],
    'role'          => $user['role'],
]);