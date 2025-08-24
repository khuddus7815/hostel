<?php
// db.php

$servername = "sdb-87.hosting.stackcp.net";
$username = "tranetra"; // Your MySQL username
$password = "y*rIWWOqA9!T"; // Your MySQL password
$dbname = "tranetra-35313133cb7c"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>