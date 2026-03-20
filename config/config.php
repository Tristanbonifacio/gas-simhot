<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection - correct __DIR__ usage
require_once __DIR__ . '/db_connect.php';  // __DIR__ points to /config/

// Site URL
define('BASE_URL', '/gasleak');

// PPM thresholds
define('PPM_SAFE',    200);
define('PPM_WARNING', 350);
define('PPM_DANGER',  450);

/**
 * Returns a live MySQLi connection.
 * Dies with a clean error if the connection fails.
 */
function db(): mysqli {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }

        // Set charset to UTF-8
        $conn->set_charset('utf8mb4');
    }

    return $conn;
}
?>