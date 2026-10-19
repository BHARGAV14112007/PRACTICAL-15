<?php
/**
 * StudentHub - PDO Database Connection (Practical 8)
 * ----------------------------------------------------------------------------
 * Central connection factory used by all database-backed pages.
 *
 * - Credentials default to the standard XAMPP MySQL values and can be
 *   overridden with environment variables (DB_HOST, DB_PORT, DB_NAME,
 *   DB_USER, DB_PASS) without editing this file.
 * - PDO runs with exception error mode, native prepared statements and
 *   UTF-8 (utf8mb4) so errors surface immediately and safely.
 * - On failure this returns NULL and logs the technical detail server-side;
 *   callers must show a generic message and MUST NOT print credentials.
 */

// Load credentials: environment first, XAMPP defaults otherwise.
function studenthub_db_config() {
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'studenthub',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    ];
}

/**
 * Get (and reuse) the shared PDO connection.
 *
 * @return PDO|null Connected instance, or NULL when the database is
 *                  unreachable. Never exposes the password.
 */
function getDbConnection() {
    static $pdo = null;
    static $attempted = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($attempted) {
        return null; // Do not retry a failed connection within one request.
    }
    $attempted = true;

    $cfg = studenthub_db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $cfg['host'],
        $cfg['port'],
        $cfg['name']
    );

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // assoc arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // native prepares
        ]);
    } catch (PDOException $e) {
        // Log technical detail WITHOUT the password for server-side diagnosis.
        error_log(sprintf(
            '[studenthub] DB connection failed (host=%s port=%s db=%s user=%s): %s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['user'],
            $e->getMessage()
        ));
        $pdo = null;
    }

    return $pdo;
}

/**
 * Get (and reuse) the shared MySQLi connection (Practical 9).
 * Uses the SAME credentials as the PDO factory above - there is only one
 * configuration source (studenthub_db_config()).
 *
 * @return mysqli|null Connected instance, or NULL when the database is
 *                     unreachable. Never exposes the password.
 */
function getMysqliConnection() {
    static $mysqli = null;
    static $attempted = false;

    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }
    if ($attempted) {
        return null; // Do not retry a failed connection within one request.
    }
    $attempted = true;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // exceptions on error

    $cfg = studenthub_db_config();

    try {
        $mysqli = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int)$cfg['port']);
        $mysqli->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        // Log technical detail WITHOUT the password for server-side diagnosis.
        error_log(sprintf(
            '[studenthub] MySQLi connection failed (host=%s port=%s db=%s user=%s): %s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['user'],
            $e->getMessage()
        ));
        $mysqli = null;
    }

    return $mysqli;
}
