<?php
/**
 * Student Hub - Student Registration Processor
 * Handles sanitization, validation, JSON storage, and response messaging.
 */

require_once __DIR__ . '/helpers.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_ajax_request()) {
        send_json_response(false, 'Invalid request method. POST expected.', [], ['method' => 'Only POST allowed'], 405);
    } else {
        header('Location: ../pages/register.php');
        exit;
    }
}

// Support JSON payload or Form data
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
// 2. SERVER-SIDE VALIDATION
// -------------------------------------------------------------
$errors = [];

// Full Name validation
if (empty($fullname)) {
    $errors['fullname'] = 'Full name is required.';
} elseif (!validate_name($fullname)) {
    $errors['fullname'] = 'Name must be between 2 and 60 characters and contain letters, spaces, hyphens only.';
}

// Email validation
if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!validate_email($email)) {
    $errors['email'] = 'Please enter a valid email address (e.g., student@college.edu).';
}

// Mobile validation
if (empty($mobile)) {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!validate_mobile($mobile)) {
    $errors['mobile'] = 'Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.';
}

// Course validation
$allowedCourses = ['BCA', 'BBA', 'B.Tech', 'Other'];
if (empty($course)) {
    $errors['course'] = 'Please select your course.';
} elseif (!in_array($course, $allowedCourses, true)) {
    $errors['course'] = 'Selected course is not recognized.';
}

// Year validation
$allowedYears = ['First Year', 'Second Year', 'Third Year', 'Final Year'];
if (empty($year)) {
    $errors['year'] = 'Please select your academic year.';
} elseif (!in_array($year, $allowedYears, true)) {
    $errors['year'] = 'Selected year is not valid.';
}

// Gender validation
$allowedGenders = ['Male', 'Female', 'Prefer not to say'];
if (empty($gender)) {
    $errors['gender'] = 'Please select your gender.';
} elseif (!in_array($gender, $allowedGenders, true)) {
    $errors['gender'] = 'Selected gender option is invalid.';
}

// Password validation
if (empty($password)) {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters long.';
}

// Password confirmation validation
if (empty($confirm)) {
    $errors['confirm_password'] = 'Please confirm your password.';
} elseif ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

// Terms acceptance validation
if (!$terms && (!isset($inputData['terms']) || $inputData['terms'] === 'false')) {
    $errors['terms'] = 'You must accept the terms and conditions to register.';
}

// -------------------------------------------------------------
// 3. DUPLICATE CHECK & JSON DATA RETRIEVAL
// -------------------------------------------------------------
$jsonFilePath = dirname(__DIR__) . '/data/students.json';
$existingStudents = read_json_file($jsonFilePath, []);

if (empty($errors)) {
    foreach ($existingStudents as $student) {
        if (isset($student['email']) && strtolower($student['email']) === strtolower($email)) {
            $errors['email'] = 'An account with this email address already exists.';
            break;
        }
    }
}

// -------------------------------------------------------------
// 4. HANDLE VALIDATION ERRORS
// -------------------------------------------------------------
if (!empty($errors)) {
    if (is_ajax_request()) {
        send_json_response(false, 'Validation failed. Please correct the highlighted errors.', [], $errors, 422);
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old'] = [
            'fullname' => $fullname,
            'email'    => $email,
            'mobile'   => $mobile,
            'course'   => $course,
            'year'     => $year,
            'gender'   => $gender
        ];
        header('Location: ../pages/register.php');
        exit;
    }
}

// -------------------------------------------------------------
// 5. CONSTRUCT RECORD & STORE IN JSON
// -------------------------------------------------------------
// Derive department based on course
$department = 'Computer Science';
if ($course === 'BCA') {
    $department = 'Computer Applications';
} elseif ($course === 'BBA') {
    $department = 'Business Administration';
} elseif ($course === 'Other') {
    $department = 'General Studies';
}

// Derive semester based on year
$semester = '1';
if ($year === 'First Year') {
    $semester = '1';
} elseif ($year === 'Second Year') {
    $semester = '3';
} elseif ($year === 'Third Year') {
    $semester = '5';
} elseif ($year === 'Final Year') {
    $semester = '7';
}

// Generate unique student ID (e.g. STU-1008 or max + 1)
$maxIdNum = 1000;
foreach ($existingStudents as $s) {
    if (isset($s['id']) && preg_match('/STU-(\d+)/', $s['id'], $matches)) {
        $num = (int)$matches[1];
        if ($num > $maxIdNum) {
            $maxIdNum = $num;
        }
    }
}
$newStudentId = 'STU-' . ($maxIdNum + 1);

// Build structured student record
$newStudent = [
    'id'             => $newStudentId,
    'name'           => $fullname,
    'department'     => $department,
    'course'         => $course,
    'year'           => $year,
    'semester'       => $semester,
    'email'          => $email,
    'mobile'         => $mobile,
    'city'           => 'Campus Resident',
    'gender'         => $gender,
    'registeredDate' => date('Y-m-d'),
    'bio'            => "Enrolled in {$course} ({$year}). Passionate student hub member and campus learner.",
    'skills'         => ['Web Development', 'Problem Solving', 'Teamwork', 'Communication'],
    'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
    'created_at'     => date('Y-m-d H:i:s')
];

// Append new student record to list
array_unshift($existingStudents, $newStudent);

// Write to JSON file format
$saved = write_json_file($jsonFilePath, $existingStudents);

if (!$saved) {
    if (is_ajax_request()) {
        send_json_response(false, 'Server error: Unable to save record to JSON file.', [], ['system' => 'Failed to write JSON data.'], 500);
    } else {
        $_SESSION['form_errors'] = ['system' => 'Failed to save record to data/students.json'];
        header('Location: ../pages/register.php');
        exit;
    }
}

// Clean sanitized record without password hash for client response
$publicRecord = $newStudent;
unset($publicRecord['password_hash']);

// -------------------------------------------------------------
// 6. SUCCESS RESPONSE
// -------------------------------------------------------------
if (is_ajax_request()) {
    send_json_response(true, 'Registration successful! Your profile record has been stored in JSON format.', [
        'student' => $publicRecord,
        'redirect' => 'profile.html'
    ]);
} else {
    unset($_SESSION['form_old']);
    unset($_SESSION['form_errors']);
    $_SESSION['flash_success'] = "Registration successful! Welcome {$fullname}. Your record (ID: {$newStudentId}) has been stored in JSON file format.";
    $_SESSION['current_student'] = $publicRecord;
    header('Location: ../pages/register.php?success=1');
    exit;
}
