<?php
/**
 * log_action.php — Logs activity and updates system_status.
 * Called via fetch() POST from all dashboards. Returns JSON.
 *
 * Location: core/log_action.php
 */

require_once __DIR__ . '/../core/auth_guard.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || empty($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or missing action.']);
    exit();
}

$db   = db();
$uid  = (int)$_SESSION['user_id'];
$name = $db->real_escape_string($_SESSION['full_name']);
$act  = $db->real_escape_string(trim($_POST['action']));

// 1. Log activity (skip housekeeping calls)
if ($act !== 'admin_ack') {
    $db->query("INSERT INTO user_activity_logs (user_id, action, created_at)
                VALUES ($uid, '$act', NOW())");
}

// 2. Leak Detected
if ($act === 'Leak Detected') {
    $res = $db->query("SELECT location FROM users WHERE id = $uid LIMIT 1");
    $row = $res ? $res->fetch_assoc() : [];
    $loc = $db->real_escape_string($row['location'] ?? 'General Area');

    $coords = [
        'Kitchen'     => [8.3701, 124.8650],
        'Laboratory'  => [8.3685, 124.8630],
        'Warehouse'   => [8.3710, 124.8660],
        'Main Office' => [8.3690, 124.8640],
    ];
    [$lat, $lng] = $coords[$row['location'] ?? ''] ?? [8.3697, 124.8644];
    $ppm = PPM_DANGER;

    $db->query("UPDATE system_status SET
                    is_active             = 1,
                    triggered_by          = '$name',
                    location              = '$loc',
                    lat                   = $lat,
                    lng                   = $lng,
                    ppm                   = $ppm,
                    acknowledged_by_admin = 0,
                    ack_time              = NULL,
                    triggered_at          = NOW()
                WHERE id = 1");
}

// 3. System Reset
elseif ($act === 'System Reset') {
    $db->query("UPDATE system_status SET
                    is_active             = 0,
                    ppm                   = 0,
                    acknowledged_by_admin = 0,
                    ack_time              = NULL
                WHERE id = 1");
}

// 4. Admin Acknowledgment
elseif ($act === 'admin_ack') {
    $db->query("UPDATE system_status SET
                    acknowledged_by_admin = 1,
                    ack_time              = NOW()
                WHERE id = 1");
}

echo json_encode(['status' => 'success', 'action' => $act]);