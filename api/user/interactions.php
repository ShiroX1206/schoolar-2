<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../scholarships/helpers.php";

requireUser();
$userId = (int)$_SESSION["user_id"];
$method = $_SERVER["REQUEST_METHOD"];
$action = isset($_GET["action"]) ? $_GET["action"] : "";

if ($action === "saved" && $method === "GET") {
    $stmt = $conn->prepare("SELECT s.* FROM saved_scholarships ss JOIN scholarships s ON s.id = ss.scholarship_id WHERE ss.user_id = ? ORDER BY ss.saved_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = array();

    while ($row = $result->fetch_assoc()) {
        $items[] = formatScholarship($conn, $row);
    }

    $stmt->close();
    sendResponse($items);
}

if ($action === "saved" && $method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $scholarshipId = isset($data["scholarship_id"]) ? (int)$data["scholarship_id"] : 0;

    if ($scholarshipId <= 0) {
        sendResponse(array("error" => "Invalid scholarship."), 400);
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO saved_scholarships (user_id, scholarship_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $scholarshipId);
    $stmt->execute();
    $stmt->close();

    sendResponse(array("success" => true, "saved" => true));
}

if ($action === "saved" && $method === "DELETE") {
    $scholarshipId = isset($_GET["scholarship_id"]) ? (int)$_GET["scholarship_id"] : 0;

    $stmt = $conn->prepare("DELETE FROM saved_scholarships WHERE user_id = ? AND scholarship_id = ?");
    $stmt->bind_param("ii", $userId, $scholarshipId);
    $stmt->execute();
    $stmt->close();

    sendResponse(array("success" => true, "saved" => false));
}

if ($action === "status" && $method === "GET") {
    $scholarshipId = isset($_GET["scholarship_id"]) ? (int)$_GET["scholarship_id"] : 0;

    $stmt = $conn->prepare("SELECT 1 FROM saved_scholarships WHERE user_id = ? AND scholarship_id = ? LIMIT 1");
    $stmt->bind_param("ii", $userId, $scholarshipId);
    $stmt->execute();
    $saved = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    sendResponse(array("saved" => (bool)$saved));
}

if ($action === "viewed" && $method === "GET") {
    $stmt = $conn->prepare("SELECT s.*, MAX(v.viewed_at) AS latest_viewed FROM viewed_scholarships v JOIN scholarships s ON s.id = v.scholarship_id WHERE v.user_id = ? GROUP BY s.id ORDER BY latest_viewed DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = array();

    while ($row = $result->fetch_assoc()) {
        $items[] = formatScholarship($conn, $row);
    }

    $stmt->close();
    sendResponse($items);
}

if ($action === "view" && $method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $scholarshipId = isset($data["scholarship_id"]) ? (int)$data["scholarship_id"] : 0;

    if ($scholarshipId > 0) {
        $stmt = $conn->prepare("INSERT INTO viewed_scholarships (user_id, scholarship_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $scholarshipId);
        $stmt->execute();
        $stmt->close();
    }

    sendResponse(array("success" => true));
}

sendResponse(array("error" => "Invalid interaction request."), 400);
?>
