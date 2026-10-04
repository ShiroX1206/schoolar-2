<?php
session_start();
header('Content-Type: application/json');

// Clear session variables and destroy session
session_unset();
session_destroy();

echo json_encode(['success' => true]);