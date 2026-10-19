<?php
/**
 * Student Hub - Login Processor (Practical 10)
 * ----------------------------------------------------------------------------
 * Authenticates a user against the `students` table (Practical 8/9 schema):
 *   - MySQLi PREPARED SELECT by email (user input is bound, never in SQL).
 *   - password_verify() against the stored bcrypt hash (never direct compare).
 *   - Dummy-hash verify when the email is unknown, so success and failure
 *     take the same code path and reveal nothing (generic error message).
 *   - session_regenerate_id(true) on success (fixation protection).
 *   - Role-based redirect: admin -> admin.php, student -> dashboard.php.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();

// Only allow POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Support form data (and JSON payloads, same convention as other processors).
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

$email    = isset($inputData['email'])    ? sanitize_email($inputData['email']) : '';
$password = isset($inputData['password']) ? (string)$inputData['password']      : '';

// Basic backend validation (format only - existence is never disclosed).
$errors = [];
if ($email === '' || !validate_email($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if ($password === '') {
    $errors['password'] = 'Please enter your password.';
}

if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_old'] = ['email' => $email];
    header('Location: login.php');
    exit;
}

// Database must be reachable; failures get a generic message (no internals).
$mysqli = getMysqliConnection();
if ($mysqli === null) {
    error_log('[studenthub] process_login: database unavailable.');
    $_SESSION['login_errors'] = ['system' => 'Login service is temporarily unavailable. Please try again later.'];
    $_SESSION['login_old'] = ['email' => $email];
    header('Location: login.php');
    exit;
}

try {
    // Prepared SELECT: the email is bound, never concatenated into SQL.
    $stmt = $mysqli->prepare(
        'SELECT `student_id`, `full_name`, `email`, `password_hash`, `role`
         FROM `students`
         WHERE `email` = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Always run password_verify (dummy hash when unknown) so the failure
    // path is identical whether the email exists or not.
    $hashToCheck = $user ? $user['password_hash'] : '$2y$10$invalidinvalidinvalidinvalidinvalidinval1';
    $passwordOk = password_verify($password, $hashToCheck);

    if (!$user || !$passwordOk) {
        // Generic message: never discloses which part was wrong.
        $_SESSION['login_errors'] = ['auth' => 'Invalid email or password. Please try again.'];
        $_SESSION['login_old'] = ['email' => $email];
        header('Location: login.php');
        exit;
    }

    // Success: establish the session and redirect by role.
    auth_login($user);
    unset($_SESSION['login_errors'], $_SESSION['login_old']);
    $_SESSION['flash_success'] = 'Login successful! Welcome back, ' . $user['full_name'] . '.';

    if ($user['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
} catch (mysqli_sql_exception $e) {
    error_log('[studenthub] process_login DB error ' . $e->getCode() . ': ' . $e->getMessage());
    $_SESSION['login_errors'] = ['system' => 'Login service is temporarily unavailable. Please try again later.'];
    $_SESSION['login_old'] = ['email' => $email];
    header('Location: login.php');
    exit;
}
