<?php
/**
 * StudentHub - Database Connection Test (Practical 8)
 * ----------------------------------------------------------------------------
 * Demonstrates:
 *   1. PDO connection via config/db.php (exception error mode).
 *   2. A parameterized SELECT using a prepared statement (no string
 *      concatenation of user input into SQL).
 *   3. Graceful handling when MySQL is unreachable (no credentials shown).
 */
require_once dirname(__DIR__) . '/config/db.php';

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Email lookup input: validated, never concatenated into SQL.
$lookupEmail = trim($_GET['email'] ?? 'aarav.sharma@college.edu');
if ($lookupEmail === '' || !filter_var($lookupEmail, FILTER_VALIDATE_EMAIL)) {
    $lookupEmail = 'aarav.sharma@college.edu';
}

$pdo = getDbConnection();
$dbError = ($pdo === null);

$counts = ['students' => null, 'events' => null, 'registrations' => null];
$foundStudent = null;
$queryError = null;

if (!$dbError) {
    try {
        // Simple aggregate reads for the status panel.
        foreach (array_keys($counts) as $table) {
            $counts[$table] = (int)$pdo->query("SELECT COUNT(*) AS c FROM `{$table}`")->fetch()['c'];
        }

        // Parameterized SELECT demo: named placeholder, value bound by PDO.
        $stmt = $pdo->prepare(
            'SELECT `student_id`, `full_name`, `email`, `mobile`, `course`,
                    `academic_year`, `gender`, `department`, `semester`, `registered_at`
             FROM `students`
             WHERE `email` = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $lookupEmail]);
        $foundStudent = $stmt->fetch(); // false when no row matches
    } catch (PDOException $e) {
        error_log('[studenthub] db_test query failed: ' . $e->getMessage());
        $queryError = 'A database query failed. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Database Test (Practical 8)</title>
    <meta name="description" content="Practical 8: PDO MySQL connection test and prepared-statement demo.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
</head>
<body>

    <!-- Header Navigation Bar (same convention as php/register.php) -->
    <header class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-actions">
                <a href="../pages/login.html" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
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
                <li><a href="../pages/faq.html" class="nav-link">FAQ</a></li>
                <li><a href="../pages/about.html" class="nav-link">About</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="db_test.php" class="nav-link active">DB Test</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 900px; margin: 2rem auto;">
        <h1 class="page-title">MySQL Connection Test <span style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700;">Practical 8 · PDO</span></h1>
        <p class="page-subtitle">Verifies <code>config/db.php</code> connectivity and a prepared-statement SELECT.</p>

        <?php if ($dbError): ?>
            <div class="alert alert-danger" role="alert" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                <strong>Database unavailable.</strong>
                Could not connect to the <code>studenthub</code> database.
                Start MySQL in XAMPP and import <code>database/studenthub.sql</code> via phpMyAdmin, then reload this page.
            </div>
        <?php else: ?>
            <div class="alert alert-success" role="status" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                <strong>Connected successfully</strong> to the <code>studenthub</code> database using PDO.
            </div>

            <?php if ($queryError): ?>
                <div class="alert alert-danger" role="alert" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <?= h($queryError) ?>
                </div>
            <?php else: ?>
                <!-- Table row counts -->
                <div class="card">
                    <h3 style="margin-top: 0;">Tables</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Table</th><th>Rows</th></tr></thead>
                            <tbody>
                                <?php foreach ($counts as $table => $n): ?>
                                    <tr><td><code><?= h($table) ?></code></td><td><?= h($n) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Prepared-statement demo -->
                <div class="card">
                    <h3 style="margin-top: 0;">Prepared-statement SELECT demo</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Query: <code>SELECT ... FROM students WHERE email = :email LIMIT 1</code>
                        (value is bound, never concatenated).
                    </p>
                    <form method="GET" action="db_test.php" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        <input type="email" name="email" class="form-control" style="flex: 1; min-width: 240px;" value="<?= h($lookupEmail) ?>" required>
                        <button type="submit" class="btn btn-primary">Look up</button>
                    </form>
                    <?php if ($foundStudent): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <tbody>
                                    <tr><th>ID</th><td><?= h($foundStudent['student_id']) ?></td></tr>
                                    <tr><th>Name</th><td><?= h($foundStudent['full_name']) ?></td></tr>
                                    <tr><th>Email</th><td><?= h($foundStudent['email']) ?></td></tr>
                                    <tr><th>Mobile</th><td><?= h($foundStudent['mobile']) ?></td></tr>
                                    <tr><th>Course</th><td><?= h($foundStudent['course']) ?> &bull; <?= h($foundStudent['academic_year']) ?></td></tr>
                                    <tr><th>Department</th><td><?= h($foundStudent['department']) ?> (Sem <?= h($foundStudent['semester']) ?>)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted);">No student found with email <code><?= h($lookupEmail) ?></code>.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
