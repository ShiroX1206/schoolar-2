<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/require_admin.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Helper to convert empty inputs to NULL for database saving
function numOrNull($value) {
    return ($value === '' || $value === null) ? null : $value;
}

// Helper to validate inputs before saving
function validateInput(array $data): ?string {
    if (empty($data['name'])) {
        return 'Scholarship name is required.';
    }

    $allowedStatuses = ['available', 'not available'];
    if (isset($data['status']) && !in_array($data['status'], $allowedStatuses, true)) {
        return 'Invalid scholarship status.';
    }

    if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        return 'Invalid contact email.';
    }

    $c = $data['criteria'] ?? [];
    
    // Check for non-numeric input in numeric criteria fields
    $numericFields = ['min_gwa', 'max_gwa', 'max_annual_income', 'min_age', 'max_age'];
    foreach ($numericFields as $field) {
        if (!empty($c[$field]) && !is_numeric($c[$field])) {
            return "Invalid $field.";
        }
    }

    // Min / Max range validations
    if (!empty($c['min_gwa']) && !empty($c['max_gwa']) && (float)$c['min_gwa'] > (float)$c['max_gwa']) {
        return 'Minimum GWA cannot be greater than maximum GWA.';
    }
    if (!empty($c['min_age']) && !empty($c['max_age']) && (int)$c['min_age'] > (int)$c['max_age']) {
        return 'Minimum age cannot be greater than maximum age.';
    }
    if (isset($c['max_annual_income']) && $c['max_annual_income'] !== '' && (float)$c['max_annual_income'] < 0) {
        return 'Maximum annual income cannot be negative.';
    }

    return null;
}

// Helper to replace or insert eligibility criteria for a scholarship
function saveCriteria(PDO $pdo, int $scholarshipId, array $c): void {
    $stmt = $pdo->prepare('DELETE FROM eligibility_criteria WHERE scholarship_id = ?');
    $stmt->execute([$scholarshipId]);

    $stmt = $pdo->prepare(
        'INSERT INTO eligibility_criteria
            (scholarship_id, min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $scholarshipId,
        numOrNull($c['min_gwa'] ?? null),
        numOrNull($c['max_gwa'] ?? null),
        numOrNull($c['max_annual_income'] ?? null),
        numOrNull($c['min_age'] ?? null),
        numOrNull($c['max_age'] ?? null),
        json_encode($c['year_levels'] ?? []),
        json_encode($c['course_scope'] ?? []),
        !empty($c['residency_required']) ? 1 : 0,
    ]);
}


// ==========================================
// 1. GET: Fetch all scholarships with criteria
// ==========================================
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT * FROM scholarships ORDER BY updated_at DESC');
    $scholarships = $stmt->fetchAll();

    foreach ($scholarships as &$s) {
        $critStmt = $pdo->prepare('SELECT * FROM eligibility_criteria WHERE scholarship_id = ?');
        $critStmt->execute([$s['id']]);
        $row = $critStmt->fetch();

        if ($row) {
            $s['criteria'] = [
                'min_gwa'            => $row['min_gwa'] !== null ? (float)$row['min_gwa'] : null,
                'max_gwa'            => $row['max_gwa'] !== null ? (float)$row['max_gwa'] : null,
                'max_annual_income'  => $row['max_annual_income'] !== null ? (float)$row['max_annual_income'] : null,
                'min_age'            => $row['min_age'] !== null ? (int)$row['min_age'] : null,
                'max_age'            => $row['max_age'] !== null ? (int)$row['max_age'] : null,
                'year_levels'        => $row['year_levels'] ? json_decode($row['year_levels']) : [],
                'course_scope'       => $row['course_scope'] ? json_decode($row['course_scope']) : [],
                'residency_required' => (bool)$row['residency_required'],
            ];
        } else {
            $s['criteria'] = null;
        }
    }

    echo json_encode($scholarships);
    exit;
}


// ==========================================
// 2. POST & PUT: Create or Update Scholarship
// ==========================================
if ($method === 'POST' || $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($method === 'PUT' && !$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing scholarship id.']);
        exit;
    }

    // Run input validation
    $error = validateInput($data);
    if ($error) {
        http_response_code(400);
        echo json_encode(['error' => $error]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        if ($method === 'POST') {
            $stmt = $pdo->prepare(
                'INSERT INTO scholarships
                    (name, provider_name, benefits, requirements, deadline, status, municipality_code, barangay_code, municipality_name, barangay_name, contact_email, contact_phone, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['name'],
                $data['provider_name'] ?? null,
                $data['benefits'] ?? null,
                $data['requirements'] ?? null,
                !empty($data['deadline']) ? $data['deadline'] : null,
                $data['status'] ?? 'available',
                $data['municipality_code'] ?? null,
                $data['barangay_code'] ?? null,
                $data['municipality_name'] ?? null,
                $data['barangay_name'] ?? null,
                $data['contact_email'] ?? null,
                $data['contact_phone'] ?? null,
                $_SESSION['user_id'],
            ]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE scholarships SET
                    name = ?, provider_name = ?, benefits = ?, requirements = ?, deadline = ?, status = ?,
                    municipality_code = ?, barangay_code = ?, municipality_name = ?, barangay_name = ?,
                    contact_email = ?, contact_phone = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $data['name'],
                $data['provider_name'] ?? null,
                $data['benefits'] ?? null,
                $data['requirements'] ?? null,
                !empty($data['deadline']) ? $data['deadline'] : null,
                $data['status'] ?? 'available',
                $data['municipality_code'] ?? null,
                $data['barangay_code'] ?? null,
                $data['municipality_name'] ?? null,
                $data['barangay_name'] ?? null,
                $data['contact_email'] ?? null,
                $data['contact_phone'] ?? null,
                $id,
            ]);
        }

        saveCriteria($pdo, $id, $data['criteria'] ?? []);

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Could not save the scholarship.']);
    }
    exit;
}


// ==========================================
// 3. DELETE: Remove a scholarship
// ==========================================
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing scholarship id.']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM scholarships WHERE id = ?');
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
    exit;
}

// Default fallback for unsupported methods
http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);