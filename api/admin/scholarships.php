<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../scholarships/helpers.php";

requireAdmin();

$method = $_SERVER["REQUEST_METHOD"];

function numberOrNull($value)
{
    if ($value === "" || $value === null) {
        return null;
    }

    return is_numeric($value) ? $value : null;
}

function validateScholarship($data)
{
    if (!isset($data["name"]) || trim($data["name"]) === "") {
        return "Scholarship name is required.";
    }

    if (isset($data["status"]) && $data["status"] !== "available" && $data["status"] !== "not available") {
        return "Invalid scholarship status.";
    }

    if (!empty($data["contact_email"]) && !filter_var($data["contact_email"], FILTER_VALIDATE_EMAIL)) {
        return "Invalid contact email.";
    }

    $criteria = isset($data["criteria"]) && is_array($data["criteria"]) ? $data["criteria"] : array();

    $numericFields = array("min_gwa", "max_gwa", "max_annual_income", "min_age", "max_age");

    foreach ($numericFields as $field) {
        if (isset($criteria[$field]) && $criteria[$field] !== "" && !is_numeric($criteria[$field])) {
            return "Invalid " . $field . ".";
        }
    }

    if (isset($criteria["min_gwa"], $criteria["max_gwa"]) && $criteria["min_gwa"] !== "" && $criteria["max_gwa"] !== "" && (float)$criteria["min_gwa"] > (float)$criteria["max_gwa"]) {
        return "Minimum GWA cannot be greater than maximum GWA.";
    }

    if (isset($criteria["min_age"], $criteria["max_age"]) && $criteria["min_age"] !== "" && $criteria["max_age"] !== "" && (int)$criteria["min_age"] > (int)$criteria["max_age"]) {
        return "Minimum age cannot be greater than maximum age.";
    }

    if (isset($criteria["max_annual_income"]) && $criteria["max_annual_income"] !== "" && (float)$criteria["max_annual_income"] < 0) {
        return "Maximum annual income cannot be negative.";
    }

    return "";
}

function saveCriteria($conn, $scholarshipId, $criteria)
{
    $delete = $conn->prepare("DELETE FROM eligibility_criteria WHERE scholarship_id = ?");
    $delete->bind_param("i", $scholarshipId);
    $delete->execute();
    $delete->close();

    $minGwa = numberOrNull(isset($criteria["min_gwa"]) ? $criteria["min_gwa"] : null);
    $maxGwa = numberOrNull(isset($criteria["max_gwa"]) ? $criteria["max_gwa"] : null);
    $maxIncome = numberOrNull(isset($criteria["max_annual_income"]) ? $criteria["max_annual_income"] : null);
    $minAge = numberOrNull(isset($criteria["min_age"]) ? $criteria["min_age"] : null);
    $maxAge = numberOrNull(isset($criteria["max_age"]) ? $criteria["max_age"] : null);
    $yearLevels = isset($criteria["year_levels"]) && is_array($criteria["year_levels"]) ? $criteria["year_levels"] : array();
    $courseScope = isset($criteria["course_scope"]) && is_array($criteria["course_scope"]) ? $criteria["course_scope"] : array();
    $residency = !empty($criteria["residency_required"]) ? 1 : 0;
    $yearJson = json_encode($yearLevels);
    $courseJson = json_encode($courseScope);

    $stmt = $conn->prepare("INSERT INTO eligibility_criteria (scholarship_id, min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssi", $scholarshipId, $minGwa, $maxGwa, $maxIncome, $minAge, $maxAge, $yearJson, $courseJson, $residency);
    $stmt->execute();
    $stmt->close();
}

// This runs after a brand new scholarship is added. It gives every
// student account a "New scholarship" notification. If this fails for
// any reason, it is not treated as a fatal error -- the scholarship
// itself has already been saved successfully by this point.
function notifyUsersOfNewScholarship($conn, $scholarshipId, $scholarshipName)
{
    try {
        $title = "New scholarship posted";
        $message = $scholarshipName . " was just added. Check if you are eligible.";
        $type = "new_match";

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, scholarship_id, type, title, message) SELECT id, ?, ?, ?, ? FROM users WHERE role = 'user'");
        $stmt->bind_param("isss", $scholarshipId, $type, $title, $message);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $error) {
        // Notifications are a nice-to-have. Do not stop the scholarship
        // from saving just because a notification could not be created.
    }
}

if ($method === "GET") {
    $result = $conn->query("SELECT * FROM scholarships ORDER BY updated_at DESC");
    $items = array();

    while ($row = $result->fetch_assoc()) {
        $items[] = formatScholarship($conn, $row);
    }

    sendResponse($items);
}

if ($method === "POST" || $method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        $data = array();
    }

    $error = validateScholarship($data);

    if ($error !== "") {
        sendResponse(array("error" => $error), 400);
    }

    $name = trim($data["name"]);
    $provider = trim(isset($data["provider_name"]) ? $data["provider_name"] : "");
    $benefits = isset($data["benefits"]) && is_array($data["benefits"]) ? implode("\n", $data["benefits"]) : (string)(isset($data["benefits"]) ? $data["benefits"] : "");
    $requirements = isset($data["requirements"]) && is_array($data["requirements"]) ? implode("\n", $data["requirements"]) : (string)(isset($data["requirements"]) ? $data["requirements"] : "");
    $deadline = !empty($data["deadline"]) ? $data["deadline"] : null;
    $status = isset($data["status"]) ? $data["status"] : "available";
    $municipalityCode = !empty($data["municipality_code"]) ? $data["municipality_code"] : null;
    $barangayCode = !empty($data["barangay_code"]) ? $data["barangay_code"] : null;
    $municipalityName = !empty($data["municipality_name"]) ? $data["municipality_name"] : null;
    $barangayName = !empty($data["barangay_name"]) ? $data["barangay_name"] : null;
    $contactEmail = !empty($data["contact_email"]) ? $data["contact_email"] : null;
    $contactPhone = !empty($data["contact_phone"]) ? $data["contact_phone"] : null;
    $criteria = isset($data["criteria"]) && is_array($data["criteria"]) ? $data["criteria"] : array();

    $conn->begin_transaction();

    try {
        if ($method === "POST") {
            $createdBy = (int)$_SESSION["user_id"];

            $stmt = $conn->prepare("INSERT INTO scholarships (name, provider_name, benefits, requirements, deadline, status, municipality_code, barangay_code, municipality_name, barangay_name, contact_email, contact_phone, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssi", $name, $provider, $benefits, $requirements, $deadline, $status, $municipalityCode, $barangayCode, $municipalityName, $barangayName, $contactEmail, $contactPhone, $createdBy);
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
        } else {
            $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

            if ($id <= 0) {
                throw new Exception("Missing scholarship id.");
            }

            $stmt = $conn->prepare("UPDATE scholarships SET name = ?, provider_name = ?, benefits = ?, requirements = ?, deadline = ?, status = ?, municipality_code = ?, barangay_code = ?, municipality_name = ?, barangay_name = ?, contact_email = ?, contact_phone = ? WHERE id = ?");
            $stmt->bind_param("ssssssssssssi", $name, $provider, $benefits, $requirements, $deadline, $status, $municipalityCode, $barangayCode, $municipalityName, $barangayName, $contactEmail, $contactPhone, $id);
            $stmt->execute();
            $stmt->close();
        }

        saveCriteria($conn, $id, $criteria);
        $conn->commit();

        if ($method === "POST" && $status === "available") {
            notifyUsersOfNewScholarship($conn, $id, $name);
        }

        sendResponse(array("success" => true, "id" => (int)$id));
    } catch (Exception $error) {
        $conn->rollback();
        sendResponse(array("error" => "Could not save the scholarship."), 500);
    }
}

if ($method === "DELETE") {
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

    if ($id <= 0) {
        sendResponse(array("error" => "Missing scholarship id."), 400);
    }

    $stmt = $conn->prepare("DELETE FROM scholarships WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(array("success" => true));
}

sendResponse(array("error" => "Method not allowed."), 405);
?>
