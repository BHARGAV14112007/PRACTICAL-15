<?php
/**
 * Student Hub - Delete Student with Confirmation (Practical 11, admin only)
 * ----------------------------------------------------------------------------
 * GET  ?id= : shows the record and asks for explicit confirmation.
 * POST      : performs the delete, but ONLY when the student has no related
 *             event registrations (foreign-key safety: the schema cascades,
 *             so the application blocks instead of deleting indiscriminately).
 */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$mysqli = getMysqliConnection();
if ($mysqli === null) {
    $_SESSION['student_errors'] = ['Service temporarily unavailable. Please try again later.'];
    header('Location: students.php');
    exit;
}

// --- POST: perform the confirmed delete --------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['student_id'] ?? '';
    if (!ctype_digit((string)$id)) {
        $_SESSION['student_errors'] = ['Invalid student reference.'];
        header('Location: students.php');
        exit;
    }
    $id = (int)$id;

    try {
        // The row must still exist.
        $stmt = $mysqli->prepare('SELECT `full_name` FROM `students` WHERE `student_id` = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            $_SESSION['student_errors'] = ["Student record not found (ID: {$id})."];
            header('Location: students.php');
            exit;
        }

        // FK safety: refuse when event registrations reference this student.
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS c FROM `registrations` WHERE `student_id` = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $linked = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        if ($linked > 0) {
            $_SESSION['student_errors'] = [
                "Cannot delete '{$row['full_name']}' (ID: {$id}): "
                . "{$linked} event registration(s) reference this student. "
                . 'Remove or reassign them first.'
            ];
            header('Location: students.php');
            exit;
        }

        $stmt = $mysqli->prepare('DELETE FROM `students` WHERE `student_id` = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['student_success'] = "Student '{$row['full_name']}' (ID: {$id}) deleted successfully.";
        audit_log($mysqli, 'student.delete', 'student', $id, "Deleted student '{$row['full_name']}'.");
        header('Location: students.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] student_delete DB error: ' . $e->getMessage());
        $_SESSION['student_errors'] = ['Could not delete the student record. Please try again later.'];
        header('Location: students.php');
        exit;
    }
}

// --- GET: confirmation screen -------------------------------------------------
$id = $_GET['id'] ?? '';
if (!ctype_digit((string)$id)) {
    $_SESSION['student_errors'] = ['Invalid student reference.'];
    header('Location: students.php');
    exit;
}
$id = (int)$id;

try {
    $stmt = $mysqli->prepare(
        'SELECT `student_id`, `full_name`, `email`, `mobile`, `course`,
                `academic_year`, `role`
         FROM `students` WHERE `student_id` = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $linked = 0;
    if ($record) {
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS c FROM `registrations` WHERE `student_id` = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $linked = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    error_log('[studenthub] student_delete load error: ' . $e->getMessage());
    $record = null;
    $linked = 0;
}

if (!$record) {
    $_SESSION['student_errors'] = ["Student record not found (ID: {$id})."];
    header('Location: students.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Confirm Delete (Practical 11)</title>
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
        <h1 class="page-title">Confirm Delete</h1>
        <p class="page-subtitle">This action cannot be undone.</p>

        <div class="card">
            <div class="table-responsive">
                <table class="data-table">
                    <tbody>
                        <tr><th>ID</th><td><?= h($record['student_id']) ?></td></tr>
                        <tr><th>Name</th><td><?= h($record['full_name']) ?></td></tr>
                        <tr><th>Email</th><td><?= h($record['email']) ?></td></tr>
                        <tr><th>Mobile</th><td><?= h($record['mobile']) ?></td></tr>
                        <tr><th>Course</th><td><?= h($record['course']) ?> &bull; <?= h($record['academic_year']) ?></td></tr>
                        <tr><th>Event registrations</th><td><?= h($linked) ?></td></tr>
                    </tbody>
                </table>
            </div>

            <?php if ($linked > 0): ?>
                <div role="alert" style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; padding: 0.85rem 1rem; border-radius: 8px; margin: 1rem 0; font-size: 0.9rem;">
                    This student has <?= h($linked) ?> event registration(s), so deletion is currently blocked to protect related records.
                </div>
                <a href="students.php" class="btn btn-outline">Back to Records</a>
            <?php else: ?>
                <p style="margin: 1rem 0;">Are you sure you want to permanently delete <strong><?= h($record['full_name']) ?></strong>?</p>
                <form method="POST" action="student_delete.php" style="display: flex; gap: 0.5rem;">
                    <input type="hidden" name="student_id" value="<?= h($record['student_id']) ?>">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="students.php" class="btn btn-outline">Cancel</a>
                </form>
            <?php endif; ?>
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
