<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
require_once __DIR__ . "/../config/auth.php";

requireUser();
$userId = (int)$_SESSION["user_id"];
$method = $_SERVER["REQUEST_METHOD"];

function getProfile($conn, $userId)
{
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email, u.birth_date, u.municipality_code, u.barangay_code, u.school_id, u.course_id, u.year_level, u.gwa, u.annual_family_income, s.name AS school_name, c.name AS course_name FROM users u LEFT JOIN schools s ON s.id = u.school_id LEFT JOIN courses c ON c.id = u.course_id WHERE u.id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$profile) {
        return null;
    }

    $age = null;

    if (!empty($profile["birth_date"])) {
        $birthDate = new DateTime($profile["birth_date"]);
        $today = new DateTime();
        $age = $birthDate->diff($today)->y;
    }

    return array(
        "id" => (int)$profile["id"],
        "name" => $profile["full_name"],
        "email" => $profile["email"],
        "birth_date" => $profile["birth_date"],
        "age" => $age,
        "municipality" => $profile["municipality_code"],
        "barangay" => $profile["barangay_code"],
        "school" => $profile["school_id"],
        "course" => $profile["course_id"],
        "year" => $profile["year_level"],
        "gwa" => $profile["gwa"],
        "income" => $profile["annual_family_income"],
        "school_name" => $profile["school_name"],
        "course_name" => $profile["course_name"]
    );
}

if ($method === "GET") {
    $profile = getProfile($conn, $userId);

    if ($profile === null) {
        sendResponse(array("error" => "User not found."), 404);
    }

    sendResponse($profile);
}

if ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        $data = array();
    }

    $name = trim(isset($data["name"]) ? $data["name"] : "");
    $municipality = trim(isset($data["municipality_code"]) ? $data["municipality_code"] : "");
    $barangay = trim(isset($data["barangay_code"]) ? $data["barangay_code"] : "");
    $school = trim(isset($data["school_id"]) ? $data["school_id"] : "");
    $course = trim(isset($data["course_id"]) ? $data["course_id"] : "");
    $year = trim(isset($data["year_level"]) ? $data["year_level"] : "");
    $gwa = isset($data["gwa"]) ? $data["gwa"] : "";
    $income = isset($data["annual_family_income"]) ? $data["annual_family_income"] : "";

    if ($name === "") {
        sendResponse(array("error" => "Full name is required."), 400);
    }

    if ($gwa !== "" && (!is_numeric($gwa) || (float)$gwa < 1 || (float)$gwa > 5)) {
        sendResponse(array("error" => "GWA must be between 1.00 and 5.00."), 400);
    }

    if ($income !== "" && (!is_numeric($income) || (float)$income < 0)) {
        sendResponse(array("error" => "Annual family income cannot be negative."), 400);
    }

    if ($school !== "" || $course !== "") {
        $stmt = $conn->prepare("SELECT id FROM schools WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $school);
        $stmt->execute();
        $schoolExists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$schoolExists) {
            sendResponse(array("error" => "Selected school does not exist."), 400);
        }

        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND school_id = ? LIMIT 1");
        $stmt->bind_param("ss", $course, $school);
        $stmt->execute();
        $courseExists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$courseExists) {
            sendResponse(array("error" => "Selected course does not belong to the selected school."), 400);
        }
    }

    $birthDate = null;

    // The current profile page stores Age instead of Birth Date.
    // We keep the database's birth_date field by calculating an approximate date.
    if (isset($data["age"]) && $data["age"] !== "" && is_numeric($data["age"])) {
        $age = (int)$data["age"];
        $birthDate = date("Y-m-d", strtotime("-" . $age . " years"));
    }

    $gwaValue = $gwa === "" ? null : (float)$gwa;
    $incomeValue = $income === "" ? null : (float)$income;

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, birth_date = ?, municipality_code = ?, barangay_code = ?, school_id = ?, course_id = ?, year_level = ?, gwa = ?, annual_family_income = ? WHERE id = ?");
    $stmt->bind_param("sssssssddi", $name, $birthDate, $municipality, $barangay, $school, $course, $year, $gwaValue, $incomeValue, $userId);

    if (!$stmt->execute()) {
        $stmt->close();
        sendResponse(array("error" => "Could not update your profile."), 500);
    }

    $stmt->close();

    sendResponse(array("success" => true, "profile" => getProfile($conn, $userId)));
}

sendResponse(array("error" => "Method not allowed."), 405);
?>
