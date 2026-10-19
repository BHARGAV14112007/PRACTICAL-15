<?php
/**
 * Student Hub - MySQL Registration Processor (Practical 9)
 * ----------------------------------------------------------------------------
 * Secure user registration backed by MySQL (Practical 8 schema: `students`).
 *
 * - Backend validation is mandatory (never trusts HTML/JS validation).
 * - Duplicate email is checked with a MySQLi PREPARED SELECT before insert.
 * - Passwords are stored only via password_hash() - never plain text.
 * - All database reads/writes use MySQLi PREPARED STATEMENTS with bound
 *   parameters. No user input is ever concatenated into SQL.
 * - Responses mirror php/process_register.php (session flash + PRG for normal
 *   POST, JSON envelope for AJAX) so the existing frontend keeps working.
 */

require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__) . '/config/db.php';

// Only allow POST requests (same convention as the JSON processor).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_ajax_request()) {
        send_json_response(false, 'Invalid request method. POST expected.', [], ['method' => 'Only POST allowed'], 405);
    } else {
        header('Location: register_mysql.php');
        exit;
    }
}

// Support JSON payload or form data (same convention as the JSON processor).
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
// 1. SANITIZE INPUTS (same rules as Practical 7 processor)
// -------------------------------------------------------------
$fullname = isset($inputData['fullname']) ? sanitize_string($inputData['fullname']) : '';
$email    = isset($inputData['email'])    ? sanitize_email($inputData['email'])       : '';
$mobile   = isset($inputData['mobile'])   ? preg_replace('/[^0-9]/', '', (string)$inputData['mobile']) : '';
$course   = isset($inputData['course'])   ? sanitize_string($inputData['course'])     : '';
$year     = isset($inputData['year'])     ? sanitize_string($inputData['year'])       : '';
$gender   = isset($inputData['gender'])   ? sanitize_string($inputData['gender'])     : '';
$password = isset($inputData['password']) ? (string)$inputData['password']            : '';
$confirm  = isset($inputData['confirm_password']) ? (string)$inputData['confirm_password'] : '';
$terms    = isset($inputData['terms'])    ? true : false;

// -------------------------------------------------------------
// 2. BACKEND VALIDATION (mandatory - mirrors existing rules)
// -------------------------------------------------------------
$errors = [];

if (empty($fullname)) {
    $errors['fullname'] = 'Full name is required.';
} elseif (!validate_name($fullname)) {
    $errors['fullname'] = 'Name must be between 2 and 60 characters and contain letters, spaces, hyphens only.';
}

if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!validate_email($email)) {
    $errors['email'] = 'Please enter a valid email address (e.g., student@college.edu).';
}

if (empty($mobile)) {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!validate_mobile($mobile)) {
    $errors['mobile'] = 'Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.';
}

$allowedCourses = ['BCA', 'BBA', 'B.Tech', 'Other'];
if (empty($course)) {
    $errors['course'] = 'Please select your course.';
} elseif (!in_array($course, $allowedCourses, true)) {
    $errors['course'] = 'Selected course is not recognized.';
}

$allowedYears = ['First Year', 'Second Year', 'Third Year', 'Final Year'];
if (empty($year)) {
    $errors['year'] = 'Please select your academic year.';
} elseif (!in_array($year, $allowedYears, true)) {
    $errors['year'] = 'Selected year is not valid.';
}

$allowedGenders = ['Male', 'Female', 'Prefer not to say'];
if (empty($gender)) {
    $errors['gender'] = 'Please select your gender.';
} elseif (!in_array($gender, $allowedGenders, true)) {
    $errors['gender'] = 'Selected gender option is invalid.';
}

if (empty($password)) {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters long.';
}

if (empty($confirm)) {
    $errors['confirm_password'] = 'Please confirm your password.';
} elseif ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!$terms && (!isset($inputData['terms']) || $inputData['terms'] === 'false')) {
    $errors['terms'] = 'You must accept the terms and conditions to register.';
}

// Sticky values for re-display (passwords are never echoed back).
$formOld = [
    'fullname' => $fullname,
    'email'    => $email,
    'mobile'   => $mobile,
    'course'   => $course,
    'year'     => $year,
    'gender'   => $gender
];

function p9_fail($errors, $formOld) {
    if (is_ajax_request()) {
        send_json_response(false, 'Validation failed. Please correct the highlighted errors.', [], $errors, 422);
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old'] = $formOld;
        header('Location: register_mysql.php');
        exit;
    }
}

if (!empty($errors)) {
    p9_fail($errors, $formOld);
}

// -------------------------------------------------------------
// 3. DATABASE CONNECTION (shared config, MySQLi for Practical 9)
// -------------------------------------------------------------
$mysqli = getMysqliConnection();
if ($mysqli === null) {
    error_log('[studenthub] register_mysql: database unavailable.');
    $dbErrors = ['system' => 'Registration service is temporarily unavailable. Please try again later.'];
    if (is_ajax_request()) {
        send_json_response(false, 'Registration service is temporarily unavailable. Please try again later.', [], $dbErrors, 503);
    } else {
        $_SESSION['form_errors'] = $dbErrors;
        $_SESSION['form_old'] = $formOld;
        header('Location: register_mysql.php');
        exit;
    }
}

try {
    // ---------------------------------------------------------
    // 4. DUPLICATE CHECK - prepared SELECT, email bound (never
    //    concatenated). NOTE: the project has no username field
    //    for student accounts, so email is the account key.
    // ---------------------------------------------------------
    $dupStmt = $mysqli->prepare('SELECT `student_id` FROM `students` WHERE `email` = ? LIMIT 1');
    $dupStmt->bind_param('s', $email);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();
    if ($dupResult->fetch_assoc()) {
        $dupStmt->close();
        p9_fail(['email' => 'An account with this email address already exists.'], $formOld);
    }
    $dupStmt->close();

    // ---------------------------------------------------------
    // 5. DERIVE DEPARTMENT + SEMESTER (same mapping as before)
    // ---------------------------------------------------------
    $department = 'Computer Science';
    if ($course === 'BCA') {
        $department = 'Computer Applications';
    } elseif ($course === 'BBA') {
        $department = 'Business Administration';
    } elseif ($course === 'Other') {
        $department = 'General Studies';
    }

    $semester = 3;
    if ($year === 'First Year') {
        $semester = 1;
    } elseif ($year === 'Second Year') {
        $semester = 3;
    } elseif ($year === 'Third Year') {
        $semester = 5;
    } elseif ($year === 'Final Year') {
        $semester = 7;
    }

    // ---------------------------------------------------------
    // 6. HASH + INSERT - prepared INSERT, all values bound.
    // ---------------------------------------------------------
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insStmt = $mysqli->prepare(
        'INSERT INTO `students`
            (`full_name`, `email`, `mobile`, `course`, `academic_year`,
             `gender`, `password_hash`, `department`, `semester`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insStmt->bind_param(
        'ssssssssi',
        $fullname,
        $email,
        $mobile,
        $course,
        $year,
        $gender,
        $passwordHash,
        $department,
        $semester
    );
    $insStmt->execute();
    $newId = $insStmt->insert_id;
    $insStmt->close();
} catch (mysqli_sql_exception $e) {
    // 1062 = duplicate entry (race between check and insert): friendly message.
    error_log('[studenthub] register_mysql DB error ' . $e->getCode() . ': ' . $e->getMessage());
    if ((int)$e->getCode() === 1062) {
        p9_fail(['email' => 'An account with this email address already exists.'], $formOld);
    }
    $dbErrors = ['system' => 'Could not save your registration. Please try again later.'];
    if (is_ajax_request()) {
        send_json_response(false, 'Could not save your registration. Please try again later.', [], $dbErrors, 500);
    } else {
        $_SESSION['form_errors'] = $dbErrors;
        $_SESSION['form_old'] = $formOld;
        header('Location: register_mysql.php');
        exit;
    }
}

// Public record (password hash never leaves the server).
$publicRecord = [
    'id'         => 'DB-' . $newId,
    'student_id' => $newId,
    'name'       => $fullname,
    'department' => $department,
    'course'     => $course,
    'year'       => $year,
    'semester'   => (string)$semester,
    'email'      => $email,
    'mobile'     => $mobile,
    'gender'     => $gender,
];

// -------------------------------------------------------------
// 7. SUCCESS RESPONSE (same shape as the JSON processor)
// -------------------------------------------------------------
if (is_ajax_request()) {
    send_json_response(true, 'Registration successful! Your account has been saved to the MySQL database.', [
        'student' => $publicRecord,
        'redirect' => 'profile.html'
    ]);
} else {
    unset($_SESSION['form_old']);
    unset($_SESSION['form_errors']);
    $_SESSION['flash_success'] = "Registration successful! Welcome {$fullname}. Your account (ID: DB-{$newId}) has been saved to the MySQL database.";
    $_SESSION['current_student'] = $publicRecord;
    header('Location: register_mysql.php?success=1');
    exit;
}
