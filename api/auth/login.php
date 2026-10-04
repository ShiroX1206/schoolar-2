<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    $data = array();
}

$email = trim(isset($data["email"]) ? $data["email"] : "");
$password = isset($data["password"]) ? $data["password"] : "";
$requiredRole = isset($data["role"]) ? $data["role"] : "";

if ($email === "" || $password === "") {
    sendResponse(array("error" => "Email and password are required."), 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(array("error" => "Please enter a valid email address."), 400);
}

$stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
    sendResponse(array("error" => "Invalid email or password."), 401);
}

if ($requiredRole !== "" && $user["role"] !== $requiredRole) {
    sendResponse(array("error" => "This account cannot use this login page."), 403);
}

session_regenerate_id(true);
$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["role"] = $user["role"];

sendResponse(array(
    "success" => true,
    "user_id" => (int)$user["id"],
    "full_name" => $user["full_name"],
    "email" => $user["email"],
    "role" => $user["role"]
));
?>
