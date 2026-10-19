<?php
/**
 * Student Hub - Add / Edit Event Form (Practical 12, admin only)
 * Presentation only: saving + uploads live in php/process_event.php.
 */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

$errors = $_SESSION['event_form_errors'] ?? [];
$old = $_SESSION['event_form_old'] ?? [];
unset($_SESSION['event_form_errors'], $_SESSION['event_form_old']);

$editingId = null;
$record = null;

if (isset($_GET['id'])) {
    if (!ctype_digit((string)$_GET['id'])) {
        $_SESSION['event_errors'] = ['Invalid event reference.'];
        header('Location: events.php');
        exit;
    }
    $editingId = (int)$_GET['id'];
    $mysqli = getMysqliConnection();
    if ($mysqli === null) {
        $_SESSION['event_errors'] = ['Event records are temporarily unavailable.'];
        header('Location: events.php');
        exit;
    }
    try {
        $stmt = $mysqli->prepare(
            'SELECT `event_id`, `title`, `description`, `category`, `event_date`,
                    `venue`, `organizer`, `seats_available`, `status`, `poster_path`
             FROM `events` WHERE `event_id` = ? LIMIT 1'
        );
        $stmt->bind_param('i', $editingId);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] event_form load error: ' . $e->getMessage());
        $record = null;
    }
    if (!$record) {
        $_SESSION['event_errors'] = ['Event record not found (ID: ' . $editingId . ').'];
        header('Location: events.php');
        exit;
    }
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function old_val($key) {
    global $old, $record;
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    return $record[$key] ?? '';
}

$categories = ['Workshop', 'Technical', 'Cultural', 'Sports', 'Seminar', 'Other'];
$statuses = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
$isEdit = ($editingId !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - <?= $isEdit ? 'Edit' : 'Add' ?> Event (Practical 12)</title>
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
        <h1 class="page-title"><?= $isEdit ? 'Edit Event #' . h($editingId) : 'Add Event' ?></h1>
        <p class="page-subtitle">Poster is optional (JPEG / PNG / WebP, max 2 MB).<?= $isEdit ? ' Uploading a new poster replaces the current one.' : '' ?></p>

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
            <?php if ($isEdit && !empty($record['poster_path'])): ?>
                <div style="margin-bottom: 1rem;">
                    <div class="form-label">Current poster</div>
                    <img src="../<?= h($record['poster_path']) ?>" alt="Current poster for <?= h($record['title']) ?>" style="max-width: 220px; border-radius: 8px; border: 1px solid var(--border-color);">
                </div>
            <?php endif; ?>

            <form method="POST" action="process_event.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'add' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="event_id" value="<?= h($editingId) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="ef-title">Title</label>
                    <input type="text" id="ef-title" name="title" class="form-control" required maxlength="150" value="<?= h(old_val('title')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-description">Description</label>
                    <textarea id="ef-description" name="description" class="form-control" rows="3" maxlength="2000" placeholder="What is this event about?"><?= h(old_val('description')) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-category">Category</label>
                    <select id="ef-category" name="category" class="form-control" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= h($c) ?>"<?= old_val('category') === $c ? ' selected' : '' ?>><?= h($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-date">Event Date</label>
                    <input type="date" id="ef-date" name="event_date" class="form-control" required value="<?= h(old_val('event_date')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-venue">Venue</label>
                    <input type="text" id="ef-venue" name="venue" class="form-control" required maxlength="120" value="<?= h(old_val('venue')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-organizer">Organizer</label>
                    <input type="text" id="ef-organizer" name="organizer" class="form-control" maxlength="120" value="<?= h(old_val('organizer')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-seats">Seats Available</label>
                    <input type="number" id="ef-seats" name="seats_available" class="form-control" min="0" max="100000" value="<?= h(old_val('seats_available') !== '' ? old_val('seats_available') : '0') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-status">Status</label>
                    <select id="ef-status" name="status" class="form-control" required>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= h($s) ?>"<?= old_val('status') === $s ? ' selected' : '' ?>><?= h($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ef-poster">Event Poster (JPEG / PNG / WebP, max 2 MB)</label>
                    <input type="file" id="ef-poster" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Event' : 'Add Event' ?></button>
                    <a href="events.php" class="btn btn-outline">Cancel</a>
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
