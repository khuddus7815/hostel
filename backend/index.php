<?php
// index.php

require_once 'db.php';
require_once 'utils.php';
require_once 'userController.php';
require_once 'complaintController.php';

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$data = json_decode(file_get_contents('php://input'), true);

$parts = explode('/', $requestUri);
$endpoint = $parts[1];

header("Content-Type: application/json");

// Handle authentication routes
if ($endpoint === 'register' && $requestMethod === 'POST') {
    userRegister($conn, $data);
} else if ($endpoint === 'login' && $requestMethod === 'POST') {
    userLogin($conn, $data);
} 
// Handle other routes
else if ($endpoint === 'complaints' && $requestMethod === 'POST') {
    $headers = getallheaders();
    $token = $headers['Authorization'];
    postComplaints($conn, $token, $data);
}
// ... (Add logic for other endpoints like GET /complaints, PUT /complaints/{id}, etc.)
else {
    http_response_code(404);
    echo json_encode(["error" => "Not Found"]);
}

$conn->close();
?>