<?php
/**
 * Student Hub - Student Records (Practical 11: list + search + filter)
 * ----------------------------------------------------------------------------
 * Viewing requires login (any role). Add/Edit/Delete UI is shown to admins
 * only; the operations themselves are enforced server-side in
 * process_student.php / student_delete.php. All reads use MySQLi prepared
 * statements with bound parameters.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_login();

$user = $_SESSION['user'];
$isAdmin = ($user['role'] === 'admin');

$success = $_SESSION['student_success'] ?? null;
$errors = $_SESSION['student_errors'] ?? [];
unset($_SESSION['student_success'], $_SESSION['student_errors']);

// --- Search / filter / pagination input (validated, never into SQL raw) ----
$q = trim($_GET['q'] ?? '');
if (strlen($q) > 100) {
    $q = substr($q, 0, 100);
}
$courseFilter = trim($_GET['course'] ?? '');
$allowedCourses = ['BCA', 'BBA', 'B.Tech', 'Other'];
if ($courseFilter !== '' && !in_array($courseFilter, $allowedCourses, true)) {
    $courseFilter = '';
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$perPage = 5;

$rows = [];
$totalRows = 0;
$totalPages = 1;
$dbError = null;

$mysqli = getMysqliConnection();
if ($mysqli === null) {
    $dbError = 'Student records are temporarily unavailable. Please try again later.';
} else {
    try {
        // Escape LIKE wildcards so user input is matched literally.
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $likeParam = '%' . $like . '%';

        $where = '';
        $types = '';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (`full_name` LIKE ? ESCAPE \'\\\\\' OR `email` LIKE ? ESCAPE \'\\\\\' OR `course` LIKE ? ESCAPE \'\\\\\')';
            $types .= 'sss';
            $params[] = $likeParam;
            $params[] = $likeParam;
            $params[] = $likeParam;
        }
        if ($courseFilter !== '') {
            $where .= ' AND `course` = ?';
            $types .= 's';
            $params[] = $courseFilter;
        }

        // Total count for pagination (same filters).
        $countSql = 'SELECT COUNT(*) AS c FROM `students` WHERE 1=1' . $where;
        $stmt = $mysqli->prepare($countSql);
        if ($types !== '') {
            bind_all($stmt, $types, $params);
        }
        $stmt->execute();
        $totalRows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // Page of rows (filters + bound LIMIT/OFFSET).
        $listSql = 'SELECT `student_id`, `full_name`, `email`, `mobile`,
                           `course`, `academic_year`, `gender`, `role`, `registered_at`
                    FROM `students` WHERE 1=1' . $where . '
                    ORDER BY `student_id` ASC LIMIT ? OFFSET ?';
        $stmt = $mysqli->prepare($listSql);
        $listTypes = $types . 'ii';
        $listParams = array_merge($params, [$perPage, $offset]);
        bind_all($stmt, $listTypes, $listParams);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] students list DB error: ' . $e->getMessage());
        $dbError = 'Student records are temporarily unavailable. Please try again later.';
    }
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Bind a dynamic parameter list to a MySQLi statement. Values are passed
 * by reference (required by bind_param) via an explicit reference array.
 */
function bind_all($stmt, $types, $params) {
    $refs = [];
    foreach ($params as $i => $value) {
        $refs[$i] = &$params[$i];
    }
    $stmt->bind_param($types, ...$refs);
}

function page_url($pageNum) {
    global $q, $courseFilter;
    $params = ['page' => $pageNum];
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($courseFilter !== '') {
        $params['course'] = $courseFilter;
    }
    return 'students.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Student Records (Practical 11)</title>
    <meta name="description" content="Practical 11: manage student records with search, filter and pagination.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
</head>
<body>

    <!-- Header Navigation Bar -->
    <header class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-actions">
                <span style="font-size: 0.85rem; font-weight: 600;"><?= h($user['name']) ?> (<?= h($user['role']) ?>)</span>
                <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
            </div>
            <a href="../pages/home.html" class="nav-brand">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
                <span>STUDENT HUB</span>
            </a>
            <?php render_nav('students'); ?>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 1100px; margin: 2rem auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <h1 class="page-title">Student Records <span style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700;">Practical 11 · CRUD</span></h1>
                <p class="page-subtitle"><?= h($totalRows) ?> record(s) found.</p>
            </div>
            <?php if ($isAdmin): ?>
                <a href="student_form.php" class="btn btn-primary">+ Add Student</a>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div role="status" style="background: #dcfce7; border: 1px solid #86efac; color: #15803d; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div role="alert" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    <?php foreach ($errors as $msg): ?>
                        <li><?= h($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Search + filter -->
        <div class="card">
            <form method="GET" action="students.php" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <input type="search" name="q" class="form-control" style="flex: 2; min-width: 200px;" placeholder="Search name, email, or course..." value="<?= h($q) ?>">
                <select name="course" class="form-control" style="flex: 1; min-width: 150px;">
                    <option value="">All courses</option>
                    <?php foreach ($allowedCourses as $c): ?>
                        <option value="<?= h($c) ?>"<?= $courseFilter === $c ? ' selected' : '' ?>><?= h($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($q !== '' || $courseFilter !== ''): ?>
                    <a href="students.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($dbError): ?>
            <div role="alert" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 1rem; border-radius: 8px;"><?= h($dbError) ?></div>
        <?php else: ?>
            <!-- Records table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th><th>Year</th><th>Role</th><?= $isAdmin ? '<th>Actions</th>' : '' ?></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="<?= $isAdmin ? '8' : '7' ?>" style="text-align: center; color: var(--text-muted);">No student records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= h($r['student_id']) ?></td>
                                        <td><strong><?= h($r['full_name']) ?></strong></td>
                                        <td><?= h($r['email']) ?></td>
                                        <td><?= h($r['mobile']) ?></td>
                                        <td><?= h($r['course']) ?></td>
                                        <td><?= h($r['academic_year']) ?></td>
                                        <td><?= h($r['role']) ?></td>
                                        <?php if ($isAdmin): ?>
                                            <td style="white-space: nowrap;">
                                                <a href="student_form.php?id=<?= h($r['student_id']) ?>" class="btn btn-sm btn-outline">Edit</a>
                                                <a href="student_delete.php?id=<?= h($r['student_id']) ?>" class="btn btn-sm btn-danger">Delete</a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination" style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.25rem; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="<?= h(page_url($page - 1)) ?>" class="page-button">&laquo; Prev</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= h(page_url($i)) ?>" class="page-button<?= $i === $page ? ' active' : '' ?>"><?= h($i) ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= h(page_url($page + 1)) ?>" class="page-button">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
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
