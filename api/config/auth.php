<?php
// This file checks whether the current user is logged in.

session_start();

function requireLogin()
{
    if (!isset($_SESSION["user_id"])) {
        sendResponse(array("error" => "Login required."), 401);
    }
}

function requireAdmin()
{
    requireLogin();

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        sendResponse(array("error" => "Admin login required."), 403);
    }
}

function requireUser()
{
    requireLogin();

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
        sendResponse(array("error" => "User login required."), 403);
    }
}
?>
