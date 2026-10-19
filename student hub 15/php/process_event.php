<?php
/**
 * Student Hub - Event Save Processor with poster upload (Practical 12)
 * ----------------------------------------------------------------------------
 * Admin-only. Handles "add" and "update" POSTs from php/event_form.php:
 *   - Backend validation of all event fields.
 *   - Poster upload validated server-side: real image parsing
 *     (getimagesize), verified MIME via finfo (never trusting the browser
 *     header or filename), JPEG/PNG/WebP only, max 2 MB.
 *   - Safe unique filenames (no user input in the stored name); uploads land
 *     in uploads/posters/ which forbids script execution (.htaccess).
 *   - Old poster file is removed when replaced (no orphans); DB stores the
 *     relative path. All writes use MySQLi prepared statements.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();
require_role('admin');

define('P12_MAX_POSTER_BYTES', 2 * 1024 * 1024); // 2 MB
define('P12_POSTER_DIR', dirname(__DIR__) . '/uploads/posters');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php');
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'add' && $action !== 'update') {
    $_SESSION['event_errors'] = ['Invalid operation.'];
    header('Location: events.php');
    exit;
}

$eventId = 0;
if ($action === 'update') {
    if (!isset($_POST['event_id']) || !ctype_digit((string)$_POST['event_id'])) {
        $_SESSION['event_errors'] = ['Invalid event reference.'];
        header('Location: events.php');
        exit;
    }
    $eventId = (int)$_POST['event_id'];
}

// --- Sanitize + validate event fields ----------------------------------------
$title       = isset($_POST['title'])       ? sanitize_string($_POST['title']) : '';
$description = isset($_POST['description']) ? sanitize_string($_POST['description']) : '';
$category    = isset($_POST['category'])    ? sanitize_string($_POST['category']) : '';
$eventDate   = isset($_POST['event_date'])  ? trim((string)$_POST['event_date']) : '';
$venue       = isset($_POST['venue'])       ? sanitize_string($_POST['venue']) : '';
$organizer   = isset($_POST['organizer'])   ? sanitize_string($_POST['organizer']) : '';
$seatsRaw    = isset($_POST['seats_available']) ? trim((string)$_POST['seats_available']) : '0';
$status      = isset($_POST['status'])      ? sanitize_string($_POST['status']) : '';

$errors = [];
if (strlen($title) < 3 || strlen($title) > 150) {
    $errors[] = 'Title is required (3-150 characters).';
}
if (strlen($description) > 2000) {
    $errors[] = 'Description must not exceed 2000 characters.';
}
$allowedCategories = ['Workshop', 'Technical', 'Cultural', 'Sports', 'Seminar', 'Other'];
if (!in_array($category, $allowedCategories, true)) {
    $errors[] = 'Please select a valid category.';
}
$dateOk = false;
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $eventDate, $m)) {
    $dateOk = checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}
if (!$dateOk) {
    $errors[] = 'Please provide a valid event date (YYYY-MM-DD).';
}
if ($venue === '' || strlen($venue) > 120) {
    $errors[] = 'Venue is required (max 120 characters).';
}
if (strlen($organizer) > 120) {
    $errors[] = 'Organizer must not exceed 120 characters.';
}
if (!ctype_digit($seatsRaw) || (int)$seatsRaw > 100000) {
    $errors[] = 'Seats available must be a number between 0 and 100000.';
}
$seats = (int)$seatsRaw;
$allowedStatuses = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Please select a valid status.';
}

$formBack = $action === 'update' ? 'event_form.php?id=' . $eventId : 'event_form.php';
$old = [
    'title' => $title, 'description' => $description, 'category' => $category,
    'event_date' => $eventDate, 'venue' => $venue, 'organizer' => $organizer,
    'seats_available' => (string)$seats, 'status' => $status,
];

if (!empty($errors)) {
    $_SESSION['event_form_errors'] = $errors;
    $_SESSION['event_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}

// --- Poster upload handling (optional file) -----------------------------------
$newPosterPath = null; // relative path for the DB, set only on success
$uploadError = null;

if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['poster'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
            ? 'Poster exceeds the 2 MB size limit.'
            : 'Poster upload failed (error code ' . (int)$file['error'] . '). Please try again.';
    } elseif ($file['size'] > P12_MAX_POSTER_BYTES) {
        $uploadError = 'Poster exceeds the 2 MB size limit.';
    } else {
        // 1. Real image parsing (rejects scripts masquerading as images).
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $uploadError = 'Poster must be a valid image file (JPEG, PNG, or WebP).';
        } else {
            // 2. Server-verified MIME (never the browser-supplied type).
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            if (!isset($mimeToExt[$mime])) {
                $uploadError = 'Poster must be a JPEG, PNG, or WebP image.';
            } else {
                // 3. Safe unique filename: server-generated, extension from
                //    the verified MIME (user filename is never reused).
                if (!is_dir(P12_POSTER_DIR) && !mkdir(P12_POSTER_DIR, 0755, true) && !is_dir(P12_POSTER_DIR)) {
                    $uploadError = 'Could not prepare the upload directory. Please try again later.';
                } else {
                    $safeName = sprintf(
                        'poster_%s_%s.%s',
                        date('YmdHis'),
                        bin2hex(random_bytes(8)),
                        $mimeToExt[$mime]
                    );
                    $destAbs = P12_POSTER_DIR . '/' . $safeName;
                    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
                        $uploadError = 'Could not save the poster file. Please try again later.';
                    } else {
                        @chmod($destAbs, 0644);
                        $newPosterPath = 'uploads/posters/' . $safeName;
                    }
                }
            }
        }
    }
}

if ($uploadError !== null) {
    $_SESSION['event_form_errors'] = [$uploadError];
    $_SESSION['event_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}

// --- Database ------------------------------------------------------------------
$mysqli = getMysqliConnection();
if ($mysqli === null) {
    // Avoid orphaned upload when the DB is unreachable.
    if ($newPosterPath !== null) {
        @unlink(dirname(__DIR__) . '/' . $newPosterPath);
    }
    error_log('[studenthub] process_event: database unavailable.');
    $_SESSION['event_form_errors'] = ['Service temporarily unavailable. Please try again later.'];
    $_SESSION['event_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}

/** Remove a stored poster file, confined to uploads/posters/. */
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

try {
    if ($action === 'add') {
        $stmt = $mysqli->prepare(
            'INSERT INTO `events`
                (`title`, `description`, `category`, `event_date`, `venue`,
                 `organizer`, `seats_available`, `status`, `poster_path`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssssiss',
            $title, $description, $category, $eventDate, $venue,
            $organizer, $seats, $status, $newPosterPath
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        audit_log($mysqli, 'event.add', 'event', $newId, "Added event '{$title}' ({$eventDate}).");
        $_SESSION['event_success'] = "Event '{$title}' added successfully (ID: {$newId}).";
    } else {
        // Target row must exist; fetch its current poster for orphan cleanup.
        $stmt = $mysqli->prepare('SELECT `poster_path` FROM `events` WHERE `event_id` = ? LIMIT 1');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$existing) {
            if ($newPosterPath !== null) {
                @unlink(dirname(__DIR__) . '/' . $newPosterPath);
            }
            $_SESSION['event_errors'] = ["Event record not found (ID: {$eventId})."];
            header('Location: events.php');
            exit;
        }

        $finalPoster = $newPosterPath !== null ? $newPosterPath : $existing['poster_path'];
        $stmt = $mysqli->prepare(
            'UPDATE `events`
             SET `title` = ?, `description` = ?, `category` = ?, `event_date` = ?,
                 `venue` = ?, `organizer` = ?, `seats_available` = ?,
                 `status` = ?, `poster_path` = ?
             WHERE `event_id` = ?'
        );
        $stmt->bind_param(
            'ssssssissi',
            $title, $description, $category, $eventDate, $venue,
            $organizer, $seats, $status, $finalPoster, $eventId
        );
        $stmt->execute();
        $stmt->close();

        // New poster replaced the old one: remove the old file (no orphans).
        if ($newPosterPath !== null && !empty($existing['poster_path'])
            && $existing['poster_path'] !== $newPosterPath) {
            p12_remove_poster($existing['poster_path']);
        }
        $_SESSION['event_success'] = "Event '{$title}' (ID: {$eventId}) updated successfully.";
        audit_log($mysqli, 'event.update', 'event', $eventId, "Updated event '{$title}' ({$eventDate}).");
    }

    unset($_SESSION['event_form_errors'], $_SESSION['event_form_old']);
    header('Location: events.php');
    exit;
} catch (mysqli_sql_exception $e) {
    error_log('[studenthub] process_event DB error ' . $e->getCode() . ': ' . $e->getMessage());
    if ($newPosterPath !== null) {
        @unlink(dirname(__DIR__) . '/' . $newPosterPath);
    }
    $_SESSION['event_form_errors'] = ['Could not save the event. Please try again later.'];
    $_SESSION['event_form_old'] = $old;
    header('Location: ' . $formBack);
    exit;
}
