<?php
// start-server.php - PHP Development Server Router

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Log the request
error_log("PHP Server Request: " . $_SERVER['REQUEST_METHOD'] . " " . $requestUri);

// Handle API requests
if (strpos($requestUri, '/api/') === 0) {
    // Remove /api prefix and route to our API
    $_SERVER['REQUEST_URI'] = substr($requestUri, 4);
    $_GET = [];
    
    // Parse query string if exists
    $parts = parse_url($requestUri);
    if (isset($parts['query'])) {
        parse_str($parts['query'], $_GET);
    }
    
    // Include the API handler
    require_once __DIR__ . '/api/index.php';
    return true;
}

// For other requests, check if file exists
$file = __DIR__ . $requestUri;

if (is_file($file)) {
    return false; // Serve the file directly
}

// Return 404 for other requests
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
return true;
?>
