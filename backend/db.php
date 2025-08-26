<?php
// db.php

$servername = "sdb-87.hosting.stackcp.net";
$username = "tranetra";
$password = "y*rIWWOqA9!T";
$dbname = "tranetra-35313133cb7c";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");
?>
