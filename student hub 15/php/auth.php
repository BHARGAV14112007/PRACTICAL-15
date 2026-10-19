<?php
/**
 * StudentHub - Session & Access Guard Library (Practical 10)
 * ----------------------------------------------------------------------------
 * Single include for all server-side authentication:
 *   - Secure session start (HttpOnly cookies, SameSite=Lax).
 *   - Login state + role helpers.
 *   - Inactivity timeout (15 minutes): expired sessions are destroyed and
 *     sent back to the login page.
 *   - require_login() / require_role() guards for protected pages.
 *
 * Usage at the TOP of a protected page (before ANY output):
 *     require_once __DIR__ . '/auth.php';
 *     auth_start();
 *     require_login();          // any authenticated user
 *     require_role('admin');    // administrators only
 */

define('STUDENTHUB_SESSION_TIMEOUT', 900); // 15 minutes of inactivity

/**
 * Start the PHP session with safe cookie settings. Call before any output.
 */
function auth_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,          // session cookie: expires when browser closes
        'path'     => '/',
        'secure'   => $secure,    // HTTPS only when available
        'httponly' => true,       // never readable from JavaScript
        'samesite' => 'Lax',      // CSRF mitigation for top-level navigation
    ]);
    session_start();
}

/**
 * Log a user in: regenerates the session ID (fixation protection) and
 * stores only the identity fields the app needs (never the password hash).
 */
function auth_login($userRow) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int)$userRow['student_id'],
        'name'  => $userRow['full_name'],
        'email' => $userRow['email'],
        'role'  => $userRow['role'],
    ];
    $_SESSION['last_activity'] = time();
}

/**
 * Current authenticated user array, or null. Enforces the inactivity timeout:
 * expired sessions are destroyed and the visitor is sent to the login page.
 */
function current_user() {
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }
    $idle = time() - (int)($_SESSION['last_activity'] ?? 0);
    if ($idle > STUDENTHUB_SESSION_TIMEOUT) {
        auth_logout();
        header('Location: login.php?error=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time(); // sliding window
    return $_SESSION['user'];
}

/**
 * Allow only authenticated users. Others go to the login page.
 */
function require_login() {
    if (current_user() === null) {
        header('Location: login.php?error=auth');
        exit;
    }
}

/**
 * Allow only users with the given role (server-side authorization).
 * Logged-in users with the wrong role are sent to the login page with a
 * generic message; guests go through require_login() first.
 */
function require_role($role) {
    require_login();
    $user = $_SESSION['user'];
    if (!isset($user['role']) || $user['role'] !== $role) {
        header('Location: login.php?error=forbidden');
        exit;
    }
}

/**
 * Record an administrative action in `audit_logs` (Practical 14).
 * Call ONLY after the corresponding operation has succeeded - failures must
 * never be logged as successes. Logging itself must never break the main
 * operation, so all errors are swallowed into the server log.
 *
 * @param mysqli $mysqli      Active database connection.
 * @param string $action      e.g. 'student.add', 'event.delete'.
 * @param string|null $targetType  e.g. 'student', 'event'.
 * @param int|null $targetId  Primary key of the affected record, if any.
 * @param string|null $details Human-readable summary (no passwords/secrets).
 */
function audit_log($mysqli, $action, $targetType = null, $targetId = null, $details = null) {
    try {
        $actorId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        $actorEmail = isset($_SESSION['user']['email']) ? (string)$_SESSION['user']['email'] : null;
        $stmt = $mysqli->prepare(
            'INSERT INTO `audit_logs`
                (`actor_id`, `actor_email`, `action`, `target_type`, `target_id`, `details`)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssis', $actorId, $actorEmail, $action, $targetType, $targetId, $details);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('[studenthub] audit_log failed: ' . $e->getMessage());
    }
}

/**
 * Secure logout: clears session data, destroys the session and removes the
 * session cookie. (No Remember-Me cookie exists in this practical.)
 */
function auth_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
