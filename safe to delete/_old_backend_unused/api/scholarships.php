<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();

// Splits newline-separated text into a simple clean array
function makeList(?string $text): array {
    if (empty(trim($text ?? ''))) {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode("\n", $text))));
}

// Fetches criteria details for a scholarship
function getCriteria(PDO $pdo, int $scholarshipId): array {
    $stmt = $pdo->prepare('SELECT min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required FROM eligibility_criteria WHERE scholarship_id = ?');
    $stmt->execute([$scholarshipId]);
    $criteria = $stmt->fetch();

    if (!$criteria) {
        return [];
    }

    $criteria['year_levels'] = json_decode($criteria['year_levels'] ?? '[]', true);
    $criteria['course_scope'] = json_decode($criteria['course_scope'] ?? '[]', true);
    $criteria['residency_required'] = (bool)$criteria['residency_required'];

    return $criteria;
}

// Formats a raw database row into a clean JSON structure
function formatScholarship(PDO $pdo, array $row): array {
    // Determine location string
    $location = 'Nationwide';
    if (!empty($row['barangay_name']) && !empty($row['municipality_name'])) {
        $location = $row['barangay_name'] . ', ' . $row['municipality_name'];
    } elseif (!empty($row['municipality_name'])) {
        $location = $row['municipality_name'];
    }

    // Build contact array
    $contact = [];
    if (!empty($row['contact_email'])) {
        $contact[] = ['label' => 'Email', 'value' => $row['contact_email']];
    }
    if (!empty($row['contact_phone'])) {
        $contact[] = ['label' => 'Phone', 'value' => $row['contact_phone']];
    }

    return [
        'id'                => (int)$row['id'],
        'name'              => $row['name'],
        'provider'          => $row['provider_name'],
        'provider_name'     => $row['provider_name'],
        'benefits'          => makeList($row['benefits']),
        'requirements'      => makeList($row['requirements']),
        'deadline'          => $row['deadline'],
        'status'            => $row['status'],
        'municipality_code' => $row['municipality_code'],
        'barangay_code'     => $row['barangay_code'],
        'municipality_name' => $row['municipality_name'],
        'barangay_name'     => $row['barangay_name'],
        'location'          => $location,
        'contact'           => $contact,
        'criteria'          => getCriteria($pdo, (int)$row['id'])
    ];
}

// --- Dynamic Query Filtering ---
$id           = (int)($_GET['id'] ?? 0);
$search       = trim($_GET['search'] ?? '');
$municipality = trim($_GET['municipality'] ?? '');
$barangay     = trim($_GET['barangay'] ?? '');

$sql = 'SELECT * FROM scholarships WHERE status = ?';
$params = ['available'];

if ($id > 0) {
    $sql .= ' AND id = ?';
    $params[] = $id;
}
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR provider_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($municipality !== '') {
    $sql .= ' AND municipality_name = ?';
    $params[] = $municipality;
}
if ($barangay !== '') {
    $sql .= ' AND barangay_name = ?';
    $params[] = $barangay;
}

$sql .= ' ORDER BY deadline IS NULL, deadline ASC, created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Format all rows
$result = [];
foreach ($rows as $row) {
    $result[] = formatScholarship($pdo, $row);
}

// Return a single object if requested by ID, or an array if general list
if ($id > 0) {
    if (empty($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'Scholarship not found.']);
        exit;
    }
    echo json_encode($result[0]);
    exit;
}

echo json_encode($result);