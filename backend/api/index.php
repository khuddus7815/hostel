<?php
// API index.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../controller/userController.php';
require_once __DIR__ . '/../controller/complaintController.php';

// Set headers for CORS and JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and URI
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the request URI
$parsedUri = parse_url($requestUri);
$path = $parsedUri['path'];

// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);

// Split path into segments
$pathSegments = explode('/', trim($path, '/'));
$endpoint = $pathSegments[0] ?? '';
$id = $pathSegments[1] ?? null;

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();
$token = '';

// Extract token from Authorization header
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
}

// Route the request
try {
    switch ($endpoint) {
        case 'register':
            if ($requestMethod === 'POST') {
                userRegister($conn, $data);
            } else {
                http_response_code(405);
                echo json_encode(["error" => "Method Not Allowed"]);
            }
            break;

        case 'login':
            if ($requestMethod === 'POST') {
                userLogin($conn, $data);
            } else {
                http_response_code(405);
                echo json_encode(["error" => "Method Not Allowed"]);
            }
            break;

        case 'complaints':
            switch ($requestMethod) {
                case 'POST':
                    if ($id) {
                        // Update complaint by ID
                        putComplaintsById($conn, $token, $id, $data);
                    } else {
                        // Create new complaint
                        postComplaints($conn, $token, $data);
                    }
                    break;
                case 'GET':
                    getAllComplaintsByUser($conn, $token);
                    break;
                case 'PUT':
                    if ($id) {
                        putComplaintsById($conn, $token, $id, $data);
                    } else {
                        http_response_code(400);
                        echo json_encode(["error" => "ID required for update"]);
                    }
                    break;
                case 'DELETE':
                    if ($id) {
                        deleteComplaints($conn, $token, $id);
                    } else {
                        http_response_code(400);
                        echo json_encode(["error" => "ID required for deletion"]);
                    }
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(["error" => "Method Not Allowed"]);
            }
            break;

        case 'userType':
            if ($requestMethod === 'GET') {
                getUserType($conn, $token);
            } else {
                http_response_code(405);
                echo json_encode(["error" => "Method Not Allowed"]);
            }
            break;

        case 'userDetails':
            if ($requestMethod === 'GET') {
                if ($id) {
                    getUserDetailsById($conn, $token, $id);
                } else {
                    getUserDetails($conn, $token);
                }
            } else {
                http_response_code(405);
                echo json_encode(["error" => "Method Not Allowed"]);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(["error" => "Endpoint not found"]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "message" => $e->getMessage()]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>
