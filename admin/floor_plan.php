<?php
/**
 * FEATURE 5 — Interactive Floor Plan
 * Location: admin/floor_plan.php
 *
 * Shows a static floor plan with blinking red hotspots
 * on the affected station when a leak is detected.
 *
 * HOW TO CUSTOMIZE:
 * Replace the SVG floor plan below with your actual building layout.
 * Adjust the hotspot positions (data-x, data-y) to match your floor plan.
 */

require_once __DIR__ . '/../core/auth_guard.php';
guard('admin');

$user      = current_user();
$check_url = base_url() . 'auth/check_alert.php';
$dash_url  = base_url() . 'admin/admin_dashboard.php';
$logout_url= logout_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Floor Plan — GAS-SIMHOT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#060d18;--sb:#09131f;--panel:#0d1e30;--border:#163350;--accent:#00d4ff;--danger:#ff4c4c;--success:#00e5a0;--warn:#ffb300;--text:#cfe8ff;--muted:#4a7a9b;--mono:'Share Tech Mono',monospace;--sans:'Exo 2',sans-serif;--sw:240px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{display:flex;background:var(--bg);color:var(--text);font-family:var(--sans);font-size:.93rem;}
.sidebar{width:var(--sw);min-width:var(--sw);background:var(--sb);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:1.5rem 0;z-index:100;}
.sb-logo{padding:0 1.4rem 1.6rem;border-bottom:1px solid var(--border);}
.sb-logo h2{font-family:var(--mono);font-size:1.05rem;color:var(--accent);letter-spacing:.07em;}
.sb-logo p{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-top:.25rem;}
.sb-badge{display:inline-block;background:rgba(0,212,255,.1);border:1px solid rgba(0,212,255,.3);color:var(--accent);font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:2px 8px;border-radius:20px;margin-top:.5rem;}
.sb-nav{flex:1;padding:1rem 0;}
.nav-sec{padding:.4rem 1.4rem .2rem;font-size:.63rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1.4rem;color:var(--text);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .2s,color .2s;border-left:3px solid transparent;}
.nav-item:hover,.nav-item.active{background:rgba(0,212,255,.07);color:var(--accent);border-left-color:var(--accent);}
.sb-foot{padding:1rem 1.4rem;border-top:1px solid var(--border);}
.user-chip{display:flex;align-items:center;gap:.65rem;margin-bottom:.9rem;}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#0072ff);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#060d18;flex-shrink:0;}
.u-name{font-size:.85rem;font-weight:600;}
.u-role{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;}
.btn-logout{display:block;width:100%;background:rgba(255,76,76,.12);border:1px solid rgba(255,76,76,.3);color:var(--danger);border-radius:6px;padding:.5rem;font-size:.8rem;font-weight:600;text-align:center;text-decoration:none;transition:background .2s;}
.btn-logout:hover{background:rgba(255,76,76,.22);}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 1.8rem;height:56px;border-bottom:1px solid var(--border);background:var(--sb);flex-shrink:0;}
.topbar-title{font-family:var(--mono);font-size:.95rem;color:var(--accent);letter-spacing:.08em;}
.live-badge{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--success);font-family:var(--mono);}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(0,229,160,.6)}50%{opacity:.6;box-shadow:0 0 0 6px rgba(0,229,160,0)}}
.btn-back{display:inline-flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--muted);text-decoration:none;background:rgba(0,212,255,.07);border:1px solid var(--border);border-radius:6px;padding:.3rem .8rem;transition:color .2s;}
.btn-back:hover{color:var(--accent);}
.content{flex:1;overflow-y:auto;padding:1.4rem 1.8rem;}
.content::-webkit-scrollbar{width:5px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}

/* Status bar */
.status-bar{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:1rem 1.5rem;margin-bottom:1.2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.8rem;}
.status-bar.alert{border-color:rgba(255,76,76,.5);background:#100808;animation:alertBorder 1s ease-in-out infinite alternate;}
@keyframes alertBorder{from{border-color:rgba(255,76,76,.3)}to{border-color:rgba(255,76,76,.8)}}
.status-info{display:flex;align-items:center;gap:.8rem;}
.status-pill{display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .9rem;border-radius:20px;font-size:.78rem;font-weight:700;letter-spacing:.06em;font-family:var(--mono);text-transform:uppercase;}
.pill-safe{background:rgba(0,229,160,.15);border:1px solid rgba(0,229,160,.4);color:var(--success);}
.pill-alert{background:rgba(255,76,76,.15);border:1px solid rgba(255,76,76,.4);color:var(--danger);animation:blinkPill 1s linear infinite;}
@keyframes blinkPill{50%{opacity:.4}}
.status-detail{font-size:.8rem;color:var(--muted);}
.status-detail strong{color:var(--text);}

/* Floor plan container */
.floor-plan-wrap{background:var(--panel);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.fp-header{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--border);}
.fp-title{font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);}
.fp-legend{display:flex;align-items:center;gap:1.2rem;font-size:.72rem;color:var(--muted);}
.leg-item{display:flex;align-items:center;gap:.4rem;}
.leg-dot{width:10px;height:10px;border-radius:50%;}
.leg-dot.safe{background:var(--success);}
.leg-dot.alert{background:var(--danger);}

/* Floor plan SVG area */
.floor-plan-area{position:relative;padding:1.5rem;}

/* The floor plan SVG */
.floor-svg{width:100%;max-width:800px;display:block;margin:0 auto;border-radius:8px;}

/* Hotspot dots */
.hotspot{
  position:absolute;
  transform:translate(-50%, -50%);
  cursor:pointer;
  z-index:10;
}

.hotspot-ring{
  width:24px;height:24px;
  border-radius:50%;
  background:rgba(0,229,160,.3);
  border:2px solid var(--success);
  display:flex;align-items:center;justify-content:center;
  transition:all .4s;
  position:relative;
}

/* Blinking red hotspot on leak */
.hotspot.active .hotspot-ring{
  background:rgba(255,76,76,.4);
  border-color:var(--danger);
  animation:hotspotPulse .8s ease-in-out infinite alternate;
  box-shadow:0 0 0 0 rgba(255,76,76,.6);
}

@keyframes hotspotPulse{
  from{transform:scale(1);box-shadow:0 0 0 0 rgba(255,76,76,.6);}
  to{transform:scale(1.3);box-shadow:0 0 0 12px rgba(255,76,76,0);}
}

.hotspot-inner{
  width:10px;height:10px;
  border-radius:50%;
  background:var(--success);
  transition:background .4s;
}
.hotspot.active .hotspot-inner{
  background:var(--danger);
  animation:innerBlink .5s linear infinite;
}
@keyframes innerBlink{50%{opacity:.2}}

/* Tooltip */
.hotspot-tooltip{
  position:absolute;
  bottom:calc(100% + 8px);
  left:50%;
  transform:translateX(-50%);
  background:#0d1e30;
  border:1px solid var(--border);
  border-radius:6px;
  padding:.4rem .7rem;
  font-size:.72rem;
  white-space:nowrap;
  color:var(--text);
  pointer-events:none;
  opacity:0;
  transition:opacity .2s;
  font-family:var(--mono);
}
.hotspot:hover .hotspot-tooltip{opacity:1;}
.hotspot.active .hotspot-tooltip{
  border-color:rgba(255,76,76,.5);
  color:var(--danger);
  opacity:1;
}

/* Alert details panel */
.alert-detail-panel{
  display:none;
  margin-top:1.2rem;
  background:#100808;
  border:1px solid rgba(255,76,76,.4);
  border-radius:10px;
  padding:1.2rem 1.5rem;
}
.alert-detail-panel.show{display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;}
.adp-icon{font-size:2.5rem;}
.adp-info h4{font-family:var(--mono);color:var(--danger);font-size:.95rem;margin-bottom:.3rem;letter-spacing:.06em;}
.adp-info p{font-size:.82rem;color:#ffb3c6;line-height:1.5;}
.btn-ack{background:var(--danger);color:white;border:none;border-radius:6px;padding:.55rem 1.2rem;font-size:.82rem;font-weight:700;cursor:pointer;font-family:var(--mono);transition:background .2s;margin-left:auto;}
.btn-ack:hover{background:#ff6b6b;}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-logo">
    <h2>🛡️ GAS-SIMHOT</h2>
    <p>Floor Plan Monitor</p>
    <span class="sb-badge">Administrator</span>
  </div>
  <nav class="sb-nav">
    <div class="nav-sec">Navigation</div>
    <a class="nav-item" href="<?= $dash_url ?>">📊 Dashboard</a>
    <a class="nav-item active" href="#">🏠 Floor Plan</a>
    <a class="nav-item" href="<?= base_url() ?>admin/analytics.php">📈 Analytics</a>
    <a class="nav-item" href="<?= base_url() ?>admin/alert_history.php">🚨 Alert History</a>
    <a class="nav-item" href="<?= base_url() ?>user/profile.php">👤 My Profile</a>
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
    <span class="topbar-title">ADMIN / FLOOR PLAN MONITOR</span>
    <div style="display:flex;align-items:center;gap:.8rem">
      <div class="live-badge"><span class="live-dot"></span>LIVE · <span id="clock">--:--:--</span></div>
      <a href="<?= $dash_url ?>" class="btn-back">← Dashboard</a>
    </div>
  </div>

  <div class="content">

    <!-- Status Bar -->
    <div class="status-bar" id="statusBar">
      <div class="status-info">
        <span class="status-pill pill-safe" id="statusPill">✅ SYSTEM SAFE</span>
        <div class="status-detail" id="statusDetail">All stations normal — no gas detected</div>
      </div>
      <div style="font-family:var(--mono);font-size:.78rem;color:var(--muted)">
        Updated: <span id="lastUpdate">--:--:--</span>
      </div>
    </div>

    <!-- Floor Plan -->
    <div class="floor-plan-wrap">
      <div class="fp-header">
        <span class="fp-title">🏠 Building Floor Plan</span>
        <div class="fp-legend">
          <span class="leg-item"><span class="leg-dot safe"></span> Normal</span>
          <span class="leg-item"><span class="leg-dot alert"></span> Gas Leak</span>
        </div>
      </div>

      <div class="floor-plan-area" id="floorPlanArea">

        <!-- ── SVG FLOOR PLAN ─────────────────────────────────
             This is a sample floor plan. Replace with your actual
             building layout SVG or image.
             Hotspot positions are set with data-station attribute.
        ──────────────────────────────────────────────────────── -->
        <svg class="floor-svg" viewBox="0 0 800 500" xmlns="http://www.w3.org/2000/svg">
          <!-- Building outline -->
          <rect x="20" y="20" width="760" height="460" rx="8" fill="#0a1628" stroke="#163350" stroke-width="2"/>

          <!-- Title -->
          <text x="400" y="50" text-anchor="middle" fill="#4a7a9b" font-family="Share Tech Mono" font-size="14">BUILDING FLOOR PLAN</text>

          <!-- ── KITCHEN (top-left) ── -->
          <rect x="40" y="70" width="200" height="160" rx="4" fill="#0d1e30" stroke="#163350" stroke-width="1.5"/>
          <text x="140" y="145" text-anchor="middle" fill="#4a7a9b" font-family="Exo 2" font-size="12" font-weight="600">KITCHEN</text>
          <text x="140" y="162" text-anchor="middle" fill="#2a4a6b" font-family="Exo 2" font-size="10">Cooking Station</text>
          <!-- Kitchen icon -->
          <text x="140" y="130" text-anchor="middle" font-size="20">🍳</text>

          <!-- ── LABORATORY (top-right) ── -->
          <rect x="280" y="70" width="220" height="160" rx="4" fill="#0d1e30" stroke="#163350" stroke-width="1.5"/>
          <text x="390" y="145" text-anchor="middle" fill="#4a7a9b" font-family="Exo 2" font-size="12" font-weight="600">LABORATORY</text>
          <text x="390" y="162" text-anchor="middle" fill="#2a4a6b" font-family="Exo 2" font-size="10">Research Station</text>
          <text x="390" y="130" text-anchor="middle" font-size="20">🔬</text>

          <!-- ── WAREHOUSE (bottom-left) ── -->
          <rect x="40" y="270" width="200" height="180" rx="4" fill="#0d1e30" stroke="#163350" stroke-width="1.5"/>
          <text x="140" y="355" text-anchor="middle" fill="#4a7a9b" font-family="Exo 2" font-size="12" font-weight="600">WAREHOUSE</text>
          <text x="140" y="372" text-anchor="middle" fill="#2a4a6b" font-family="Exo 2" font-size="10">Storage Area</text>
          <text x="140" y="340" text-anchor="middle" font-size="20">📦</text>

          <!-- ── MAIN OFFICE (bottom-right) ── -->
          <rect x="280" y="270" width="220" height="180" rx="4" fill="#0d1e30" stroke="#163350" stroke-width="1.5"/>
          <text x="390" y="355" text-anchor="middle" fill="#4a7a9b" font-family="Exo 2" font-size="12" font-weight="600">MAIN OFFICE</text>
          <text x="390" y="372" text-anchor="middle" fill="#2a4a6b" font-family="Exo 2" font-size="10">Administration</text>
          <text x="390" y="340" text-anchor="middle" font-size="20">🏢</text>

          <!-- ── HALLWAYS ── -->
          <rect x="240" y="70" width="40" height="380" fill="#081018" stroke="none"/>
          <rect x="40" y="230" width="460" height="40" fill="#081018" stroke="none"/>

          <!-- Corridor labels -->
          <text x="260" y="200" text-anchor="middle" fill="#1a3a5c" font-family="Exo 2" font-size="9" transform="rotate(-90, 260, 200)">CORRIDOR</text>
          <text x="270" y="258" text-anchor="middle" fill="#1a3a5c" font-family="Exo 2" font-size="9">HALLWAY</text>

          <!-- ── RIGHT SIDE PANEL ── -->
          <rect x="540" y="70" width="220" height="380" rx="4" fill="#080f1a" stroke="#163350" stroke-width="1.5" stroke-dasharray="5,3"/>
          <text x="650" y="260" text-anchor="middle" fill="#1a3a5c" font-family="Exo 2" font-size="11">EXPANSION AREA</text>
          <text x="650" y="278" text-anchor="middle" fill="#1a3a5c" font-family="Exo 2" font-size="9">(Future Use)</text>

          <!-- Door indicators -->
          <line x1="40" y1="230" x2="60" y2="230" stroke="#00d4ff" stroke-width="2"/>
          <line x1="280" y1="230" x2="260" y2="230" stroke="#00d4ff" stroke-width="2"/>
          <line x1="40" y1="270" x2="60" y2="270" stroke="#00d4ff" stroke-width="2"/>
          <line x1="280" y1="270" x2="260" y2="270" stroke="#00d4ff" stroke-width="2"/>
        </svg>

        <!-- ── HOTSPOTS ──────────────────────────────────────
             Position these over each room on the floor plan.
             Adjust left/top % to match your floor plan layout.
             data-station must match EXACTLY the location in the DB.
        ───────────────────────────────────────────────────── -->

        <div class="hotspot" id="hs-Kitchen"
             style="left:21.5%;top:38%"
             data-station="Kitchen"
             onclick="hotspotClick('Kitchen')">
          <div class="hotspot-ring">
            <div class="hotspot-inner"></div>
          </div>
          <div class="hotspot-tooltip">📍 Kitchen</div>
        </div>

        <div class="hotspot" id="hs-Laboratory"
             style="left:49%;top:38%"
             data-station="Laboratory"
             onclick="hotspotClick('Laboratory')">
          <div class="hotspot-ring">
            <div class="hotspot-inner"></div>
          </div>
          <div class="hotspot-tooltip">📍 Laboratory</div>
        </div>

        <div class="hotspot" id="hs-Warehouse"
             style="left:21.5%;top:75%"
             data-station="Warehouse"
             onclick="hotspotClick('Warehouse')">
          <div class="hotspot-ring">
            <div class="hotspot-inner"></div>
          </div>
          <div class="hotspot-tooltip">📍 Warehouse</div>
        </div>

        <div class="hotspot" id="hs-MainOffice"
             style="left:49%;top:75%"
             data-station="Main Office"
             onclick="hotspotClick('Main Office')">
          <div class="hotspot-ring">
            <div class="hotspot-inner"></div>
          </div>
          <div class="hotspot-tooltip">📍 Main Office</div>
        </div>

      </div><!-- /floor-plan-area -->

      <!-- Alert detail panel (appears below floor plan on leak) -->
      <div class="alert-detail-panel" id="alertDetailPanel">
        <span class="adp-icon">🚨</span>
        <div class="adp-info">
          <h4 id="adpTitle">GAS LEAK DETECTED</h4>
          <p id="adpMsg"></p>
        </div>
        <button class="btn-ack" onclick="acknowledgeFromFloorPlan()">✔ Acknowledge</button>
      </div>

    </div><!-- /floor-plan-wrap -->

  </div>
</div>

<script>
const CHECK_URL   = '<?= $check_url ?>';
const LOG_ACT_URL = '<?= base_url() ?>core/log_action.php';

// Clock
setInterval(() => {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-PH', {hour12:false});
}, 1000);

// Track last state
let lastActive = false;

// Clear all hotspots
function clearAllHotspots() {
  document.querySelectorAll('.hotspot').forEach(h => h.classList.remove('active'));
}

// Activate hotspot for a specific station
function activateHotspot(stationName) {
  clearAllHotspots();
  // Find matching hotspot
  document.querySelectorAll('.hotspot').forEach(h => {
    if (h.dataset.station === stationName) {
      h.classList.add('active');
    }
  });
}

// Hotspot click — show info
function hotspotClick(station) {
  // Could navigate to more details if needed
  const el = document.getElementById('hs-' + station.replace(' ', ''));
  if (el) {
    const isActive = el.classList.contains('active');
    if (isActive) {
      alert('🚨 Active leak at: ' + station + '\nAdmin acknowledgment required.');
    }
  }
}

// Acknowledge from floor plan
function acknowledgeFromFloorPlan() {
  const fd = new FormData();
  fd.append('action', 'admin_ack');
  fetch(LOG_ACT_URL, {method:'POST', body:fd}).then(() => poll());
}

// Real-time poll
function poll() {
  fetch(CHECK_URL)
    .then(r => r.json())
    .then(d => {
      const active = d.is_active === 1;
      const acked  = d.acknowledged_by_admin === 1;
      const bar    = document.getElementById('statusBar');
      const pill   = document.getElementById('statusPill');
      const detail = document.getElementById('statusDetail');
      const update = document.getElementById('lastUpdate');
      const panel  = document.getElementById('alertDetailPanel');

      update.textContent = new Date().toLocaleTimeString('en-PH', {hour12:false});

      if (active) {
        // Activate hotspot
        activateHotspot(d.location || '');

        // Update status bar
        bar.className = 'status-bar alert';
        pill.className = 'status-pill pill-alert';
        pill.textContent = '🚨 GAS LEAK DETECTED';
        detail.innerHTML = `Station: <strong>${d.location}</strong> &nbsp;|&nbsp; Triggered by: <strong>${d.triggered_by}</strong>`;
        detail.style.color = '#ff8080';

        // Show alert detail panel
        if (!acked) {
          panel.classList.add('show');
          document.getElementById('adpTitle').textContent = '🚨 GAS LEAK — ' + (d.location || 'Unknown Station');
          document.getElementById('adpMsg').textContent =
            'Triggered by: ' + (d.triggered_by || 'Unknown') +
            '  |  Time: ' + (d.triggered_at || 'N/A') +
            '  |  PPM: ' + (d.ppm || 450);
        } else {
          panel.classList.remove('show');
        }
      } else {
        // Reset
        clearAllHotspots();
        bar.className = 'status-bar';
        pill.className = 'status-pill pill-safe';
        pill.textContent = '✅ SYSTEM SAFE';
        detail.textContent = 'All stations normal — no gas detected';
        detail.style.color = '';
        panel.classList.remove('show');
      }

      lastActive = active;
    })
    .catch(() => {});
}

setInterval(poll, 2000);
poll();
</script>
</body>
</html>