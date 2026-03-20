<?php
/**
 * Staff / User Dashboard.
 * Location: user/user_dashboard.php
 */

require_once __DIR__ . '/../core/auth_guard.php';
guard('staff');

$user = current_user();
$db   = db();
$uid  = $user['id'];

$logs = $db->query("
    SELECT action, created_at FROM user_activity_logs
    WHERE user_id = $uid ORDER BY created_at DESC LIMIT 20
");

$my_leaks  = (int)$db->query("SELECT COUNT(*) c FROM user_activity_logs WHERE user_id=$uid AND action LIKE '%Leak%'")->fetch_assoc()['c'];
$my_resets = (int)$db->query("SELECT COUNT(*) c FROM user_activity_logs WHERE user_id=$uid AND action LIKE '%Reset%'")->fetch_assoc()['c'];
$my_total  = (int)$db->query("SELECT COUNT(*) c FROM user_activity_logs WHERE user_id=$uid")->fetch_assoc()['c'];

$check_url   = base_url() . 'auth/check_alert.php';
$log_act_url = base_url() . 'core/log_action.php';
$logout_url  = logout_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Terminal — GAS-SIMHOT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#07090f;--sb:#0c0f1a;--panel:#101422;--border:#1e2640;--accent:#7c6fff;--danger:#ff4c7a;--success:#00e5a0;--blue:#00d4ff;--warn:#ffb300;--text:#d4d9f0;--muted:#4a5280;--mono:'Share Tech Mono',monospace;--sans:'Exo 2',sans-serif;--sw:240px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{display:flex;background:var(--bg);color:var(--text);font-family:var(--sans);font-size:.93rem;}
.sidebar{width:var(--sw);min-width:var(--sw);background:var(--sb);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:1.5rem 0;z-index:100;}
.sb-logo{padding:0 1.4rem 1.6rem;border-bottom:1px solid var(--border);}
.sb-logo h2{font-family:var(--mono);font-size:1.05rem;color:var(--accent);letter-spacing:.07em;}
.sb-logo p{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-top:.25rem;}
.sb-badge{display:inline-block;background:rgba(124,111,255,.12);border:1px solid rgba(124,111,255,.3);color:var(--accent);font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:2px 8px;border-radius:20px;margin-top:.5rem;}
.station{display:inline-block;background:rgba(0,212,255,.1);border:1px solid rgba(0,212,255,.25);color:var(--blue);font-size:.65rem;font-weight:600;padding:2px 8px;border-radius:20px;margin-top:.3rem;}
.sb-nav{flex:1;padding:1rem 0;}
.nav-sec{padding:.4rem 1.4rem .2rem;font-size:.63rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1.4rem;color:var(--text);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .2s,color .2s;border-left:3px solid transparent;}
.nav-item:hover,.nav-item.active{background:rgba(124,111,255,.08);color:var(--accent);border-left-color:var(--accent);}
.sb-foot{padding:1rem 1.4rem;border-top:1px solid var(--border);}
.user-chip{display:flex;align-items:center;gap:.65rem;margin-bottom:.9rem;}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#4a3fcf);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#07090f;flex-shrink:0;}
.u-name{font-size:.85rem;font-weight:600;}
.u-role{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;}
.btn-logout{display:block;width:100%;background:rgba(255,76,122,.12);border:1px solid rgba(255,76,122,.3);color:var(--danger);border-radius:6px;padding:.5rem;font-size:.8rem;font-weight:600;text-align:center;text-decoration:none;transition:background .2s;}
.btn-logout:hover{background:rgba(255,76,122,.22);}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 1.8rem;height:56px;border-bottom:1px solid var(--border);background:var(--sb);flex-shrink:0;}
.topbar-title{font-family:var(--mono);font-size:.95rem;color:var(--accent);letter-spacing:.08em;}
.live-badge{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--success);font-family:var(--mono);}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(0,229,160,.6)}50%{opacity:.6;box-shadow:0 0 0 6px rgba(0,229,160,0)}}
.content{flex:1;overflow-y:auto;padding:1.4rem 1.8rem;}
.content::-webkit-scrollbar{width:5px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.4rem;}
.sc{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:1.1rem 1.3rem;position:relative;overflow:hidden;transition:transform .2s;}
.sc:hover{transform:translateY(-3px);}
.sc-label{font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;}
.sc-value{font-family:var(--mono);font-size:2rem;font-weight:700;line-height:1;}
.sc-icon{position:absolute;right:1rem;top:.9rem;font-size:1.6rem;opacity:.18;}
.sc.purple{border-color:rgba(124,111,255,.3);} .sc.purple .sc-value{color:var(--accent);}
.sc.red{border-color:rgba(255,76,122,.3);} .sc.red .sc-value{color:var(--danger);}
.sc.green{border-color:rgba(0,229,160,.3);} .sc.green .sc-value{color:var(--success);}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;}
.panel{background:var(--panel);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.ph{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--border);}
.ph-title{font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);}
.pb{padding:1.2rem;}
.sensor-wrap{text-align:center;}
.sensor-ring{position:relative;width:160px;height:160px;border-radius:50%;border:10px solid var(--border);margin:.5rem auto 1rem;display:flex;align-items:center;justify-content:center;flex-direction:column;transition:border-color .4s,box-shadow .4s;}
.sensor-ring.safe{border-color:var(--success);box-shadow:0 0 28px rgba(0,229,160,.3);}
.sensor-ring.danger{border-color:var(--danger);box-shadow:0 0 36px rgba(255,76,122,.5);animation:dShake .3s infinite alternate;}
@keyframes dShake{from{transform:rotate(-.5deg)}to{transform:rotate(.5deg)}}
.s-ppm{font-family:var(--mono);font-size:2rem;font-weight:700;line-height:1;transition:color .4s;}
.s-unit{font-size:.7rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase;}
.bar-wrap{background:var(--border);border-radius:10px;height:8px;overflow:hidden;margin:.8rem 0;}
.bar{height:100%;border-radius:10px;transition:width .5s ease,background .4s;background:var(--success);width:0%;}
.status-txt{font-size:.82rem;color:var(--muted);font-family:var(--mono);text-transform:uppercase;letter-spacing:.06em;margin-bottom:1rem;}
.sim-btns{display:grid;grid-template-columns:1fr 1fr;gap:.7rem;}
.btn-leak{background:var(--danger);color:white;border:none;border-radius:8px;padding:.75rem;font-size:.9rem;font-weight:700;font-family:var(--mono);letter-spacing:.06em;cursor:pointer;transition:background .2s,transform .1s,box-shadow .2s;}
.btn-leak:hover{background:#ff6b93;box-shadow:0 0 20px rgba(255,76,122,.4);}
.btn-reset{background:rgba(0,229,160,.12);color:var(--success);border:1px solid rgba(0,229,160,.3);border-radius:8px;padding:.75rem;font-size:.9rem;font-weight:700;font-family:var(--mono);cursor:pointer;transition:background .2s;}
.btn-reset:hover{background:rgba(0,229,160,.22);}
.btn-leak:active,.btn-reset:active{transform:scale(.97);}
.alert-banner{display:none;border-radius:8px;padding:.8rem 1rem;font-size:.85rem;font-weight:600;margin-bottom:1rem;align-items:center;gap:.6rem;}
.alert-banner.show{display:flex;}
.ab-danger{background:rgba(255,76,122,.12);border:1px solid rgba(255,76,122,.4);color:var(--danger);animation:blinkB 1s linear infinite;}
.ab-ack{background:rgba(0,229,160,.1);border:1px solid rgba(0,229,160,.3);color:var(--success);}
@keyframes blinkB{50%{opacity:.5}}
.log-scroll{max-height:320px;overflow-y:auto;}
.log-scroll::-webkit-scrollbar{width:4px;}
.log-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
table{width:100%;border-collapse:collapse;font-size:.82rem;}
thead th{font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:.5rem .7rem;text-align:left;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--panel);}
tbody tr{border-bottom:1px solid rgba(30,38,64,.6);transition:background .15s;}
tbody tr:hover{background:rgba(124,111,255,.04);}
tbody td{padding:.45rem .7rem;vertical-align:middle;}
.tag{display:inline-block;padding:2px 7px;border-radius:4px;font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;}
.tag-danger{background:rgba(255,76,122,.15);color:var(--danger);}
.tag-success{background:rgba(0,229,160,.12);color:var(--success);}
.tag-info{background:rgba(0,212,255,.12);color:var(--blue);}
.tag-muted{background:rgba(74,82,128,.15);color:var(--muted);}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-logo">
    <h2>⚗️ GAS-SIMHOT</h2>
    <p>User Terminal</p>
    <span class="sb-badge">Staff</span><br>
    <span class="station">📍 <?= htmlspecialchars($user['location']) ?></span>
  </div>
  <nav class="sb-nav">
    <div class="nav-sec">Monitoring</div>
    <a class="nav-item active" href="#">📊 Sensor Monitor</a>
    <a class="nav-item" href="#logs-panel" onclick="document.getElementById('logs-panel').scrollIntoView({behavior:'smooth'});return false;">📋 My Log History</a>
  </nav>
  <div class="sb-foot">
    <div class="user-chip">
      <div class="avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
      <div>
        <div class="u-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="u-role"><?= htmlspecialchars($user['location']) ?></div>
      </div>
    </div>
    <a href="<?= $logout_url ?>" class="btn-logout">⏻ Logout</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <span class="topbar-title">STAFF / SENSOR TERMINAL</span>
    <div class="live-badge"><span class="live-dot"></span>MONITORING · <span id="clock">--:--:--</span></div>
  </div>

  <div class="content">
    <div class="stats-grid">
      <div class="sc purple"><div class="sc-label">Total Actions</div><div class="sc-value"><?= $my_total ?></div><span class="sc-icon">📋</span></div>
      <div class="sc red"><div class="sc-label">Leak Events</div><div class="sc-value"><?= $my_leaks ?></div><span class="sc-icon">🚨</span></div>
      <div class="sc green"><div class="sc-label">Resets</div><div class="sc-value"><?= $my_resets ?></div><span class="sc-icon">🔄</span></div>
    </div>

    <div class="grid2">
      <div class="panel">
        <div class="ph">
          <span class="ph-title">🌡️ Sensor Monitor</span>
          <span style="font-size:.72rem;color:var(--muted)">Station: <?= htmlspecialchars($user['location']) ?></span>
        </div>
        <div class="pb sensor-wrap">
          <div class="alert-banner ab-danger" id="banner-leak"><span>🚨</span><span>GAS LEAK DETECTED! Admin has been notified.</span></div>
          <div class="alert-banner ab-ack" id="banner-ack"><span>✅</span><span>Admin acknowledged. Under control. <span id="ack-time" style="font-family:var(--mono);margin-left:.3rem"></span></span></div>

          <div class="sensor-ring safe" id="sensor-ring">
            <div class="s-ppm" id="sensor-ppm" style="color:var(--success)">0</div>
            <div class="s-unit">PPM</div>
          </div>
          <div class="bar-wrap"><div class="bar" id="ppm-bar"></div></div>
          <div class="status-txt" id="status-txt">System Normal</div>

          <div class="sim-btns">
            <button class="btn-leak" onclick="triggerLeak()">🚨 Simulate Leak</button>
            <button class="btn-reset" onclick="resetSystem()">🔄 Reset System</button>
          </div>
        </div>
      </div>

      <div class="panel" id="logs-panel">
        <div class="ph">
          <span class="ph-title">📋 My Actions</span>
          <span style="font-size:.72rem;color:var(--muted)">Last 20</span>
        </div>
        <div class="log-scroll" style="padding:.5rem 1rem">
          <table>
            <thead><tr><th>Action</th><th>Timestamp</th></tr></thead>
            <tbody>
            <?php while ($l = $logs->fetch_assoc()):
              $isLeak  = stripos($l['action'], 'Leak')  !== false;
              $isReset = stripos($l['action'], 'Reset') !== false;
              $tc = $isLeak ? 'tag-danger' : ($isReset ? 'tag-success' : 'tag-info');
            ?>
              <tr>
                <td><span class="tag <?= $tc ?>"><?= htmlspecialchars($l['action']) ?></span></td>
                <td style="font-family:var(--mono);font-size:.72rem;color:var(--muted)"><?= date('M d, H:i:s', strtotime($l['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const CHECK_URL   = '<?= $check_url ?>';
const LOG_ACT_URL = '<?= $log_act_url ?>';

setInterval(() => {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-PH',{hour12:false});
}, 1000);

let audioCtx = null, buzzerInterval = null;

function startBuzzer() {
  if (buzzerInterval) return;
  if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  buzzerInterval = setInterval(() => {
    const osc = audioCtx.createOscillator(), gain = audioCtx.createGain();
    osc.type = 'square'; osc.frequency.value = 880;
    gain.gain.setValueAtTime(.08, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(.0001, audioCtx.currentTime + .45);
    osc.connect(gain); gain.connect(audioCtx.destination);
    osc.start(); osc.stop(audioCtx.currentTime + .45);
  }, 600);
}
function stopBuzzer() { clearInterval(buzzerInterval); buzzerInterval = null; }

function setGauge(ppm, state) {
  const ring  = document.getElementById('sensor-ring');
  const ppmEl = document.getElementById('sensor-ppm');
  const bar   = document.getElementById('ppm-bar');
  const stTxt = document.getElementById('status-txt');
  ppmEl.textContent = ppm;
  bar.style.width   = Math.min((ppm/1000)*100, 100) + '%';
  if (state === 'danger') {
    ring.className = 'sensor-ring danger';
    ppmEl.style.color = 'var(--danger)'; bar.style.background = 'var(--danger)';
    stTxt.textContent = '⚠ LEAK DETECTED'; stTxt.style.color = 'var(--danger)';
  } else {
    ring.className = 'sensor-ring safe';
    ppmEl.style.color = 'var(--success)'; bar.style.background = 'var(--success)';
    stTxt.textContent = 'SYSTEM NORMAL'; stTxt.style.color = 'var(--muted)';
  }
}

function showBanner(which) {
  document.getElementById('banner-leak').classList.remove('show');
  document.getElementById('banner-ack').classList.remove('show');
  if (which) document.getElementById('banner-' + which).classList.add('show');
}

function triggerLeak() {
  setGauge(450, 'danger'); showBanner('leak'); startBuzzer();
  logAction('Leak Detected');
}
function resetSystem() {
  setGauge(0, 'safe'); showBanner(null); stopBuzzer();
  logAction('System Reset');
}
function logAction(act) {
  const fd = new FormData();
  fd.append('action', act);
  fetch(LOG_ACT_URL, {method:'POST', body:fd}).catch(()=>{});
}

setInterval(() => {
  fetch(CHECK_URL).then(r => r.json()).then(d => {
    const active = d.is_active === 1;
    const acked  = d.acknowledged_by_admin === 1;
    if (active && !acked) {
      setGauge(d.ppm || 450, 'danger'); showBanner('leak'); startBuzzer();
    } else if (acked) {
      setGauge(d.ppm || 450, 'danger'); showBanner('ack'); stopBuzzer();
      if (d.ack_time) document.getElementById('ack-time').textContent = d.ack_time;
    } else {
      setGauge(0, 'safe'); showBanner(null); stopBuzzer();
    }
  }).catch(()=>{});
}, 2000);
</script>
</body>
</html>
