<?php
// utils.php

function jwtGenerator($user_id, $type) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode(['user' => ['user_id' => $user_id, 'type' => $type]]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $secret = getenv('JWTSECRET');
    if (!$secret) {
        // Return a proper error if the secret is not set
        return false;
    }
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function jwtDecoder($token) {
    $secret = getenv('JWTSECRET');
    if (!$secret) {
        return false;
    }
    
    $parts = explode('.', $token);
    
    if (count($parts) != 3) {
        return false;
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));
    
    $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
    
    if (hash_equals($signature, $expectedSignature)) {
        return json_decode($payload, true);
    }
    return false;
}
?>