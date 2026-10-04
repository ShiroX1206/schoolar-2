<?php
function splitLines($text)
{
    if ($text === null || trim($text) === "") {
        return array();
    }

    $lines = preg_split('/\r\n|\r|\n/', $text);
    $result = array();

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== "") {
            $result[] = $line;
        }
    }

    return $result;
}

function splitComma($text)
{
    if ($text === null || trim($text) === "") {
        return array();
    }

    $parts = explode(',', $text);
    $result = array();

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== "") {
            $result[] = $part;
        }
    }

    return $result;
}

function getCriteria($conn, $scholarshipId)
{
    $stmt = $conn->prepare("SELECT min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required FROM eligibility_criteria WHERE scholarship_id = ? LIMIT 1");
    $stmt->bind_param("i", $scholarshipId);
    $stmt->execute();
    $criteria = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$criteria) {
        return array(
            "min_gwa" => null,
            "max_gwa" => null,
            "max_annual_income" => null,
            "min_age" => null,
            "max_age" => null,
            "year_levels" => array(),
            "course_scope" => array(),
            "residency_required" => false
        );
    }

    $yearLevels = json_decode($criteria["year_levels"], true);
    $courseScope = json_decode($criteria["course_scope"], true);

    if (!is_array($yearLevels)) {
        $yearLevels = array();
    }

    if (!is_array($courseScope)) {
        $courseScope = array();
    }

    return array(
        "min_gwa" => $criteria["min_gwa"] !== null ? (float)$criteria["min_gwa"] : null,
        "max_gwa" => $criteria["max_gwa"] !== null ? (float)$criteria["max_gwa"] : null,
        "max_annual_income" => $criteria["max_annual_income"] !== null ? (float)$criteria["max_annual_income"] : null,
        "min_age" => $criteria["min_age"] !== null ? (int)$criteria["min_age"] : null,
        "max_age" => $criteria["max_age"] !== null ? (int)$criteria["max_age"] : null,
        "year_levels" => $yearLevels,
        "course_scope" => $courseScope,
        "residency_required" => (bool)$criteria["residency_required"]
    );
}

function formatScholarship($conn, $row)
{
    $location = "Nationwide";

    if (!empty($row["barangay_name"]) && !empty($row["municipality_name"])) {
        $location = $row["barangay_name"] . ", " . $row["municipality_name"];
    } elseif (!empty($row["municipality_name"])) {
        $location = $row["municipality_name"];
    }

    $contact = array();

    if (!empty($row["contact_email"])) {
        $contact[] = array("label" => "Email", "value" => $row["contact_email"]);
    }

    if (!empty($row["contact_phone"])) {
        $contact[] = array("label" => "Phone", "value" => $row["contact_phone"]);
    }

    $criteria = getCriteria($conn, (int)$row["id"]);

    return array(
        "id" => (int)$row["id"],
        "name" => $row["name"],
        "provider" => $row["provider_name"],
        "provider_name" => $row["provider_name"],
        "category" => "Scholarship",
        "benefits" => splitLines($row["benefits"]),
        "requirements" => splitLines($row["requirements"]),
        "deadline" => $row["deadline"],
        "status" => $row["status"],
        "municipality" => $row["municipality_code"],
        "municipality_code" => $row["municipality_code"],
        "municipalityName" => $row["municipality_name"] ? $row["municipality_name"] : "Nationwide",
        "municipality_name" => $row["municipality_name"],
        "barangay" => $row["barangay_code"],
        "barangay_code" => $row["barangay_code"],
        "barangayName" => $row["barangay_name"] ? $row["barangay_name"] : "Any Barangay",
        "barangay_name" => $row["barangay_name"],
        "location" => $location,
        "contact" => $contact,
        "contactEmail" => $row["contact_email"] ? $row["contact_email"] : "",
        "contactPhone" => $row["contact_phone"] ? $row["contact_phone"] : "",
        "criteria" => $criteria,
        "minGwa" => $criteria["min_gwa"],
        "maxGwa" => $criteria["max_gwa"],
        "maxIncome" => $criteria["max_annual_income"],
        "minAge" => $criteria["min_age"],
        "maxAge" => $criteria["max_age"],
        "yearLevels" => $criteria["year_levels"],
        "courseScope" => implode(", ", $criteria["course_scope"]),
        "residency" => $criteria["residency_required"]
    );
}

function getScholarshipById($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM scholarships WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return formatScholarship($conn, $row);
}
?>
