<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$type = $_GET['type'] ?? '';

// Fetch list of schools
if ($type === 'schools') {
    $stmt = $pdo->query('SELECT id, name, acronym FROM schools ORDER BY name');
    echo json_encode($stmt->fetchAll());
    exit;
}

// Fetch list of courses for a specific school
if ($type === 'courses') {
    $schoolId = $_GET['school_id'] ?? '';

    if (empty($schoolId)) {
        http_response_code(400);
        echo json_encode(['error' => 'school_id is required.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, school_id, name, code FROM courses WHERE school_id = ? ORDER BY name');
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// Fallback for unknown type
http_response_code(400);
echo json_encode(['error' => 'Unknown reference type.']);