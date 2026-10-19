<?php
/**
 * Student Hub - Events JSON API (Practical 13)
 * ----------------------------------------------------------------------------
 * Endpoints (same POST+JSON design as the existing processors):
 *
 *   GET  ?action=list              public   - all events (JSON array)
 *   GET  ?action=get&id=N          public   - single event (404 when missing)
 *   GET  ?action=csrf              login    - CSRF token + role for the demo UI
 *   POST action=add                admin    - create event (201 on success)
 *   POST action=update             admin    - update event (404 when missing)
 *   POST action=delete             admin    - delete event (409 when linked
 *                                            registrations exist)
 *
 * POST accepts application/json bodies or regular form posts. State-changing
 * actions require an admin session AND a valid CSRF token (hash_equals).
 * Every response uses the project's standard JSON envelope
 * {success, message, data, errors, timestamp} with a suitable status code.
 * NOTE: poster uploads stay on php/event_form.php; the API keeps any
 * existing poster_path untouched (NULL for API-created events).
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

auth_start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// --- Read input: JSON body or form fields --------------------------------------
$input = [];
if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $decoded = json_decode(file_get_contents('php://input'), true);
        if (!is_array($decoded)) {
            send_json_response(false, 'Invalid JSON in request body.', [], ['body' => 'Request body is not valid JSON.'], 400);
        }
        $input = $decoded;
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

$action = isset($input['action']) ? strtolower(trim((string)$input['action'])) : 'list';

// --- Database --------------------------------------------------------------------
$mysqli = getMysqliConnection();
if ($mysqli === null) {
    error_log('[studenthub] api_events: database unavailable.');
    send_json_response(false, 'Service temporarily unavailable. Please try again later.', [], ['system' => 'Database unreachable.'], 503);
}

function api_event_row($mysqli, $id) {
    $stmt = $mysqli->prepare(
        'SELECT `event_id`, `title`, `description`, `category`, `event_date`,
                `venue`, `organizer`, `seats_available`, `status`, `poster_path`
         FROM `events` WHERE `event_id` = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function api_validate_event($input, &$errors) {
    $title       = isset($input['title'])       ? sanitize_string($input['title']) : '';
    $description = isset($input['description']) ? sanitize_string($input['description']) : '';
    $category    = isset($input['category'])    ? sanitize_string($input['category']) : '';
    $eventDate   = isset($input['event_date'])  ? trim((string)$input['event_date']) : '';
    $venue       = isset($input['venue'])       ? sanitize_string($input['venue']) : '';
    $organizer   = isset($input['organizer'])   ? sanitize_string($input['organizer']) : '';
    $seatsRaw    = isset($input['seats_available']) ? trim((string)$input['seats_available']) : '0';
    $status      = isset($input['status'])      ? sanitize_string($input['status']) : '';

    if (strlen($title) < 3 || strlen($title) > 150) {
        $errors['title'] = 'Title is required (3-150 characters).';
    }
    if (strlen($description) > 2000) {
        $errors['description'] = 'Description must not exceed 2000 characters.';
    }
    if (!in_array($category, ['Workshop', 'Technical', 'Cultural', 'Sports', 'Seminar', 'Other'], true)) {
        $errors['category'] = 'Please select a valid category.';
    }
    $dateOk = false;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $eventDate, $m)) {
        $dateOk = checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
    if (!$dateOk) {
        $errors['event_date'] = 'Please provide a valid event date (YYYY-MM-DD).';
    }
    if ($venue === '' || strlen($venue) > 120) {
        $errors['venue'] = 'Venue is required (max 120 characters).';
    }
    if (strlen($organizer) > 120) {
        $errors['organizer'] = 'Organizer must not exceed 120 characters.';
    }
    if (!ctype_digit($seatsRaw) || (int)$seatsRaw > 100000) {
        $errors['seats_available'] = 'Seats must be a number between 0 and 100000.';
    }
    if (!in_array($status, ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'], true)) {
        $errors['status'] = 'Please select a valid status.';
    }

    return [
        'title' => $title, 'description' => $description, 'category' => $category,
        'event_date' => $eventDate, 'venue' => $venue, 'organizer' => $organizer,
        'seats_available' => (int)$seatsRaw, 'status' => $status,
    ];
}

// --- GET: public reads + CSRF token ----------------------------------------------
if ($method === 'GET') {
    try {
        if ($action === 'list') {
            $result = $mysqli->query(
                'SELECT `event_id`, `title`, `description`, `category`, `event_date`,
                        `venue`, `organizer`, `seats_available`, `status`, `poster_path`
                 FROM `events` ORDER BY `event_date` ASC, `event_id` ASC'
            );
            send_json_response(true, 'Events retrieved.', ['events' => $result->fetch_all(MYSQLI_ASSOC)]);
        }

        if ($action === 'get') {
            $id = $input['id'] ?? '';
            if (!ctype_digit((string)$id)) {
                send_json_response(false, 'Invalid event reference.', [], ['id' => 'Event id must be a number.'], 400);
            }
            $row = api_event_row($mysqli, (int)$id);
            if (!$row) {
                send_json_response(false, 'Event not found.', [], ['id' => 'No event with this id.'], 404);
            }
            send_json_response(true, 'Event retrieved.', ['event' => $row]);
        }

        if ($action === 'csrf') {
            // Token issuance requires a logged-in user (any role); the demo
            // UI uses the role to decide whether to show management controls.
            if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
                send_json_response(false, 'Login required.', [], ['auth' => 'Please log in first.'], 401);
            }
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            send_json_response(true, 'CSRF token issued.', [
                'csrf_token' => $_SESSION['csrf_token'],
                'role' => $_SESSION['user']['role'],
                'name' => $_SESSION['user']['name'],
            ]);
        }

        send_json_response(false, 'Unknown action. Use list, get, or csrf.', [], ['action' => 'Unsupported GET action.'], 400);
    } catch (mysqli_sql_exception $e) {
        error_log('[studenthub] api_events GET error: ' . $e->getMessage());
        send_json_response(false, 'Service temporarily unavailable.', [], ['system' => 'Query failed.'], 500);
    }
}

// --- POST: admin-only writes with CSRF protection ----------------------------------
if ($method !== 'POST') {
    send_json_response(false, 'Method not allowed. Use GET or POST.', [], ['method' => 'Unsupported HTTP method.'], 405);
}

if (!in_array($action, ['add', 'update', 'delete'], true)) {
    send_json_response(false, 'Unknown action. Use add, update, or delete.', [], ['action' => 'Unsupported POST action.'], 400);
}

// Server-side authorization first (frontend controls are never enough).
if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
    send_json_response(false, 'Login required.', [], ['auth' => 'Please log in first.'], 401);
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    send_json_response(false, 'Access denied: administrators only.', [], ['auth' => 'Insufficient privileges.'], 403);
}

// CSRF check for every state-changing request under session authentication.
$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    send_json_response(false, 'Invalid or missing CSRF token.', [], ['csrf_token' => 'Refresh the page and try again.'], 403);
}

try {
    if ($action === 'add') {
        $errors = [];
        $data = api_validate_event($input, $errors);
        if (!empty($errors)) {
            send_json_response(false, 'Validation failed.', [], $errors, 422);
        }
        $stmt = $mysqli->prepare(
            'INSERT INTO `events`
                (`title`, `description`, `category`, `event_date`, `venue`,
                 `organizer`, `seats_available`, `status`, `poster_path`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)'
        );
        $stmt->bind_param(
            'ssssssis',
            $data['title'], $data['description'], $data['category'],
            $data['event_date'], $data['venue'], $data['organizer'],
            $data['seats_available'], $data['status']
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        audit_log($mysqli, 'event.add', 'event', $newId, "Added event '{$data['title']}' via API.");
        send_json_response(true, 'Event created.', ['event' => api_event_row($mysqli, $newId)], [], 201);
    }

    if ($action === 'update') {
        $id = $input['id'] ?? ($input['event_id'] ?? '');
        if (!ctype_digit((string)$id)) {
            send_json_response(false, 'Invalid event reference.', [], ['id' => 'Event id must be a number.'], 400);
        }
        $errors = [];
        $data = api_validate_event($input, $errors);
        if (!empty($errors)) {
            send_json_response(false, 'Validation failed.', [], $errors, 422);
        }
        if (!api_event_row($mysqli, (int)$id)) {
            send_json_response(false, 'Event not found.', [], ['id' => 'No event with this id.'], 404);
        }
        $stmt = $mysqli->prepare(
            'UPDATE `events`
             SET `title` = ?, `description` = ?, `category` = ?, `event_date` = ?,
                 `venue` = ?, `organizer` = ?, `seats_available` = ?, `status` = ?
             WHERE `event_id` = ?'
        );
        $stmt->bind_param(
            'ssssssisi',
            $data['title'], $data['description'], $data['category'],
            $data['event_date'], $data['venue'], $data['organizer'],
            $data['seats_available'], $data['status'], $id
        );
        $stmt->execute();
        $stmt->close();
        audit_log($mysqli, 'event.update', 'event', (int)$id, "Updated event '{$data['title']}' via API.");
        send_json_response(true, 'Event updated.', ['event' => api_event_row($mysqli, (int)$id)]);
    }

    // delete
    $id = $input['id'] ?? ($input['event_id'] ?? '');
    if (!ctype_digit((string)$id)) {
        send_json_response(false, 'Invalid event reference.', [], ['id' => 'Event id must be a number.'], 400);
    }
    $row = api_event_row($mysqli, (int)$id);
    if (!$row) {
        send_json_response(false, 'Event not found.', [], ['id' => 'No event with this id.'], 404);
    }
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS c FROM `registrations` WHERE `event_id` = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $linked = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    if ($linked > 0) {
        send_json_response(false, "Cannot delete: {$linked} registration(s) reference this event.", [], ['event' => 'Remove or reassign registrations first.'], 409);
    }
    $stmt = $mysqli->prepare('DELETE FROM `events` WHERE `event_id` = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    audit_log($mysqli, 'event.delete', 'event', (int)$id, "Deleted event '{$row['title']}' via API.");
    // Remove its poster file too (no orphans), confined to uploads/posters/.
    if (!empty($row['poster_path'])) {
        $base = realpath(dirname(__DIR__) . '/uploads/posters');
        $target = realpath(dirname(__DIR__) . '/' . $row['poster_path']);
        if ($base !== false && $target !== false && strpos($target, $base) === 0 && is_file($target)) {
            @unlink($target);
        }
    }
    send_json_response(true, 'Event deleted.', ['id' => (int)$id]);
} catch (mysqli_sql_exception $e) {
    error_log('[studenthub] api_events POST error: ' . $e->getMessage());
    send_json_response(false, 'Service temporarily unavailable.', [], ['system' => 'Operation failed.'], 500);
}
