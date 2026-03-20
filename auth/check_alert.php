<?php
/**
 * check_alert.php — Real-time JSON status endpoint.
 * Polled every 2s by all dashboards. No auth required (read-only public state).
 *
 * Location: auth/check_alert.php
 */

require_once __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

$res = db()->query("SELECT * FROM system_status WHERE id = 1 LIMIT 1");

if ($res && $row = $res->fetch_assoc()) {
    $row['is_active']             = (int)$row['is_active'];
    $row['acknowledged_by_admin'] = (int)$row['acknowledged_by_admin'];
    $row['ppm']                   = (int)($row['ppm'] ?? 0);
    $row['lat']                   = (float)($row['lat'] ?? 0);
    $row['lng']                   = (float)($row['lng'] ?? 0);
    echo json_encode($row);
} else {
    echo json_encode([
        'is_active' => 0, 'ppm' => 0,
        'acknowledged_by_admin' => 0,
        'location' => '', 'triggered_by' => '',
        'lat' => 0, 'lng' => 0, 'ack_time' => null,
    ]);
}