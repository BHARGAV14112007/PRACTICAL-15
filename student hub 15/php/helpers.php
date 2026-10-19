<?php
/**
 * Student Hub - Common PHP Utilities & JSON Helper Functions
 * Practical 7: Form Data Processing, Server-Side Validation, Sanitization & JSON Storage
 */

// Start session if not already started and headers not sent
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * Sanitize generic string input
 *
 * @param mixed $data
 * @return string
 */
function sanitize_string($data) {
    if (!isset($data) || $data === null) {
        return '';
    }
    $data = trim((string)$data);
    $data = stripslashes($data);
    // Strip full script and style tag blocks including inner content
    $data = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/\1>/is', '', $data);
    // Strip remaining HTML tags
    $data = strip_tags($data);
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize email input
 *
 * @param string $email
 * @return string
 */
function sanitize_email($email) {
    $email = trim((string)$email);
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email address
 *
 * @param string $email
 * @return bool
 */
function validate_email($email) {
    if (empty($email)) {
        return false;
    }
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
}

/**
 * Validate Indian / campus mobile number (10 digits starting with 6-9)
 *
 * @param string $mobile
 * @return bool
 */
function validate_mobile($mobile) {
    return (bool)preg_match('/^[6-9][0-9]{9}$/', $mobile);
}

/**
 * Validate person full name (letters, spaces, dots, hyphens, apostrophes)
 *
 * @param string $name
 * @return bool
 */
function validate_name($name) {
    if (strlen($name) < 2 || strlen($name) > 60) {
        return false;
    }
    return (bool)preg_match("/^[A-Za-z]+(?:[ '.-][A-Za-z]+)*$/", $name);
}

/**
 * Safely read a JSON file
 *
 * @param string $filePath
 * @param mixed $default
 * @return mixed
 */
function read_json_file($filePath, $default = []) {
    if (!file_exists($filePath)) {
        return $default;
    }
    $content = @file_get_contents($filePath);
    if ($content === false || trim($content) === '') {
        return $default;
    }
    $decoded = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $default;
    }
    return $decoded;
}

/**
 * Safely write data to a JSON file with exclusive file lock
 *
 * @param string $filePath
 * @param mixed $data
 * @return bool
 */
function write_json_file($filePath, $data) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($jsonContent === false) {
        return false;
    }
    return file_put_contents($filePath, $jsonContent, LOCK_EX) !== false;
}

/**
 * Send standard JSON response (for AJAX / Fetch API requests)
 *
 * @param bool $success
 * @param string $message
 * @param array $data
 * @param array $errors
 * @param int $statusCode
 */
function send_json_response($success, $message, $data = [], $errors = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
        'errors'  => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Detect if incoming request is an AJAX/Fetch JSON request
 *
 * @return bool
 */
function is_ajax_request() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        return true;
    }
    return false;
}
