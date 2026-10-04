<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/response.php";

$type = isset($_GET["type"]) ? $_GET["type"] : "";

if ($type === "schools") {
    $result = $conn->query("SELECT id, name, acronym FROM schools ORDER BY name");
    $items = array();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    sendResponse($items);
}

if ($type === "courses") {
    $schoolId = isset($_GET["school_id"]) ? $_GET["school_id"] : "";

    if ($schoolId === "") {
        sendResponse(array("error" => "school_id is required."), 400);
    }

    $stmt = $conn->prepare("SELECT id, school_id, name, code FROM courses WHERE school_id = ? ORDER BY name");
    $stmt->bind_param("s", $schoolId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = array();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();
    sendResponse($items);
}

sendResponse(array("error" => "Unknown reference type."), 400);
?>
