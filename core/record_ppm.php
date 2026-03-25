<?php
/**
 * record_ppm.php — Records PPM reading and runs trend analysis.
 * Called via POST fetch() every 30s from dashboards.
 * Location: core/record_ppm.php
 */
require_once __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

$ppm = (int)($_POST['ppm'] ?? 0);

$status = 'safe';
if ($ppm >= 450)     $status = 'danger';
elseif ($ppm >= 200) $status = 'warning';

$db = db();

// Make sure table exists
$db->query("CREATE TABLE IF NOT EXISTS ppm_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppm_value INT NOT NULL DEFAULT 0,
    status ENUM('safe','warning','danger') NOT NULL DEFAULT 'safe',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$db->query("INSERT INTO ppm_readings (ppm_value, status) VALUES ($ppm, '$status')");
$db->query("DELETE FROM ppm_readings WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

// Trend analysis
$res   = $db->query("SELECT ppm_value, recorded_at FROM ppm_readings WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY recorded_at ASC");
$trend = 'safe';

if ($res && $res->num_rows >= 3) {
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $n      = count($rows);
    $first  = (int)$rows[0]['ppm_value'];
    $last   = (int)$rows[$n-1]['ppm_value'];
    $mins   = (strtotime($rows[$n-1]['recorded_at']) - strtotime($rows[0]['recorded_at'])) / 60;

    if ($mins > 0 && $first > 0) {
        $pct_per_min = (($last - $first) / $first / max($mins,1)) * 100;
        if ($last >= 450)                       $trend = 'danger';
        elseif ($pct_per_min > 10 && $last > 100) $trend = 'warning';
    }
}

echo json_encode(['status' => 'ok', 'trend' => $trend, 'ppm' => $ppm]);