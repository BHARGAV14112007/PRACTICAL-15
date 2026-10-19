<?php
/**
 * Student Hub - Contact Form Processor
 * Handles sanitization, validation, JSON storage of queries/messages, and response messaging.
 */

require_once __DIR__ . '/helpers.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_ajax_request()) {
        send_json_response(false, 'Invalid request method. POST expected.', [], ['method' => 'Only POST allowed'], 405);
    } else {
        header('Location: ../pages/contact.php');
        exit;
    }
}

// Support JSON payload or standard Form data
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}
if (empty($inputData)) {
    $inputData = $_POST;
}

// -------------------------------------------------------------
// 1. SANITIZE INPUTS (Server-Side Sanitization)
// -------------------------------------------------------------
$name    = isset($inputData['name'])    ? sanitize_string($inputData['name'])       : '';
$email   = isset($inputData['email'])   ? sanitize_email($inputData['email'])       : '';
$subject = isset($inputData['subject']) ? sanitize_string($inputData['subject'])    : '';
$message = isset($inputData['message']) ? sanitize_string($inputData['message'])    : '';

// -------------------------------------------------------------
// 2. SERVER-SIDE VALIDATION
// -------------------------------------------------------------
$errors = [];

// Name validation
if (empty($name)) {
    $errors['name'] = 'Your name is required.';
} elseif (!validate_name($name)) {
    $errors['name'] = 'Please enter a valid name (2-60 characters, letters only).';
}

// Email validation
if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!validate_email($email)) {
    $errors['email'] = 'Please enter a valid email address (e.g., student@college.edu).';
}

// Subject validation
if (empty($subject)) {
    $errors['subject'] = 'Subject is required.';
} elseif (strlen($subject) < 3 || strlen($subject) > 120) {
    $errors['subject'] = 'Subject must be between 3 and 120 characters.';
}

// Message validation
if (empty($message)) {
    $errors['message'] = 'Message content cannot be empty.';
} elseif (strlen($message) < 10) {
    $errors['message'] = 'Message must be at least 10 characters long.';
} elseif (strlen($message) > 3000) {
    $errors['message'] = 'Message exceeds maximum length of 3000 characters.';
}

// -------------------------------------------------------------
// 3. HANDLE VALIDATION ERRORS
// -------------------------------------------------------------
if (!empty($errors)) {
    if (is_ajax_request()) {
        send_json_response(false, 'Validation failed. Please correct the highlighted errors.', [], $errors, 422);
    } else {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_old'] = [
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message
        ];
        header('Location: ../pages/contact.php');
        exit;
    }
}

// -------------------------------------------------------------
// 4. CONSTRUCT CONTACT RECORD & STORE IN JSON
// -------------------------------------------------------------
$jsonFilePath = dirname(__DIR__) . '/data/contacts.json';
$existingMessages = read_json_file($jsonFilePath, []);

// Generate unique message ticket ID (e.g., MSG-1001)
$maxIdNum = 1000;
foreach ($existingMessages as $msg) {
    if (isset($msg['id']) && preg_match('/MSG-(\d+)/', $msg['id'], $matches)) {
        $num = (int)$matches[1];
        if ($num > $maxIdNum) {
            $maxIdNum = $num;
        }
    }
}
$newMessageId = 'MSG-' . ($maxIdNum + 1);

// Client IP detection safely
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Build structured message record
$newRecord = [
    'id'          => $newMessageId,
    'name'        => $name,
    'email'       => $email,
    'subject'     => $subject,
    'message'     => $message,
    'status'      => 'New',
    'submittedAt' => date('Y-m-d H:i:s'),
    'ipAddress'   => $clientIp
];

// Append new message record
array_unshift($existingMessages, $newRecord);

// Write to JSON file format with lock
$saved = write_json_file($jsonFilePath, $existingMessages);

if (!$saved) {
    if (is_ajax_request()) {
        send_json_response(false, 'Server error: Unable to store contact record in JSON file.', [], ['system' => 'File write error.'], 500);
    } else {
        $_SESSION['contact_errors'] = ['system' => 'Failed to save record to data/contacts.json'];
        header('Location: ../pages/contact.php');
        exit;
    }
}

// -------------------------------------------------------------
// 5. SUCCESS RESPONSE
// -------------------------------------------------------------
if (is_ajax_request()) {
    send_json_response(true, "Thank you, {$name}! Your message has been received (Ticket: {$newMessageId}) and stored in JSON format. Our support team will get back to you shortly.", [
        'contact' => $newRecord
    ]);
} else {
    unset($_SESSION['contact_old']);
    unset($_SESSION['contact_errors']);
    $_SESSION['contact_success'] = "Thank you, {$name}! Your message has been received (Ticket: {$newMessageId}) and saved to the JSON database. We will reply to {$email} shortly.";
    header('Location: ../pages/contact.php?success=1');
    exit;
}
