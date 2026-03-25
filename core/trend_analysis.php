<?php
/**
 * FEATURE 2 — Smart PPM Trend Analysis
 *
 * Files:
 *   core/trend_analysis.php  ← PHP function (include in check_alert.php)
 *   core/record_ppm.php      ← Called every 30s via JS fetch()
 *
 * Integration:
 *   - Add recordPPM() call in your JS polling interval
 *   - check_alert.php will return 'warning' status when trend is rising
 */

// ────────────────────────────────────────────────────────────
// PART A: core/trend_analysis.php
// Include this in auth/check_alert.php
// ────────────────────────────────────────────────────────────
?>
<?php
/**
 * Analyzes the last 5 minutes of PPM readings.
 * Returns: 'safe' | 'warning' | 'danger'
 *
 * Warning triggers when PPM increases >10% per minute consistently.
 */
function analyzePPMTrend(mysqli $db): string {
    // Get readings from the last 5 minutes
    $res = $db->query("
        SELECT ppm_value, recorded_at
        FROM ppm_readings
        WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY recorded_at ASC
    ");

    if (!$res || $res->num_rows < 3) {
        return 'safe'; // Not enough data
    }

    $readings = [];
    while ($row = $res->fetch_assoc()) {
        $readings[] = $row;
    }

    $count = count($readings);
    if ($count < 2) return 'safe';

    // Calculate PPM increase rate per minute
    $first_ppm  = (int)$readings[0]['ppm_value'];
    $last_ppm   = (int)$readings[$count - 1]['ppm_value'];
    $first_time = strtotime($readings[0]['recorded_at']);
    $last_time  = strtotime($readings[$count - 1]['recorded_at']);

    $duration_minutes = ($last_time - $first_time) / 60;

    if ($duration_minutes <= 0 || $first_ppm <= 0) return 'safe';

    // Rate of change per minute
    $rate_per_minute = ($last_ppm - $first_ppm) / $duration_minutes;
    $pct_per_minute  = ($rate_per_minute / $first_ppm) * 100;

    // If increasing >10% per minute — Yellow Warning
    if ($pct_per_minute > 10 && $last_ppm > 100) {
        return 'warning';
    }

    // Danger threshold
    if ($last_ppm >= 450) {
        return 'danger';
    }

    return 'safe';
}

// ────────────────────────────────────────────────────────────
// PART B: core/record_ppm.php
// Called via fetch() from dashboards every 30s
// ────────────────────────────────────────────────────────────
/*
<?php
require_once __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

$ppm    = (int)($_POST['ppm'] ?? 0);
$status = 'safe';

if ($ppm >= 450)      $status = 'danger';
elseif ($ppm >= 200)  $status = 'warning';

$db = db();
$db->query("INSERT INTO ppm_readings (ppm_value, status) VALUES ($ppm, '$status')");

// Clean up old readings (keep last 1 hour only)
$db->query("DELETE FROM ppm_readings WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

// Run trend analysis
require_once __DIR__ . '/trend_analysis.php';
$trend = analyzePPMTrend($db);

echo json_encode(['status' => 'ok', 'trend' => $trend, 'ppm' => $ppm]);
*/
?>

<!-- ── FEATURE 2: Frontend JS ─────────────────────────────────
Add this to your user_dashboard.php and admin_dashboard.php
inside the existing <script> block
──────────────────────────────────────────────────────────── -->
<script>
/* ── Smart Trend Analysis JS ─────────────────────────────────── */
const RECORD_PPM_URL = '<?= base_url() ?>core/record_ppm.php';
let warningShown = false;

// Call this every 30 seconds to record PPM and check trend
function recordAndAnalyzePPM(currentPPM) {
  const fd = new FormData();
  fd.append('ppm', currentPPM);

  fetch(RECORD_PPM_URL, {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => {
      if (d.trend === 'warning' && !warningShown) {
        warningShown = true;
        showYellowWarning();
      } else if (d.trend === 'safe') {
        warningShown = false;
        hideYellowWarning();
      }
    })
    .catch(()=>{});
}

function showYellowWarning() {
  const existing = document.getElementById('yellowWarning');
  if (existing) return;

  const div = document.createElement('div');
  div.id = 'yellowWarning';
  div.innerHTML = `
    <div style="
      position:fixed;top:1.2rem;left:50%;transform:translateX(-50%);
      background:#1a1200;border:2px solid #ffb300;border-radius:10px;
      padding:.9rem 1.5rem;z-index:8500;display:flex;align-items:center;gap:.8rem;
      box-shadow:0 0 30px rgba(255,179,0,.3);animation:slideDown .3s ease both;
      font-family:'Exo 2',sans-serif;
    ">
      <span style="font-size:1.5rem;animation:spin 2s linear infinite">⚠️</span>
      <div>
        <div style="font-family:'Share Tech Mono',monospace;color:#ffb300;font-weight:700;font-size:.9rem;letter-spacing:.06em">
          YELLOW WARNING — PPM RISING
        </div>
        <div style="color:#997700;font-size:.75rem;margin-top:.2rem">
          Gas levels increasing rapidly. Monitor closely.
        </div>
      </div>
      <button onclick="hideYellowWarning()" style="background:none;border:none;color:#997700;font-size:1.2rem;cursor:pointer;margin-left:.5rem">✕</button>
    </div>
  `;
  document.body.appendChild(div);
}

function hideYellowWarning() {
  const w = document.getElementById('yellowWarning');
  if (w) w.remove();
}

// Add to your existing setInterval polling:
// recordAndAnalyzePPM(currentPPMValue);  // Call every 30s
</script>

<style>
@keyframes slideDown{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
</style>