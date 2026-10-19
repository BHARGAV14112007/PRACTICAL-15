<?php
/**
 * Student Hub - Delete Event with Confirmation (Practical 12, admin only)
 * ----------------------------------------------------------------------------
 * GET  ?id= : shows the record (with poster) and asks for confirmation.
 * POST      : deletes the row only when no student registrations reference
 *             it (FK safety), then removes the poster file (no orphans).
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
    $_SESSION['event_errors'] = ['Service temporarily unavailable. Please try again later.'];
    header('Location: events.php');
    exit;
}

function p12_remove_poster($relativePath) {
    if (!is_string($relativePath) || $relativePath === '') {
        return;
    }
    $base = realpath(dirname(__DIR__) . '/uploads/posters');
    $target = realpath(dirname(__DIR__) . '/' . $relativePath);
    if ($base === false || $target === false) {
        return;
    }
    if (strpos($target, $base) === 0 && is_file($target)) {
        @unlink($target);
    }
}

// --- POST: perform the confirmed delete ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['event_id'] ?? '';
    if (!ctype_digit((string)$id)) {
        $_SESSION['event_errors'] = ['Invalid event reference.'];
        header('Location: events.php');
        exit;
    }
    $id = (int)$id;

    try {
        $stmt = $mysqli->prepare('SELECT `title`, `poster_path` FROM `events` WHERE `event_id` = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            $_SESSION['event_errors'] = ["Event record not found (ID: {$id})."];
            header('Location: events.php');
            exit;
        }

        // FK safety: refuse when registrations reference this event.
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS c FROM `registrations` WHERE `event_id` = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $linked = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        if ($linked > 0) {
            $_SESSION['event_errors'] = [
                "Cannot delete '{$row['title']}' (ID: {$id}): "
                . "{$linked} student registration(s) reference this event. "
                . 'Remove or reassign them first.'
            ];
            header('Location: events.php');
            exit;
        }

        $stmt = $mysqli->prepare('DELETE FROM `events` WHERE `event_id` = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        p12_remove_poster($row['poster_path']);

        $_SESSION['event_success'] = "Event '{$row['title']}' (ID: {$id}) deleted successfully.";
        audit_log($mysqli, 'event.delete', 'event', $id, "Deleted event '{$row['title']}'.");
        header('Location: events.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] event_delete DB error: ' . $e->getMessage());
        $_SESSION['event_errors'] = ['Could not delete the event. Please try again later.'];
        header('Location: events.php');
        exit;
    }
}

// --- GET: confirmation screen ----------------------------------------------------
$id = $_GET['id'] ?? '';
if (!ctype_digit((string)$id)) {
    $_SESSION['event_errors'] = ['Invalid event reference.'];
    header('Location: events.php');
    exit;
}
$id = (int)$id;

try {
    $stmt = $mysqli->prepare(
        'SELECT `event_id`, `title`, `category`, `event_date`, `venue`,
                `status`, `poster_path`
         FROM `events` WHERE `event_id` = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $linked = 0;
    if ($record) {
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS c FROM `registrations` WHERE `event_id` = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $linked = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    error_log('[studenthub] event_delete load error: ' . $e->getMessage());
    $record = null;
    $linked = 0;
}

if (!$record) {
    $_SESSION['event_errors'] = ["Event record not found (ID: {$id})."];
    header('Location: events.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Confirm Event Delete (Practical 12)</title>
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
                <li><a href="events.php" class="nav-link active">Events</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 640px; margin: 2rem auto;">
        <h1 class="page-title">Confirm Delete</h1>
        <p class="page-subtitle">This action cannot be undone.</p>

        <div class="card">
            <?php if (!empty($record['poster_path'])): ?>
                <img src="../<?= h($record['poster_path']) ?>" alt="Poster for <?= h($record['title']) ?>" style="max-width: 220px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 1rem;">
            <?php endif; ?>
            <div class="table-responsive">
                <table class="data-table">
                    <tbody>
                        <tr><th>ID</th><td><?= h($record['event_id']) ?></td></tr>
                        <tr><th>Title</th><td><?= h($record['title']) ?></td></tr>
                        <tr><th>Category</th><td><?= h($record['category']) ?></td></tr>
                        <tr><th>Date</th><td><?= h($record['event_date']) ?></td></tr>
                        <tr><th>Venue</th><td><?= h($record['venue']) ?></td></tr>
                        <tr><th>Registrations</th><td><?= h($linked) ?></td></tr>
                    </tbody>
                </table>
            </div>

            <?php if ($linked > 0): ?>
                <div role="alert" style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; padding: 0.85rem 1rem; border-radius: 8px; margin: 1rem 0; font-size: 0.9rem;">
                    This event has <?= h($linked) ?> student registration(s), so deletion is currently blocked to protect related records.
                </div>
                <a href="events.php" class="btn btn-outline">Back to Events</a>
            <?php else: ?>
                <p style="margin: 1rem 0;">Are you sure you want to permanently delete <strong><?= h($record['title']) ?></strong><?= !empty($record['poster_path']) ? ' and its poster file' : '' ?>?</p>
                <form method="POST" action="event_delete.php" style="display: flex; gap: 0.5rem;">
                    <input type="hidden" name="event_id" value="<?= h($record['event_id']) ?>">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="events.php" class="btn btn-outline">Cancel</a>
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
