<?php
/**
 * clear_logs.php — Admin-only log wipe.
 * Location: clear_logs.php (project root)
 */

require_once __DIR__ . '/core/auth_guard.php';
guard('admin');

$db  = db();
$uid = current_user()['id'];

$db->query("DELETE FROM user_activity_logs");
$db->query("INSERT INTO user_activity_logs (user_id, action) VALUES ($uid, 'Cleared All Logs')");

header('Location: ' . base_url() . 'admin/admin_dashboard.php?msg=All+logs+cleared');
exit();
