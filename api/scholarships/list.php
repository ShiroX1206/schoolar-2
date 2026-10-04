<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/response.php";
require_once __DIR__ . "/helpers.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$search = trim(isset($_GET["search"]) ? $_GET["search"] : "");
$municipality = trim(isset($_GET["municipality"]) ? $_GET["municipality"] : "");
$barangay = trim(isset($_GET["barangay"]) ? $_GET["barangay"] : "");
$includeUnavailable = isset($_GET["include_unavailable"]) && $_GET["include_unavailable"] === "1";

$sql = "SELECT * FROM scholarships WHERE 1=1";
$types = "";
$values = array();

if ($id > 0) {
    $sql .= " AND id = ?";
    $types .= "i";
    $values[] = $id;
}

if (!$includeUnavailable) {
    $sql .= " AND status = 'available'";
}

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR provider_name LIKE ?)";
    $types .= "ss";
    $values[] = "%" . $search . "%";
    $values[] = "%" . $search . "%";
}

if ($municipality !== "") {
    $sql .= " AND (municipality_code = ? OR municipality_code IS NULL OR municipality_code = '')";
    $types .= "s";
    $values[] = $municipality;
}

if ($barangay !== "") {
    $sql .= " AND (barangay_code = ? OR barangay_code IS NULL OR barangay_code = '')";
    $types .= "s";
    $values[] = $barangay;
}

$sql .= " ORDER BY CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC, updated_at DESC";

if ($types === "") {
    $result = $conn->query($sql);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
}

$items = array();

while ($row = $result->fetch_assoc()) {
    $items[] = formatScholarship($conn, $row);
}

if ($id > 0) {
    if (count($items) === 0) {
        sendResponse(array("error" => "Scholarship not found."), 404);
    }

    sendResponse($items[0]);
}

sendResponse($items);
?>
