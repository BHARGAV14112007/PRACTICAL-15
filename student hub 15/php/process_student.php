<?php
/**
 * Student Hub - Student Save Processor (Practical 11, admin only)
 * ----------------------------------------------------------------------------
 * Handles "add" and "update" POSTs from php/student_form.php:
 *   - Server-side validation (same rules as registration).
 *   - Duplicate-email check via MySQLi prepared SELECT (excluding self).
 *   - INSERT / UPDATE via MySQLi prepared statements (all values bound).
 *   - Password: required on add; optional on update (blank keeps old hash).
 *   - Role is never written here (no privilege escalation).
 *   - PRG pattern with session flash messages back to the form or the list.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'add' && $action !== 'update') {
    $_SESSION['student_errors'] = ['Invalid operation.'];
    header('Location: students.php');
    exit;
}

$studentId = 0;
if ($action === 'update') {
    if (!isset($_POST['student_id']) || !ctype_digit((string)$_POST['student_id'])) {
        $_SESSION['student_errors'] = ['Invalid student reference.'];
        header('Location: students.php');
        exit;
    }
    $studentId = (int)$_POST['student_id'];
}

// --- Sanitize ---------------------------------------------------------------
$fullname = isset($_POST['fullname']) ? sanitize_string($_POST['fullname']) : '';
$email    = isset($_POST['email'])    ? sanitize_email($_POST['email'])       : '';
$mobile   = isset($_POST['mobile'])   ? preg_replace('/[^0-9]/', '', (string)$_POST['mobile']) : '';
$course   = isset($_POST['course'])   ? sanitize_string($_POST['course'])     : '';
$year     = isset($_POST['year'])     ? sanitize_string($_POST['year'])       : '';
$gender   = isset($_POST['gender'])   ? sanitize_string($_POST['gender'])     : '';
$password = isset($_POST['password']) ? (string)$_POST['password']            : '';

// --- Validate ---------------------------------------------------------------
$errors = [];
if ($fullname === '' || !validate_name($fullname)) {
    $errors[] = 'Full name is required (2-60 letters, spaces, hyphens only).';
}
if ($email === '' || !validate_email($email)) {
    $errors[] = 'A valid email address is required.';
}
if ($mobile === '' || !validate_mobile($mobile)) {
    $errors[] = 'Mobile must be a valid 10-digit number starting with 6, 7, 8, or 9.';
}
$allowedCourses = ['BCA', 'BBA', 'B.Tech', 'Other'];
if (!in_array($course, $allowedCourses, true)) {
    $errors[] = 'Please select a valid course.';
}
$allowedYears = ['First Year', 'Second Year', 'Third Year', 'Final Year'];
if (!in_array($year, $allowedYears, true)) {
    $errors[] = 'Please select a valid academic year.';
}
if (!in_array($gender, ['Male', 'Female', 'Prefer not to say'], true)) {
    $errors[] = 'Please select a valid gender option.';
}
if ($action === 'add' && strlen($password) < 8) {
    $errors[] = 'Password is required (minimum 8 characters).';
}
if ($action === 'update' && $password !== '' && strlen($password) < 8) {
    $errors[] = 'New password must be at least 8 characters (or leave blank).';
}

$formBack = $action === 'update' ? 'student_form.php?id=' . $studentId : 'student_form.php';
$old = [
    'full_name' => $fullname, 'email' => $email, 'mobile' => $mobile,
    'course' => $course, 'academic_year' => $year, 'gender' => $gender,
];

if (!empty($errors)) {
    $_SESSION['student_form_errors'] = $errors;
    $_SESSION['student_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}

// --- Database ---------------------------------------------------------------
$mysqli = getMysqliConnection();
if ($mysqli === null) {
    error_log('[studenthub] process_student: database unavailable.');
    $_SESSION['student_form_errors'] = ['Service temporarily unavailable. Please try again later.'];
    $_SESSION['student_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}

try {
    // Duplicate-email check (excluding the row being edited).
    $dupSql = 'SELECT `student_id` FROM `students` WHERE `email` = ?'
        . ($action === 'update' ? ' AND `student_id` <> ?' : '') . ' LIMIT 1';
    $stmt = $mysqli->prepare($dupSql);
    if ($action === 'update') {
        $stmt->bind_param('si', $email, $studentId);
    } else {
        $stmt->bind_param('s', $email);
    }
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($dup) {
        $_SESSION['student_form_errors'] = ['An account with this email address already exists.'];
        $_SESSION['student_form_old'] = $old;
        header('Location: ' . $formBack);
        exit;
    }

    // Department/semester mapping (same convention as registration).
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

    if ($action === 'add') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare(
            'INSERT INTO `students`
                (`full_name`, `email`, `mobile`, `course`, `academic_year`,
                 `gender`, `password_hash`, `department`, `semester`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssssssi',
            $fullname, $email, $mobile, $course, $year,
            $gender, $hash, $department, $semester
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        audit_log($mysqli, 'student.add', 'student', $newId, "Added student '{$fullname}' ({$email}).");
        $_SESSION['student_success'] = "Student '{$fullname}' added successfully (ID: {$newId}).";
    } else {
        // On update the target row must exist.
        $stmt = $mysqli->prepare('SELECT `student_id` FROM `students` WHERE `student_id` = ? LIMIT 1');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) {
            $_SESSION['student_errors'] = ["Student record not found (ID: {$studentId})."];
            header('Location: students.php');
            exit;
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare(
                'UPDATE `students`
                 SET `full_name` = ?, `email` = ?, `mobile` = ?, `course` = ?,
                     `academic_year` = ?, `gender` = ?, `password_hash` = ?,
                     `department` = ?, `semester` = ?
                 WHERE `student_id` = ?'
            );
            $stmt->bind_param(
                'ssssssssii',
                $fullname, $email, $mobile, $course, $year,
                $gender, $hash, $department, $semester, $studentId
            );
        } else {
            $stmt = $mysqli->prepare(
                'UPDATE `students`
                 SET `full_name` = ?, `email` = ?, `mobile` = ?, `course` = ?,
                     `academic_year` = ?, `gender` = ?, `department` = ?,
                     `semester` = ?
                 WHERE `student_id` = ?'
            );
            $stmt->bind_param(
                'sssssssii',
                $fullname, $email, $mobile, $course, $year,
                $gender, $department, $semester, $studentId
            );
        }
        $stmt->execute();
        $stmt->close();
        audit_log($mysqli, 'student.update', 'student', $studentId, "Updated student '{$fullname}' ({$email}).");
        $_SESSION['student_success'] = "Student '{$fullname}' (ID: {$studentId}) updated successfully.";
    }

    unset($_SESSION['student_form_errors'], $_SESSION['student_form_old']);
    header('Location: students.php');
    exit;
} catch (mysqli_sql_exception $e) {
    error_log('[studenthub] process_student DB error ' . $e->getCode() . ': ' . $e->getMessage());
    if ((int)$e->getCode() === 1062) {
        $_SESSION['student_form_errors'] = ['An account with this email address already exists.'];
    } else {
        $_SESSION['student_form_errors'] = ['Could not save the student record. Please try again later.'];
    }
    $_SESSION['student_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}
