<?php
// userController.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/utils.php';

function userRegister($conn, $data) {
    try {
        // Validate required fields
        $required_fields = ['full_name', 'email', 'phone', 'password', 'type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required field: $field"]);
                return;
            }
        }

        $full_name = $data['full_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $password = $data['password'];
        $type = $data['type'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid email format"]);
            return;
        }

        // Validate password length
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "Password must be at least 6 characters long"]);
            return;
        }

        // Check if user already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["error" => "User already exists with this email"]);
            return;
        }

        // Hash password
        $bcryptPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $email, $phone, $bcryptPassword, $type);
        $stmt->execute();
        $userId = $conn->insert_id;

        // Generate JWT token
        $jwtToken = jwtGenerator($userId, $type);
        
        if (!$jwtToken) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to generate authentication token"]);
            return;
        }

        // Handle role-specific data
        if ($type === "student") {
            $block_id = $data['block_id'] ?? null;
            $usn = $data['usn'] ?? '';
            $room = $data['room'] ?? '';
            
            if (empty($block_id) || empty($usn) || empty($room)) {
                http_response_code(400);
                echo json_encode(["error" => "Student requires block_id, usn, and room"]);
                return;
            }
            
            $stmt = $conn->prepare("INSERT INTO student (student_id, block_id, usn, room) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $userId, $block_id, $usn, $room);
            $stmt->execute();
        } else if ($type === "warden") {
            $block_id = $data['block_id'] ?? null;
            
            if (empty($block_id)) {
                http_response_code(400);
                echo json_encode(["error" => "Warden requires block_id"]);
                return;
            }
            
            $stmt = $conn->prepare("INSERT INTO warden (warden_id, block_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $block_id);
            $stmt->execute();
        }

        echo json_encode([
            "message" => "User registered successfully",
            "jwtToken" => $jwtToken,
            "user" => [
                "id" => $userId,
                "email" => $email,
                "type" => $type
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Registration failed", "message" => $e->getMessage()]);
    }
}

function userLogin($conn, $data) {
    try {
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Email and password are required"]);
            return;
        }

        $email = $data['email'];
        $password = $data['password'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid email format"]);
            return;
        }

        // Get user from database
        $stmt = $conn->prepare("SELECT user_id, password, type, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid credentials"]);
            return;
        }

        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid credentials"]);
            return;
        }

        // Generate JWT token
        $jwtToken = jwtGenerator($user['user_id'], $user['type']);
        
        if (!$jwtToken) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to generate authentication token"]);
            return;
        }

        echo json_encode([
            "message" => "Login successful",
            "jwtToken" => $jwtToken,
            "user" => [
                "id" => $user['user_id'],
                "email" => $email,
                "name" => $user['full_name'],
                "type" => $user['type']
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Login failed", "message" => $e->getMessage()]);
    }
}

function verifyToken($conn, $token) {
    try {
        if (empty($token)) {
            http_response_code(401);
            echo json_encode(["error" => "Token required"]);
            return;
        }

        $decodedToken = jwtDecoder($token);
        if (!$decodedToken) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid token"]);
            return;
        }

        echo json_encode([
            "valid" => true,
            "user" => $decodedToken['user']
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Token verification failed"]);
    }
}
?>
