<?php
/**
 * Logout handler.
 * Location: auth/logout.php
 */

require_once __DIR__ . '/../config/db_connect.php';

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    db()->query("INSERT INTO user_activity_logs (user_id, action) VALUES ($uid, 'Logged Out')");
}

$redirect = login_url();
session_unset();
session_destroy();
header('Location: ' . $redirect);
exit();