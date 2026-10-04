<?php
session_start();

// Check if user is NOT logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Login required.']);
    exit;
}