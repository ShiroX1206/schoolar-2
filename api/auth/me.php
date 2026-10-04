<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    sendResponse(array("authenticated" => false), 401);
}

$userId = (int)$_SESSION["user_id"];

$stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION = array();
    session_destroy();
    sendResponse(array("authenticated" => false), 401);
}

sendResponse(array(
    "authenticated" => true,
    "user_id" => (int)$user["id"],
    "full_name" => $user["full_name"],
    "email" => $user["email"],
    "role" => $user["role"]
));
?>
