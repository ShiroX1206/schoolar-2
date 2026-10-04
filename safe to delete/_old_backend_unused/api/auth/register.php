<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true) ?? [];

// 1. Check required fields
$required = ['name', 'email', 'password', 'municipality_code', 'barangay_code', 'birth_date', 'school_id', 'course_id', 'year_level', 'gwa', 'annual_family_income'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

// Format variables
$name     = trim($data['name']);
$email    = trim($data['email']);
$password = $data['password'];
$gwa      = (float)$data['gwa'];
$income   = (float)$data['annual_family_income'];

// 2. Input Validation
if (strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter your full name.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address.']);
    exit;
}

if ($gwa < 1.00 || $gwa > 5.00) {
    http_response_code(400);
    echo json_encode(['error' => 'GWA must be between 1.00 and 5.00.']);
    exit;
}

if ($income < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Annual family income cannot be negative.']);
    exit;
}

// 3. Database Checks
$pdo = getDbConnection();

// Check unique email
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'An account with this email already exists.']);
    exit;
}

// Check if school exists
$stmt = $pdo->prepare('SELECT id FROM schools WHERE id = ?');
$stmt->execute([$data['school_id']]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Selected school does not exist.']);
    exit;
}

// Check if course belongs to the selected school
$stmt = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND school_id = ?');
$stmt->execute([$data['course_id'], $data['school_id']]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Selected course does not belong to the selected school.']);
    exit;
}

// 4. Save User & Start Session
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, birth_date, municipality_code, barangay_code, school_id, course_id, year_level, gwa, annual_family_income, role) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "user")'
    );

    $stmt->execute([
        $name,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $data['birth_date'],
        $data['municipality_code'],
        $data['barangay_code'],
        $data['school_id'],
        $data['course_id'],
        $data['year_level'],
        $gwa,
        $income
    ]);

    $userId = (int)$pdo->lastInsertId();
    $pdo->commit();

    // Auto log in after registration
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['role']    = 'user';

    echo json_encode([
        'success'   => true,
        'user_id'   => $userId,
        'full_name' => $name,
        'role'      => 'user'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Could not create the account. Please try again.']);
}