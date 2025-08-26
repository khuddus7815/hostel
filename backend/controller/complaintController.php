<?php
// complaintController.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/utils.php';

function decodeUser($conn, $token) {
    if (empty($token)) {
        return false;
    }

    $decodedToken = jwtDecoder($token);
    if (!$decodedToken) {
        return false;
    }

    $user_id = $decodedToken['user']['user_id'];
    $type = $decodedToken['user']['type'];
    $userInfo = ['user_id' => $user_id, 'type' => $type];

    if ($type === "student") {
        $stmt = $conn->prepare("SELECT student_id, room, block_id FROM student WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $studentInfo = $result->fetch_assoc();
            $userInfo = array_merge($userInfo, $studentInfo);
        }
    } else if ($type === "warden") {
        $stmt = $conn->prepare("SELECT warden_id, block_id FROM warden WHERE warden_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $wardenInfo = $result->fetch_assoc();
            $userInfo = array_merge($userInfo, $wardenInfo);
        }
    }

    return $userInfo;
}

function postComplaints($conn, $token, $data) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo || $userInfo['type'] !== 'student') {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $student_id = $userInfo['user_id'];
    $block_id = $userInfo['block_id'] ?? null;
    $title = $data['name'] ?? $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $room = $data['room'] ?? $userInfo['room'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    $status = 'pending';

    try {
        $stmt = $conn->prepare("INSERT INTO complaints (title, description, user_id, status, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $title, $description, $student_id, $status, $created_at);
        $stmt->execute();
        
        $complaint_id = $conn->insert_id;
        echo json_encode([
            "message" => "Complaint submitted successfully", 
            "complaint_id" => $complaint_id
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to submit complaint"]);
    }
}

function putComplaintsById($conn, $token, $id, $data = null) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo || $userInfo['type'] !== 'warden') {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    try {
        $status = $data['status'] ?? 'resolved';
        $assigned_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE complaints SET status = ?, updated_at = ? WHERE complaint_id = ?");
        $stmt->bind_param("ssi", $status, $assigned_at, $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(["message" => "Complaint updated successfully"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Complaint not found"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update complaint"]);
    }
}

function deleteComplaints($conn, $token, $id) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo || $userInfo['type'] !== 'warden') {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM complaints WHERE complaint_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(["message" => "Complaint deleted successfully"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Complaint not found"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete complaint"]);
    }
}

function getAllComplaintsByUser($conn, $token) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $user_id = $userInfo['user_id'];
    $type = $userInfo['type'];

    try {
        if ($type === "warden") {
            $result = $conn->query("SELECT c.*, u.full_name, u.email FROM complaints c LEFT JOIN users u ON c.user_id = u.user_id ORDER BY c.created_at DESC");
        } else if ($type === "student") {
            $stmt = $conn->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden"]);
            return;
        }

        $complaints = [];
        while ($row = $result->fetch_assoc()) {
            $complaints[] = $row;
        }
        echo json_encode($complaints);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch complaints"]);
    }
}

function getUserType($conn, $token) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    echo json_encode(["userType" => $userInfo['type']]);
}

function getUserDetails($conn, $token) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    $user_id = $userInfo['user_id'];
    $type = $userInfo['type'];

    try {
        if ($type === "student") {
            $stmt = $conn->prepare("
                SELECT u.full_name, u.email, u.phone, s.usn, s.block_id, s.room 
                FROM users u 
                LEFT JOIN student s ON u.user_id = s.student_id 
                WHERE u.user_id = ?
            ");
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
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch user details"]);
    }
}

function getUserDetailsById($conn, $token, $id) {
    $userInfo = decodeUser($conn, $token);
    if (!$userInfo) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    try {
        $stmt = $conn->prepare("
            SELECT u.full_name, u.email, u.phone, u.type,
                   s.usn, s.block_id as student_block_id, s.room,
                   w.block_id as warden_block_id
            FROM users u 
            LEFT JOIN student s ON u.user_id = s.student_id 
            LEFT JOIN warden w ON u.user_id = w.warden_id
            WHERE u.user_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userDetails = $result->fetch_assoc();
            echo json_encode($userDetails);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "User not found"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch user details"]);
    }
}
?>
