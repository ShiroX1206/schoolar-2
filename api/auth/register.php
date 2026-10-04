<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    $data = array();
}

$name = trim(isset($data["name"]) ? $data["name"] : "");
$email = trim(isset($data["email"]) ? $data["email"] : "");
$password = isset($data["password"]) ? $data["password"] : "";
$municipality = trim(isset($data["municipality_code"]) ? $data["municipality_code"] : "");
$barangay = trim(isset($data["barangay_code"]) ? $data["barangay_code"] : "");
$birthDate = isset($data["birth_date"]) ? $data["birth_date"] : "";
$schoolId = trim(isset($data["school_id"]) ? $data["school_id"] : "");
$courseId = trim(isset($data["course_id"]) ? $data["course_id"] : "");
$yearLevel = trim(isset($data["year_level"]) ? $data["year_level"] : "");
$gwa = isset($data["gwa"]) ? $data["gwa"] : "";
$income = isset($data["annual_family_income"]) ? $data["annual_family_income"] : "";

if ($name === "" || $email === "" || $password === "" || $municipality === "" || $barangay === "" || $birthDate === "" || $schoolId === "" || $courseId === "" || $yearLevel === "" || $gwa === "" || $income === "") {
    sendResponse(array("error" => "Please complete all required fields."), 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(array("error" => "Please enter a valid email address."), 400);
}

if (strlen($password) < 8) {
    sendResponse(array("error" => "Password must be at least 8 characters."), 400);
}

if (!is_numeric($gwa) || (float)$gwa < 1 || (float)$gwa > 5) {
    sendResponse(array("error" => "GWA must be between 1.00 and 5.00."), 400);
}

if (!is_numeric($income) || (float)$income < 0) {
    sendResponse(array("error" => "Annual family income cannot be negative."), 400);
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$existingUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingUser) {
    sendResponse(array("error" => "An account with this email already exists."), 409);
}

$stmt = $conn->prepare("SELECT id FROM schools WHERE id = ? LIMIT 1");
$stmt->bind_param("s", $schoolId);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$school) {
    sendResponse(array("error" => "Selected school does not exist."), 400);
}

$stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND school_id = ? LIMIT 1");
$stmt->bind_param("ss", $courseId, $schoolId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    sendResponse(array("error" => "Selected course does not belong to the selected school."), 400);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$role = "user";
$gwaValue = (float)$gwa;
$incomeValue = (float)$income;

$stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, birth_date, municipality_code, barangay_code, school_id, course_id, year_level, gwa, annual_family_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssdd", $name, $email, $passwordHash, $role, $birthDate, $municipality, $barangay, $schoolId, $courseId, $yearLevel, $gwaValue, $incomeValue);

if (!$stmt->execute()) {
    $stmt->close();
    sendResponse(array("error" => "Could not create the account."), 500);
}

$userId = $stmt->insert_id;
$stmt->close();

session_regenerate_id(true);
$_SESSION["user_id"] = (int)$userId;
$_SESSION["role"] = "user";

sendResponse(array(
    "success" => true,
    "user_id" => (int)$userId,
    "full_name" => $name,
    "role" => "user"
));
?>
