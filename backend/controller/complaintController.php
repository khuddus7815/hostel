<?php
// complaintController.php

require_once 'db.php';
require_once 'utils.php';

function decodeUser($conn, $token) {
    $decodedToken = jwtDecoder($token);
    if (!$decodedToken) {
        return false;
    }

    $user_id = $decodedToken['user']['user_id'];
    $type = $decodedToken['user']['type'];
    $userInfo = null;

    if ($type === "student") {
        $stmt = $conn->prepare("SELECT student_id, room, block_id FROM student WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $userInfo = $result->fetch_assoc();
        }
    }
    // ... (Add logic for warden if needed, similar to userController)

    return $userInfo;
}

function postComplaints($conn, $token, $data) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $student_id = $userInfo['student_id'];
    $block_id = $userInfo['block_id'];
    $name = $data['name'];
    $description = $data['description'];
    $room = $data['room'];
    $created_at = date('Y-m-d H:i:s');
    $is_completed = 0;

    $stmt = $conn->prepare("INSERT INTO complaint (name, block_id, student_id, description, room, is_completed, created_at, assigned_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("siissi", $name, $block_id, $student_id, $description, $room, $is_completed, $created_at);
    $stmt->execute();

    echo json_encode($stmt->insert_id);
}

// ... (Add other functions like putComplaintsByid, getAllComplaintsByUser, etc. similar to the Node.js file)
?>