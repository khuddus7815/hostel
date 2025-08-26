<?php
// index.php

require_once 'db.php';
require_once 'utils.php';
require_once 'userController.php';
require_once 'complaintController.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$id = $_GET['id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

switch ($endpoint) {
    case 'register':
        if ($requestMethod === 'POST') {
            userRegister($conn, $data);
        } else {
            http_response_code(405);
        }
        break;
    case 'login':
        if ($requestMethod === 'POST') {
            userLogin($conn, $data);
        } else {
            http_response_code(405);
        }
        break;
    case 'complaints':
        if ($requestMethod === 'POST') {
            postComplaints($conn, $token, $data);
        } else if ($requestMethod === 'GET') {
            getAllComplaintsByUser($conn, $token);
        } else if ($requestMethod === 'PUT' && $id) {
            putComplaintsById($conn, $token, $id);
        } else if ($requestMethod === 'DELETE' && $id) {
            deleteComplaints($conn, $token, $id);
        } else {
            http_response_code(405);
        }
        break;
    case 'userType':
        if ($requestMethod === 'GET') {
            getUserType($conn, $token);
        } else {
            http_response_code(405);
        }
        break;
    case 'userDetails':
        if ($requestMethod === 'GET') {
            getUserDetails($conn, $token);
        } else {
            http_response_code(405);
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(["error" => "Not Found"]);
        break;
}

$conn->close();
?>