<?php
/**
 * Student Hub - Admin Dashboard (Practical 14)
 * ----------------------------------------------------------------------------
 * Admin-only. Every number on this page comes from live database queries -
 * nothing is hardcoded. Shows user/event/registration statistics, the latest
 * event registrations, and the recent audit log. Navigation is role-based
 * (php/nav.php); authorization stays server-side (require_role).
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

$user = $_SESSION['user'];

$stats = ['users' => 0, 'students' => 0, 'events' => 0, 'registrations' => 0];
$latestRegs = [];
$auditRows = [];
$dbError = null;

$mysqli = getMysqliConnection();
if ($mysqli === null) {
    $dbError = 'Dashboard data is temporarily unavailable. Please try again later.';
} else {
    try {
        $stats['users'] = (int)$mysqli->query('SELECT COUNT(*) AS c FROM `students`')->fetch_assoc()['c'];
        $stats['students'] = (int)$mysqli->query("SELECT COUNT(*) AS c FROM `students` WHERE `role` = 'student'")->fetch_assoc()['c'];
        $stats['events'] = (int)$mysqli->query('SELECT COUNT(*) AS c FROM `events`')->fetch_assoc()['c'];
        $stats['registrations'] = (int)$mysqli->query('SELECT COUNT(*) AS c FROM `registrations`')->fetch_assoc()['c'];

        $res = $mysqli->query(
            'SELECT r.`registration_id`, r.`status`, r.`registered_at`,
                    s.`full_name` AS student_name, s.`email` AS student_email,
                    e.`title` AS event_title, e.`event_date`
             FROM `registrations` r
             JOIN `students` s ON s.`student_id` = r.`student_id`
             JOIN `events` e ON e.`event_id` = r.`event_id`
             ORDER BY r.`registered_at` DESC, r.`registration_id` DESC
             LIMIT 5'
        );
        $latestRegs = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        $res = $mysqli->query(
            'SELECT `log_id`, `actor_email`, `action`, `target_type`,
                    `target_id`, `details`, `created_at`
             FROM `audit_logs`
             ORDER BY `created_at` DESC, `log_id` DESC
             LIMIT 10'
        );
        $auditRows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] admin_dashboard DB error: ' . $e->getMessage());
        $dbError = 'Dashboard data is temporarily unavailable. Please try again later.';
    } catch (Error $e) {
        // e.g. audit_logs table missing before the Practical 14 migration.
        error_log('[studenthub] admin_dashboard error: ' . $e->getMessage());
        $dbError = 'Dashboard data is temporarily unavailable. Please try again later.';
    }
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Admin Dashboard (Practical 14)</title>
    <meta name="description" content="Practical 14: database-driven admin dashboard with audit log.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
</head>
<body>

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
            <?php render_nav('admin'); ?>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 1100px; margin: 2rem auto;">
        <h1 class="page-title">Administrator Control Center <span style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700;">Practical 14 · Live DB</span></h1>
        <p class="page-subtitle">Welcome back, <?= h($user['name']) ?>. All figures below are queried live from MySQL.</p>

        <?php if ($dbError): ?>
            <div role="alert" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;"><?= h($dbError) ?></div>
        <?php else: ?>
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value"><?= h($stats['users']) ?></div><div class="stat-label">Total Users</div></div>
                <div class="stat-card"><div class="stat-value"><?= h($stats['students']) ?></div><div class="stat-label">Students</div></div>
                <div class="stat-card"><div class="stat-value"><?= h($stats['events']) ?></div><div class="stat-label">Events</div></div>
                <div class="stat-card"><div class="stat-value"><?= h($stats['registrations']) ?></div><div class="stat-label">Registrations</div></div>
            </div>

            <!-- Quick links -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.75rem;">
                <a href="students.php" class="btn btn-primary">Manage Students</a>
                <a href="events.php" class="btn btn-primary">Manage Events</a>
                <a href="events_live.php" class="btn btn-outline">Live Events (API)</a>
                <a href="db_test.php" class="btn btn-outline">DB Test</a>
            </div>

            <!-- Latest registrations -->
            <div class="card">
                <h3 style="margin-top: 0;">Latest Registrations</h3>
                <?php if (empty($latestRegs)): ?>
                    <p style="color: var(--text-muted);">No registrations recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>#</th><th>Student</th><th>Event</th><th>Status</th><th>Registered At</th></tr></thead>
                            <tbody>
                                <?php foreach ($latestRegs as $r): ?>
                                    <tr>
                                        <td><?= h($r['registration_id']) ?></td>
                                        <td><strong><?= h($r['student_name']) ?></strong><br><span style="font-size: 0.8rem; color: var(--text-muted);"><?= h($r['student_email']) ?></span></td>
                                        <td><?= h($r['event_title']) ?><br><span style="font-size: 0.8rem; color: var(--text-muted);"><?= h($r['event_date']) ?></span></td>
                                        <td><?= h($r['status']) ?></td>
                                        <td><?= h($r['registered_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent audit log -->
            <div class="card">
                <h3 style="margin-top: 0;">Recent Admin Activity</h3>
                <?php if (empty($auditRows)): ?>
                    <p style="color: var(--text-muted);">No audited actions yet. Student and event changes made from this point on will appear here.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>#</th><th>Actor</th><th>Action</th><th>Target</th><th>Details</th><th>At</th></tr></thead>
                            <tbody>
                                <?php foreach ($auditRows as $a): ?>
                                    <tr>
                                        <td><?= h($a['log_id']) ?></td>
                                        <td><?= h($a['actor_email']) ?></td>
                                        <td><code><?= h($a['action']) ?></code></td>
                                        <td><?= h(trim(($a['target_type'] ?? '') . ' #' . ($a['target_id'] ?? ''), ' #')) ?></td>
                                        <td><?= h($a['details']) ?></td>
                                        <td><?= h($a['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
