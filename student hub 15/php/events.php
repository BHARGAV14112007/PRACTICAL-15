<?php
/**
 * Student Hub - Event Management (Practical 12: admin list)
 * ----------------------------------------------------------------------------
 * Admin-only overview of all events with poster thumbnails. Add/Edit/Delete
 * actions live here; enforcement is server-side in process_event.php and
 * event_delete.php. All reads use MySQLi prepared statements.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

$user = $_SESSION['user'];

$success = $_SESSION['event_success'] ?? null;
$errors = $_SESSION['event_errors'] ?? [];
unset($_SESSION['event_success'], $_SESSION['event_errors']);

$rows = [];
$dbError = null;

$mysqli = getMysqliConnection();
if ($mysqli === null) {
    $dbError = 'Event records are temporarily unavailable. Please try again later.';
} else {
    try {
        $result = $mysqli->query(
            'SELECT `event_id`, `title`, `category`, `event_date`, `venue`,
                    `organizer`, `seats_available`, `status`, `poster_path`,
                    `created_at`
             FROM `events`
             ORDER BY `event_date` ASC, `event_id` ASC'
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] events list DB error: ' . $e->getMessage());
        $dbError = 'Event records are temporarily unavailable. Please try again later.';
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
    <title>Student Hub - Manage Events (Practical 12)</title>
    <meta name="description" content="Practical 12: admin event management with poster uploads.">
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
            <?php render_nav('events'); ?>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 1100px; margin: 2rem auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <h1 class="page-title">Manage Events <span style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700;">Practical 12 · Posters</span></h1>
                <p class="page-subtitle"><?= h(count($rows)) ?> event(s). Posters: JPEG / PNG / WebP, max 2 MB.</p>
            </div>
            <a href="event_form.php" class="btn btn-primary">+ Add Event</a>
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

        <?php if ($dbError): ?>
            <div role="alert" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 1rem; border-radius: 8px;"><?= h($dbError) ?></div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Poster</th><th>Title</th><th>Category</th><th>Date</th><th>Venue</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No events found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= h($r['event_id']) ?></td>
                                        <td>
                                            <?php if (!empty($r['poster_path'])): ?>
                                                <img src="../<?= h($r['poster_path']) ?>" alt="Poster for <?= h($r['title']) ?>" style="width: 64px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.8rem;">No poster</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= h($r['title']) ?></strong></td>
                                        <td><?= h($r['category']) ?></td>
                                        <td><?= h($r['event_date']) ?></td>
                                        <td><?= h($r['venue']) ?></td>
                                        <td><?= h($r['status']) ?></td>
                                        <td style="white-space: nowrap;">
                                            <a href="event_form.php?id=<?= h($r['event_id']) ?>" class="btn btn-sm btn-outline">Edit</a>
                                            <a href="event_delete.php?id=<?= h($r['event_id']) ?>" class="btn btn-sm btn-danger">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
