<?php
// db.php

header("Content-Type: application/json");

$servername = "sdb-87.hosting.stackcp.net";
$username = "tranetra";
$password = "y*rIWWOqA9!T";
$dbname = "tranetra-35313133cb7c";
$port = 41146;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    die();
}

// Set character set
$conn->set_charset("utf8mb4");

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>
