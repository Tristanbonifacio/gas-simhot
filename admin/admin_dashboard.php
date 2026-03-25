<?php
/**
 * Admin Dashboard.
 * Location: admin/admin_dashboard.php
 */

require_once __DIR__ . '/../core/auth_guard.php';
guard('admin');

$user = current_user();
$db   = db();

// Handle clear logs BEFORE fetching logs
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $db->query("DELETE FROM user_activity_logs");
    $uid = $user['id'];
    $db->query("INSERT INTO user_activity_logs (user_id, action) VALUES ($uid, 'Cleared All Logs')");
    header('Location: ' . base_url() . 'admin/admin_dashboard.php?msg=Logs+cleared');
    exit();
}

// Stats
$total_logs  = (int)$db->query("SELECT COUNT(*) c FROM user_activity_logs")->fetch_assoc()['c'];
$leak_count  = (int)$db->query("SELECT COUNT(*) c FROM user_activity_logs WHERE action LIKE '%Leak%'")->fetch_assoc()['c'];
$total_users = (int)$db->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$is_active   = (int)$db->query("SELECT is_active FROM system_status WHERE id=1")->fetch_assoc()['is_active'];

// Logs
$logs = $db->query("
    SELECT u.full_name, u.role, u.location, l.action, l.created_at
    FROM user_activity_logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC LIMIT 50
");

$msg          = htmlspecialchars($_GET['msg'] ?? '');
$check_url    = base_url() . 'auth/check_alert.php';
$log_act_url  = base_url() . 'core/log_action.php';
$logout_url   = logout_url();
$clear_url    = base_url() . 'admin/admin_dashboard.php?clear=1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — GAS-SIMHOT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
:root{--bg:#060d18;--sb:#09131f;--panel:#0d1e30;--border:#163350;--accent:#00d4ff;--danger:#ff4c4c;--success:#00e5a0;--warn:#ffb300;--text:#cfe8ff;--muted:#4a7a9b;--mono:'Share Tech Mono',monospace;--sans:'Exo 2',sans-serif;--sw:240px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{display:flex;background:var(--bg);color:var(--text);font-family:var(--sans);font-size:.93rem;}

/* Sidebar */
.sidebar{width:var(--sw);min-width:var(--sw);background:var(--sb);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:1.5rem 0;z-index:100;}
.sb-logo{padding:0 1.4rem 1.6rem;border-bottom:1px solid var(--border);}
.sb-logo h2{font-family:var(--mono);font-size:1.05rem;color:var(--accent);letter-spacing:.07em;}
.sb-logo p{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-top:.25rem;}
.sb-badge{display:inline-block;background:rgba(0,212,255,.1);border:1px solid rgba(0,212,255,.3);color:var(--accent);font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:2px 8px;border-radius:20px;margin-top:.5rem;}
.sb-nav{flex:1;padding:1rem 0;}
.nav-sec{padding:.4rem 1.4rem .2rem;font-size:.63rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1.4rem;color:var(--text);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .2s,color .2s;border-left:3px solid transparent;cursor:pointer;}
.nav-item:hover,.nav-item.active{background:rgba(0,212,255,.07);color:var(--accent);border-left-color:var(--accent);}
.nav-item.red{color:var(--danger);}
.nav-item.red:hover{background:rgba(255,76,76,.07);border-left-color:var(--danger);}
.sb-foot{padding:1rem 1.4rem;border-top:1px solid var(--border);}
.user-chip{display:flex;align-items:center;gap:.65rem;margin-bottom:.9rem;}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#0072ff);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#060d18;flex-shrink:0;}
.u-name{font-size:.85rem;font-weight:600;}
.u-role{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;}
.btn-logout{display:block;width:100%;background:rgba(255,76,76,.12);border:1px solid rgba(255,76,76,.3);color:var(--danger);border-radius:6px;padding:.5rem;font-size:.8rem;font-weight:600;text-align:center;text-decoration:none;transition:background .2s;}
.btn-logout:hover{background:rgba(255,76,76,.22);}

/* Main */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 1.8rem;height:56px;border-bottom:1px solid var(--border);background:var(--sb);flex-shrink:0;}
.topbar-title{font-family:var(--mono);font-size:.95rem;color:var(--accent);letter-spacing:.08em;}
.live-badge{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--success);font-family:var(--mono);}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(0,229,160,.6)}50%{opacity:.6;box-shadow:0 0 0 6px rgba(0,229,160,0)}}
.content{flex:1;overflow-y:auto;padding:1.4rem 1.8rem;}
.content::-webkit-scrollbar{width:5px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}

/* Stat cards */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.4rem;}
.sc{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:1.1rem 1.3rem;position:relative;overflow:hidden;transition:transform .2s;}
.sc:hover{transform:translateY(-3px);}
.sc-label{font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;}
.sc-value{font-family:var(--mono);font-size:2rem;font-weight:700;line-height:1;}
.sc-icon{position:absolute;right:1rem;top:.9rem;font-size:1.6rem;opacity:.18;}
.sc.info{border-color:rgba(0,212,255,.3);} .sc.info .sc-value{color:var(--accent);}
.sc.success{border-color:rgba(0,229,160,.3);} .sc.success .sc-value{color:var(--success);}
.sc.danger{border-color:rgba(255,76,76,.3);} .sc.danger .sc-value{color:var(--danger);}
.sc.warn{border-color:rgba(255,179,0,.3);} .sc.warn .sc-value{color:var(--warn);}

/* Layout */
.grid-gauge-map{display:grid;grid-template-columns:210px 1fr;gap:1.2rem;margin-bottom:1.4rem;}

/* Panel */
.panel{background:var(--panel);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.ph{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--border);}
.ph-title{font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);display:flex;align-items:center;gap:.5rem;}
.pb{padding:1.1rem 1.2rem;}

/* Gauge */
.gauge-wrap{text-align:center;padding:.5rem 0;}
.gauge-ring{width:130px;height:130px;border-radius:50%;border:8px solid var(--border);display:flex;align-items:center;justify-content:center;flex-direction:column;margin:0 auto 1rem;transition:border-color .4s,box-shadow .4s;}
.gauge-ring.safe{border-color:var(--success);box-shadow:0 0 20px rgba(0,229,160,.3);}
.gauge-ring.danger{border-color:var(--danger);box-shadow:0 0 20px rgba(255,76,76,.5);animation:gShake .5s infinite alternate;}
@keyframes gShake{from{transform:rotate(-1deg)}to{transform:rotate(1deg)}}
.g-ppm{font-family:var(--mono);font-size:1.6rem;font-weight:700;line-height:1;}
.g-unit{font-size:.68rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase;}
.status-pill{display:inline-block;padding:.3rem .9rem;border-radius:20px;font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-family:var(--mono);}
.status-pill.online{background:rgba(0,229,160,.15);border:1px solid rgba(0,229,160,.4);color:var(--success);}
.status-pill.offline{background:rgba(255,76,76,.15);border:1px solid rgba(255,76,76,.4);color:var(--danger);animation:blink 1s linear infinite;}
@keyframes blink{50%{opacity:.4}}

/* Map */
#map{height:280px;border-radius:0 0 8px 8px;position:relative;z-index:1;}

/* Log table */
.log-scroll{max-height:340px;overflow-y:auto;}
.log-scroll::-webkit-scrollbar{width:4px;}
.log-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
table{width:100%;border-collapse:collapse;font-size:.83rem;}
thead th{font-size:.66rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:.5rem .7rem;text-align:left;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--panel);}
tbody tr{border-bottom:1px solid rgba(22,51,80,.5);transition:background .15s;}
tbody tr:hover{background:rgba(0,212,255,.04);}
tbody td{padding:.5rem .7rem;vertical-align:middle;}
.tag{display:inline-block;padding:2px 7px;border-radius:4px;font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;}
.tag-danger{background:rgba(255,76,76,.15);color:var(--danger);}
.tag-success{background:rgba(0,229,160,.12);color:var(--success);}
.tag-info{background:rgba(0,212,255,.12);color:var(--accent);}
.tag-muted{background:rgba(74,122,155,.15);color:var(--muted);}

/* Search */
.search-bar{display:flex;align-items:center;gap:.5rem;background:rgba(0,212,255,.04);border:1px solid var(--border);border-radius:6px;padding:.4rem .8rem;}
.search-bar input{background:none;border:none;outline:none;color:var(--text);font-family:var(--sans);font-size:.85rem;flex:1;}
.search-bar input::placeholder{color:var(--muted);}

/* Buttons */
.btn-sm{font-size:.75rem;padding:.3rem .75rem;border-radius:5px;border:1px solid var(--border);background:rgba(0,212,255,.07);color:var(--accent);cursor:pointer;font-family:var(--sans);font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;transition:background .2s;}
.btn-sm:hover{background:rgba(0,212,255,.15);}
.btn-sm.danger{color:var(--danger);border-color:rgba(255,76,76,.3);background:rgba(255,76,76,.07);}
.btn-sm.danger:hover{background:rgba(255,76,76,.15);}

/* Alert bar */
.alert-bar{border-radius:8px;padding:.7rem 1.1rem;font-size:.82rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;}
.alert-bar.success{background:rgba(0,229,160,.1);border:1px solid rgba(0,229,160,.3);color:var(--success);}

/* ── GLOBAL TOAST ── */
.global-toast{position:fixed;top:1.2rem;right:1.2rem;background:#130a0e;border:1px solid var(--danger);border-left:4px solid var(--danger);border-radius:10px;padding:.9rem 1.2rem;display:none;align-items:center;gap:.8rem;z-index:9998;max-width:340px;box-shadow:0 4px 24px rgba(255,76,76,.3);animation:toastIn .3s ease both;}
.global-toast.show{display:flex;}
@keyframes toastIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
.toast-dot{width:10px;height:10px;border-radius:50%;background:var(--danger);animation:pulse 1s infinite;flex-shrink:0;}
.toast-msg{font-size:.82rem;color:var(--text);line-height:1.4;}
.toast-msg strong{color:var(--danger);font-family:var(--mono);}
.toast-close{background:none;border:none;color:var(--muted);cursor:pointer;font-size:1.1rem;line-height:1;flex-shrink:0;}
.toast-close:hover{color:var(--text);}

/* Sticky emergency alert */
#sticky-alert{position:fixed;bottom:1.5rem;right:1.5rem;width:360px;background:#150b0b;border:1px solid rgba(255,76,76,.5);border-left:5px solid var(--danger);border-radius:10px;padding:1.2rem 1.4rem;box-shadow:0 8px 32px rgba(255,76,76,.2);z-index:9000;display:none;animation:slideUp .3s ease both;}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
#sticky-alert h4{color:var(--danger);font-family:var(--mono);font-size:1rem;letter-spacing:.06em;display:flex;align-items:center;gap:.5rem;}
#sticky-alert p{font-size:.82rem;color:var(--text);margin:.6rem 0 1rem;line-height:1.5;}
.btn-ack{width:100%;background:var(--danger);color:white;border:none;border-radius:6px;padding:.55rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:var(--mono);letter-spacing:.06em;transition:background .2s;}
.btn-ack:hover{background:#ff6b6b;}

/* ── Toast Notification ── */
.notif-toast{position:fixed;top:1.2rem;right:1.2rem;max-width:320px;background:#0b1629;border:1px solid var(--border);border-left:4px solid var(--accent);border-radius:10px;padding:1rem 1.2rem;z-index:8000;display:none;animation:slideIn .3s ease both;box-shadow:0 8px 24px rgba(0,0,0,.4);}
.notif-toast.show{display:block;}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
.notif-toast.danger{border-left-color:var(--danger);background:#130008;}
.notif-toast.success{border-left-color:var(--success);background:#001a0f;}
.notif-title{font-family:var(--mono);font-size:.85rem;font-weight:700;margin-bottom:.3rem;}
.notif-body{font-size:.78rem;color:var(--muted);line-height:1.5;}
</style>
</head>
<body>

<div class="global-toast" id="global-toast">
  <span class="toast-dot"></span>
  <div class="toast-msg"><strong>🚨 GAS LEAK ACTIVE</strong><br><span id="toast-loc"></span></div>
  <button class="toast-close" onclick="document.getElementById('global-toast').classList.remove('show')">✕</button>
</div>

<div class="global-toast" id="global-toast">
  <span class="toast-dot"></span>
  <div class="toast-msg"><strong>🚨 GAS LEAK ACTIVE</strong><br><span id="toast-loc"></span></div>
  <button class="toast-close" onclick="document.getElementById('global-toast').classList.remove('show')">✕</button>
</div>


<!-- Toast -->
<div class="notif-toast" id="notifToast">
  <div class="notif-title" id="notifTitle"></div>
  <div class="notif-body" id="notifBody"></div>
</div>

<aside class="sidebar">
  <div class="sb-logo">
    <h2>🛡️ GAS-SIMHOT</h2>
    <p>Admin Command Center</p>
    <span class="sb-badge">Administrator</span>
  </div>
  <nav class="sb-nav">
    <div class="nav-sec">Monitoring</div>
    <a class="nav-item active" href="#">
      <span>📊</span> Dashboard
    </a>
    <a class="nav-item" href="#map-section" onclick="document.getElementById('map-section').scrollIntoView()">
    <span>🗺️</span> Live Map
</a>

<a class="nav-item" href="floor_plan.php">
    <span>🏠</span> Interactive Floor Plan
</a>
<a class="nav-item" href="generate_report.php" target="_blank">
    <span>📄</span> Weekly Report (PDF)
</a>
    <div class="nav-sec" style="margin-top:.8rem">Management</div>
    <a class="nav-item" href="#logs-section" onclick="document.getElementById('logs-section').scrollIntoView({behavior:'smooth'});return false;">
      <span>📋</span> Activity Logs
    </a>
    <a class="nav-item" href="<?= base_url() ?>user/profile.php"><span>👤</span> My Profile</a>
        <a class="nav-item red" href="<?= $clear_url ?>"
       onclick="return confirm('Delete ALL activity logs? This cannot be undone.')">
      <span>🗑️</span> Clear Logs
    </a>
  </nav>
  <div class="sb-foot">
    <div class="user-chip">
      <div class="avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
      <div>
        <div class="u-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="u-role">Admin</div>
      </div>
    </div>
    <a href="<?= $logout_url ?>" class="btn-logout">⏻ Logout</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <span class="topbar-title">ADMIN / DASHBOARD</span>
    <div class="live-badge">
      <span class="live-dot"></span>
      LIVE FEED · <span id="clock">--:--:--</span>
    </div>
  </div>

  <div class="content">
    <?php if ($msg): ?>
      <div class="alert-bar success">✓ <?= $msg ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="sc info">
        <div class="sc-label">Total Personnel</div>
        <div class="sc-value"><?= $total_users ?></div>
        <span class="sc-icon">👥</span>
      </div>
      <div class="sc <?= $is_active ? 'danger' : 'success' ?>" id="status-card">
        <div class="sc-label">System Status</div>
        <div class="sc-value" id="stat-status"><?= $is_active ? 'ALERT' : 'SAFE' ?></div>
        <span class="sc-icon"><?= $is_active ? '🚨' : '✅' ?></span>
      </div>
      <div class="sc warn">
        <div class="sc-label">Leak Events</div>
        <div class="sc-value"><?= $leak_count ?></div>
        <span class="sc-icon">💨</span>
      </div>
      <div class="sc info">
        <div class="sc-label">Activity Logs</div>
        <div class="sc-value"><?= $total_logs ?></div>
        <span class="sc-icon">📋</span>
      </div>
    </div>

    <!-- Gauge + Map -->
    <div class="grid-gauge-map">
      <div class="panel">
        <div class="ph"><span class="ph-title">🌡️ Gas Level</span></div>
        <div class="pb gauge-wrap">
          <div class="gauge-ring safe" id="gauge-ring">
            <div class="g-ppm" id="live-ppm">0</div>
            <div class="g-unit">PPM</div>
          </div>
          <div class="status-pill online" id="live-pill">SYSTEM ONLINE</div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:.6rem" id="gauge-loc">—</div>
        </div>
      </div>

      <div class="panel" id="map-section">
        <div class="ph">
          <span class="ph-title">📍 Live Alert Map</span>
          <span style="font-size:.72rem;color:var(--muted)" id="map-label">No active alert</span>
        </div>
        <div id="map"></div>
      </div>
    </div>

    <!-- Logs -->
    <div class="panel" id="logs-section">
      <div class="ph">
        <span class="ph-title">📋 Activity Log</span>
        <div style="display:flex;align-items:center;gap:.7rem">
          <div class="search-bar">
            <span style="color:var(--muted)">🔍</span>
            <input type="text" id="logSearch" placeholder="Search logs…" oninput="filterLogs()">
          </div>
          <a href="<?= $clear_url ?>" class="btn-sm danger"
             onclick="return confirm('Delete ALL activity logs?')">🗑️ Clear</a>
        </div>
      </div>
      <div class="log-scroll">
        <table id="logTable">
          <thead>
            <tr><th>User</th><th>Role</th><th>Station</th><th>Action</th><th>Time</th></tr>
          </thead>
          <tbody>
          <?php while ($row = $logs->fetch_assoc()):
            $isLeak  = stripos($row['action'], 'Leak')  !== false;
            $isReset = stripos($row['action'], 'Reset') !== false;
            $tc = $isLeak ? 'tag-danger' : ($isReset ? 'tag-success' : 'tag-info');
          ?>
            <tr>
              <td style="font-weight:600"><?= htmlspecialchars($row['full_name']) ?></td>
              <td><span class="tag tag-muted"><?= strtoupper($row['role']) ?></span></td>
              <td style="color:var(--muted)"><?= htmlspecialchars($row['location']) ?></td>
              <td><span class="tag <?= $tc ?>"><?= htmlspecialchars($row['action']) ?></span></td>
              <td style="font-family:var(--mono);font-size:.75rem;color:var(--muted)">
                <?= date('M d, H:i:s', strtotime($row['created_at'])) ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Emergency Alert -->
<div id="sticky-alert">
  <h4>🚨 GAS LEAK DETECTED</h4>
  <p id="sticky-msg"></p>
  <button class="btn-ack" onclick="acknowledge()">✔ Acknowledge & Notify Staff</button>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const CHECK_URL   = '<?= $check_url ?>';
const LOG_ACT_URL = '<?= $log_act_url ?>';

// Clock
setInterval(() => {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-PH', {hour12:false});
}, 1000);

// Log search
function filterLogs() {
  const q = document.getElementById('logSearch').value.toLowerCase();
  document.querySelectorAll('#logTable tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// Map
const map = L.map('map').setView([8.3697, 124.8644], 16);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);
let marker = null;

// Poll
function poll() {
  fetch(CHECK_URL)
    .then(r => r.json())
    .then(d => {
      const active = d.is_active === 1;
      const acked  = d.acknowledged_by_admin === 1;
      const ppm    = d.ppm || (active ? 450 : 0);
      // Global toast notification
      if (active) {
        document.getElementById('toast-loc').textContent = '📍 ' + (d.location||'') + '  👤 ' + (d.triggered_by||'');
        document.getElementById('global-toast').classList.add('show');
      } else {
        document.getElementById('global-toast').classList.remove('show');
      }


      document.getElementById('live-ppm').textContent = ppm;
      document.getElementById('gauge-loc').textContent = active ? (d.location || '—') : '—';

      const ring    = document.getElementById('gauge-ring');
      const pill    = document.getElementById('live-pill');
      const sc      = document.getElementById('status-card');
      const scVal   = document.getElementById('stat-status');

      if (active) {
        ring.className = 'gauge-ring danger';
        pill.className = 'status-pill offline';
        pill.textContent = '⚠ EMERGENCY';
        sc.className = 'sc danger';
        scVal.textContent = 'ALERT';

        if (d.lat && d.lng) {
          const latlng = [d.lat, d.lng];
          if (!marker) marker = L.marker(latlng).addTo(map);
          else marker.setLatLng(latlng);
          marker.bindPopup(`<b>🚨 ${d.location}</b><br>By: ${d.triggered_by}`).openPopup();
          map.flyTo(latlng, 18, {animate:true, duration:1.5});
          document.getElementById('map-label').textContent = `Alert at ${d.location}`;
        }

        if (!acked) {
          // Show toast if new leak
          if (!prevActive) {
            showToast('danger', '🚨 GAS LEAK ALERT', '📍 ' + (d.location||'Unknown') + '  👤 ' + (d.triggered_by||''));
          }
          const sa = document.getElementById('sticky-alert');
          sa.style.display = 'block';
          document.getElementById('sticky-msg').textContent =
            `📍 ${d.location}  |  👤 ${d.triggered_by}  |  🕐 ${d.triggered_at || ''}`;
        } else {
          document.getElementById('sticky-alert').style.display = 'none';
        }
      } else {
        ring.className = 'gauge-ring safe';
        pill.className = 'status-pill online';
        pill.textContent = 'SYSTEM ONLINE';
        sc.className = 'sc success';
        scVal.textContent = 'SAFE';
        document.getElementById('sticky-alert').style.display = 'none';
        document.getElementById('map-label').textContent = 'No active alert';
        if (marker) { map.removeLayer(marker); marker = null; }
      }
    })
    .catch(() => {});
  prevActive = d.is_active === 1;
}

function acknowledge() {
  const fd = new FormData();
  fd.append('action', 'admin_ack');
  fetch(LOG_ACT_URL, {method:'POST', body:fd}).then(() => poll());
}


// Toast notification
let toastTimer = null;
function showToast(type, title, body, duration=5000) {
  const t = document.getElementById('notifToast');
  t.className = 'notif-toast show ' + type;
  document.getElementById('notifTitle').textContent = title;
  document.getElementById('notifBody').textContent  = body;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), duration);
}

// Track previous state for notifications
let prevActive = false;

setInterval(poll, 2000);
poll();
</script>
</body>
</html>