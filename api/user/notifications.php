<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
require_once __DIR__ . "/../config/auth.php";

requireUser();
$userId = (int)$_SESSION["user_id"];
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, scholarship_id, type, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = array();

    while ($row = $result->fetch_assoc()) {
        $items[] = array(
            "id" => (int)$row["id"],
            "scholarship_id" => $row["scholarship_id"] !== null ? (int)$row["scholarship_id"] : null,
            "type" => $row["type"],
            "title" => $row["title"],
            "message" => $row["message"],
            "is_read" => (bool)$row["is_read"],
            "date" => date("F j, Y", strtotime($row["created_at"]))
        );
    }

    $stmt->close();
    sendResponse($items);
}

if ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($data["id"]) ? (int)$data["id"] : 0;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $stmt->close();

    sendResponse(array("success" => true));
}

sendResponse(array("error" => "Method not allowed."), 405);
?>
