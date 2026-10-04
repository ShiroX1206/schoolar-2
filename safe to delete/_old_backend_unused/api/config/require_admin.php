<?php
session_start();

// Check if user is NOT logged in as an admin
if (($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admin login required.']);
    exit;
}