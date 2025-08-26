<?php
// utils.php - Utility functions

// Set JWT secret - in production, use environment variable
$JWT_SECRET = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';

/**
 * Generate JWT token
 * @param int $user_id User ID
 * @param string $type User type (student/warden)
 * @return string|false JWT token or false on failure
 */
function jwtGenerator($user_id, $type) {
    global $JWT_SECRET;
    
    if (empty($user_id) || empty($type)) {
        return false;
    }

    // JWT Header
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]);

    // JWT Payload
    $payload = json_encode([
        'user' => [
            'user_id' => (int)$user_id,
            'type' => $type
        ],
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 hours expiration
    ]);

    // Base64 URL encode
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);

    // Create signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);

    // Create JWT
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Decode and verify JWT token
 * @param string $token JWT token
 * @return array|false Decoded payload or false on failure
 */
function jwtDecoder($token) {
    global $JWT_SECRET;
    
    if (empty($token) || empty($JWT_SECRET)) {
        return false;
    }

    try {
        // Split token into parts
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        // Decode header and payload
        $decodedHeader = json_decode(base64UrlDecode($header), true);
        $decodedPayload = json_decode(base64UrlDecode($payload), true);

        if (!$decodedHeader || !$decodedPayload) {
            return false;
        }

        // Verify algorithm
        if ($decodedHeader['alg'] !== 'HS256') {
            return false;
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $header . "." . $payload, $JWT_SECRET, true);
        $providedSignature = base64UrlDecode($signature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return false;
        }

        // Check expiration
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }

        return $decodedPayload;

    } catch (Exception $e) {
        return false;
    }
}

/**
 * Base64 URL encode
 * @param string $data Data to encode
 * @return string Encoded string
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decode
 * @param string $data Data to decode
 * @return string Decoded string
 */
function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

/**
 * Sanitize input data
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure random string
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 32) {
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        // Fallback to less secure method
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
}

/**
 * Log error message
 * @param string $message Error message
 * @param array $context Additional context
 */
function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];
    
    error_log(json_encode($logEntry), 3, 'errors.log');
}

/**
 * Send JSON response
 * @param mixed $data Response data
 * @param int $status HTTP status code
 */
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}
?>
