<?php
// db.php - Database connection

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production

// Set content type and CORS headers
if (!headers_sent()) {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
}

// Database configuration
$servername = "sdb-87.hosting.stackcp.net";
$username = "tranetra";
$password = "y*rIWWOqA9!T";
$dbname = "tranetra-35313133cb7c";
$port = 41353;

try {
    // Create connection with error handling
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database connection failed",
            "message" => "Unable to connect to the database server"
        ]);
        exit();
    }

    // Set character set to handle UTF-8 properly
    if (!$conn->set_charset("utf8mb4")) {
        http_response_code(500);
        echo json_encode([
            "error" => "Character set error",
            "message" => "Failed to set character set"
        ]);
        exit();
    }

    // Enable error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Set timezone
    $conn->query("SET time_zone = '+00:00'");

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection error",
        "message" => "Database server is not available"
    ]);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Connection error",
        "message" => "An unexpected error occurred"
    ]);
    exit();
}

// Function to safely close connection
function closeConnection($connection) {
    if ($connection && !$connection->connect_error) {
        $connection->close();
    }
}

// Register shutdown function to close connection
register_shutdown_function(function() use ($conn) {
    closeConnection($conn);
});
?>
