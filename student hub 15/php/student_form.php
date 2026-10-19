<?php
/**
 * Student Hub - Add / Edit Student Form (Practical 11, admin only)
 * Presentation only: saving lives in php/process_student.php.
 * Role is never editable here (prevents privilege escalation).
 */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

$errors = $_SESSION['student_form_errors'] ?? [];
$old = $_SESSION['student_form_old'] ?? [];
unset($_SESSION['student_form_errors'], $_SESSION['student_form_old']);

$editingId = null;
$record = null;

// Edit mode: load the existing row for sticky defaults (prepared SELECT).
if (isset($_GET['id'])) {
    if (!ctype_digit((string)$_GET['id'])) {
        $_SESSION['student_errors'] = ['Invalid student reference.'];
        header('Location: students.php');
        exit;
    }
    $editingId = (int)$_GET['id'];
    $mysqli = getMysqliConnection();
    if ($mysqli === null) {
        $_SESSION['student_errors'] = ['Student records are temporarily unavailable.'];
        header('Location: students.php');
        exit;
    }
    try {
        $stmt = $mysqli->prepare(
            'SELECT `student_id`, `full_name`, `email`, `mobile`, `course`,
                    `academic_year`, `gender`, `department`, `semester`
             FROM `students` WHERE `student_id` = ? LIMIT 1'
        );
        $stmt->bind_param('i', $editingId);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] student_form load error: ' . $e->getMessage());
        $record = null;
    }
    if (!$record) {
        $_SESSION['student_errors'] = ['Student record not found (ID: ' . $editingId . ').'];
        header('Location: students.php');
        exit;
    }
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Sticky value: flashed input wins, else the loaded row, else empty.
function old_val($key) {
    global $old, $record;
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    return $record[$key] ?? '';
}

$courses = ['BCA', 'BBA', 'B.Tech', 'Other'];
$years = ['First Year', 'Second Year', 'Third Year', 'Final Year'];
$genders = ['Male', 'Female', 'Prefer not to say'];
$isEdit = ($editingId !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - <?= $isEdit ? 'Edit' : 'Add' ?> Student (Practical 11)</title>
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
</head>
<body>

    <header class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-actions">
                <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
            </div>
            <a href="../pages/home.html" class="nav-brand">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
                <span>STUDENT HUB</span>
            </a>
            <ul class="nav-links">
                <li><a href="../pages/home.html" class="nav-link">Home</a></li>
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="students.php" class="nav-link active">Students</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 640px; margin: 2rem auto;">
        <h1 class="page-title"><?= $isEdit ? 'Edit Student #' . h($editingId) : 'Add Student' ?></h1>
        <p class="page-subtitle"><?= $isEdit ? 'Update the student details below.' : 'Create a new student record.' ?> Password is <?= $isEdit ? 'optional when editing (leave blank to keep the current one).' : 'required for new accounts.' ?></p>

        <?php if (!empty($errors)): ?>
            <div role="alert" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    <?php foreach ($errors as $msg): ?>
                        <li><?= h($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="process_student.php" novalidate>
                <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'add' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="student_id" value="<?= h($editingId) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="sf-name">Full Name</label>
                    <input type="text" id="sf-name" name="fullname" class="form-control" required minlength="2" maxlength="60" value="<?= h(old_val('full_name')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sf-email">Email Address</label>
                    <input type="email" id="sf-email" name="email" class="form-control" required value="<?= h(old_val('email')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sf-mobile">Mobile Number</label>
                    <input type="tel" id="sf-mobile" name="mobile" class="form-control" required inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" value="<?= h(old_val('mobile')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sf-course">Course</label>
                    <select id="sf-course" name="course" class="form-control" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= h($c) ?>"<?= old_val('course') === $c ? ' selected' : '' ?>><?= h($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sf-year">Academic Year</label>
                    <select id="sf-year" name="year" class="form-control" required>
                        <option value="">Select year</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= h($y) ?>"<?= old_val('academic_year') === $y ? ' selected' : '' ?>><?= h($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <fieldset class="form-group gender-group">
                    <legend class="form-label">Gender</legend>
                    <div class="radio-options">
                        <?php foreach ($genders as $g): ?>
                            <label><input type="radio" name="gender" value="<?= h($g) ?>"<?= old_val('gender') === $g ? ' checked' : '' ?> required> <?= h($g) ?></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="form-group">
                    <label class="form-label" for="sf-password">Password<?= $isEdit ? ' (blank = keep current)' : '' ?></label>
                    <input type="password" id="sf-password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Student' : 'Add Student' ?></button>
                    <a href="students.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copy">&copy; 2026 Student Hub. All rights reserved.</p>
            <div class="social-links">
                <a href="#" class="social-link">Facebook</a>
                <a href="#" class="social-link">Twitter</a>
                <a href="#" class="social-link">Instagram</a>
                <a href="#" class="social-link">LinkedIn</a>
            </div>
        </div>
    </footer>

</body>
</html>
