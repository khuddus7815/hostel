<?php
// userController.php

require_once 'db.php';
require_once 'utils.php';

function userRegister($conn, $data) {
    $full_name = $data['full_name'];
    $email = $data['email'];
    $phone = $data['phone'];
    $password = $data['password'];
    $type = $data['type'];

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(401);
        echo json_encode("User already exist!");
        return;
    }

    $bcryptPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone, $bcryptPassword, $type);
    $stmt->execute();
    $userId = $conn->insert_id;

    $jwtToken = jwtGenerator($userId, $type);

    if ($type === "student") {
        $block_id = $data['block_id'];
        $usn = $data['usn'];
        $room = $data['room'];
        $stmt = $conn->prepare("INSERT INTO student (student_id, block_id, usn, room) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $userId, $block_id, $usn, $room);
        $stmt->execute();
    } else if ($type === "warden") {
        $block_id = $data['block_id'];
        $stmt = $conn->prepare("INSERT INTO warden (warden_id, block_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $block_id);
        $stmt->execute();
    }

    echo json_encode(["jwtToken" => $jwtToken]);
}

function userLogin($conn, $data) {
    $email = $data['email'];
    $password = $data['password'];

    $stmt = $conn->prepare("SELECT user_id, password, type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode("Invalid Credential");
        return;
    }

    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode("Invalid Credential");
        return;
    }

    $jwtToken = jwtGenerator($user['user_id'], $user['type']);
    echo json_encode(["jwtToken" => $jwtToken]);
}
?>