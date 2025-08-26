<?php
// complaintController.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/utils.php';

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
    } else if ($type === "warden") {
        $stmt = $conn->prepare("SELECT warden_id, block_id FROM warden WHERE warden_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $userInfo = $result->fetch_assoc();
        }
    }

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
    $stmt->bind_param("siissis", $name, $block_id, $student_id, $description, $room, $is_completed, $created_at);
    $stmt->execute();
    echo json_encode($stmt->insert_id);
}

function putComplaintsById($conn, $token, $id) {
    $decodedToken = jwtDecoder($token);
    if (!$decodedToken || $decodedToken['user']['type'] !== 'warden') {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $stmt = $conn->prepare("UPDATE complaint SET is_completed = NOT is_completed, assigned_at = ? WHERE id = ?");
    $assigned_at = date('Y-m-d H:i:s');
    $stmt->bind_param("si", $assigned_at, $id);
    $stmt->execute();
    echo json_encode(["success" => "Complaint updated"]);
}

function deleteComplaints($conn, $token, $id) {
    $decodedToken = jwtDecoder($token);
    if (!$decodedToken || $decodedToken['user']['type'] !== 'warden') {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM complaint WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["success" => "Complaint deleted"]);
}

function getAllComplaintsByUser($conn, $token) {
    $decodedToken = jwtDecoder($token);
    if (!$decodedToken) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $user_id = $decodedToken['user']['user_id'];
    $type = $decodedToken['user']['type'];

    if ($type === "warden") {
        $result = $conn->query("SELECT * FROM complaint ORDER BY created_at DESC");
    } else if ($type === "student") {
        $stmt = $conn->prepare("SELECT * FROM complaint WHERE student_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    echo json_encode($complaints);
}

function getUserType($conn, $token) {
    $decodedToken = jwtDecoder($token);
    if (!$decodedToken) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $type = $decodedToken['user']['type'];
    echo json_encode(["userType" => $type]);
}

function getUserDetails($conn, $token) {
    $decodedToken = jwtDecoder($token);
    if (!$decodedToken) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $user_id = $decodedToken['user']['user_id'];
    $type = $decodedToken['user']['type'];
    $userDetails = null;

    if ($type === "student") {
        $stmt = $conn->prepare("SELECT u.full_name, u.email, u.phone, s.usn, b.block_id, b.block_name, s.room FROM users u, student s, block b WHERE u.user_id = ? AND u.user_id = s.student_id AND s.block_id = b.block_id");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetails = $result->fetch_assoc();
    } else if ($type === "warden") {
        $stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetails = $result->fetch_assoc();
    }
    
    echo json_encode($userDetails);
}
?>
