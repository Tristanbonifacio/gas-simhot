<?php
/**
 * FEATURE 4 — Sensor Health / Digital Inventory
 * Location: manager/sensor_health.php
 *
 * Tracks sensor installation dates per station.
 * Flags sensors older than 6 months as "Maintenance Required".
 */

require_once __DIR__ . '/../core/auth_guard.php';
guard('manager');

$user = current_user();
$db   = db();

// ── Handle Add/Update Sensor ──────────────────────────────────
if (isset($_POST['save_sensor'])) {
    $station  = $db->real_escape_string(trim($_POST['station_name']));
    $model    = $db->real_escape_string(trim($_POST['model']));
    $serial   = $db->real_escape_string(trim($_POST['serial_number']));
    $inst_dt  = $db->real_escape_string($_POST['installation_date']);
    $maint_dt = $_POST['last_maintenance'] ? "'" . $db->real_escape_string($_POST['last_maintenance']) . "'" : 'NULL';
    $notes    = $db->real_escape_string(trim($_POST['notes']));

    $db->query("INSERT INTO sensors (station_name, model, serial_number, installation_date, last_maintenance, notes)
                VALUES ('$station', '$model', '$serial', '$inst_dt', $maint_dt, '$notes')
                ON DUPLICATE KEY UPDATE
                    model='$model',
                    serial_number='$serial',
                    installation_date='$inst_dt',
                    last_maintenance=$maint_dt,
                    notes='$notes',
                    status='active'");

    header('Location: sensor_health.php?msg=Sensor+updated');
    exit();
}

// ── Handle Mark as Maintained ─────────────────────────────────
if (isset($_GET['maintain'])) {
    $id = (int)$_GET['maintain'];
    $db->query("UPDATE sensors SET last_maintenance=CURDATE(), status='active' WHERE id=$id");
    header('Location: sensor_health.php?msg=Sensor+marked+as+maintained');
    exit();
}

// ── Fetch all sensors with health status ──────────────────────
$sensors_res = $db->query("
    SELECT *,
        DATEDIFF(CURDATE(), installation_date) AS days_installed,
        DATEDIFF(CURDATE(), COALESCE(last_maintenance, installation_date)) AS days_since_maintenance
    FROM sensors
    ORDER BY station_name ASC
");

$sensors = [];
$need_maintenance = 0;
while ($s = $sensors_res->fetch_assoc()) {
    // Flag if >180 days (6 months) since last maintenance or install
    $s['needs_maintenance'] = $s['days_since_maintenance'] > 180;
    $s['health_pct'] = max(0, 100 - round(($s['days_since_maintenance'] / 365) * 100));

    if ($s['needs_maintenance']) {
        $need_maintenance++;
        $db->query("UPDATE sensors SET status='maintenance_required' WHERE id={$s['id']}");
    }
    $sensors[] = $s;
}

$msg       = htmlspecialchars($_GET['msg'] ?? '');
$dash_url  = base_url() . 'manager/manager_dashboard.php';
$logout_url= logout_url();
$locations = ['Kitchen','Laboratory','Warehouse','Main Office'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sensor Health — GAS-SIMHOT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#06100f;--sb:#091a18;--panel:#0d2320;--border:#163b38;--accent:#00e5a0;--danger:#ff4c4c;--blue:#00d4ff;--warn:#ffb300;--text:#c6f0ea;--muted:#3d7a72;--mono:'Share Tech Mono',monospace;--sans:'Exo 2',sans-serif;--sw:240px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{display:flex;background:var(--bg);color:var(--text);font-family:var(--sans);font-size:.93rem;}
.sidebar{width:var(--sw);min-width:var(--sw);background:var(--sb);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:1.5rem 0;z-index:100;}
.sb-logo{padding:0 1.4rem 1.6rem;border-bottom:1px solid var(--border);}
.sb-logo h2{font-family:var(--mono);font-size:1.05rem;color:var(--accent);letter-spacing:.07em;}
.sb-logo p{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-top:.25rem;}
.sb-nav{flex:1;padding:1rem 0;}
.nav-sec{padding:.4rem 1.4rem .2rem;font-size:.63rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1.4rem;color:var(--text);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .2s,color .2s;border-left:3px solid transparent;}
.nav-item:hover,.nav-item.active{background:rgba(0,229,160,.07);color:var(--accent);border-left-color:var(--accent);}
.sb-foot{padding:1rem 1.4rem;border-top:1px solid var(--border);}
.user-chip{display:flex;align-items:center;gap:.65rem;margin-bottom:.9rem;}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#008f5c);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#06100f;flex-shrink:0;}
.u-name{font-size:.85rem;font-weight:600;}
.u-role{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;}
.btn-logout{display:block;width:100%;background:rgba(255,76,76,.12);border:1px solid rgba(255,76,76,.3);color:var(--danger);border-radius:6px;padding:.5rem;font-size:.8rem;font-weight:600;text-align:center;text-decoration:none;transition:background .2s;}
.btn-logout:hover{background:rgba(255,76,76,.22);}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 1.8rem;height:56px;border-bottom:1px solid var(--border);background:var(--sb);flex-shrink:0;}
.topbar-title{font-family:var(--mono);font-size:.95rem;color:var(--accent);letter-spacing:.08em;}
.btn-back{display:inline-flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--muted);text-decoration:none;background:rgba(0,229,160,.07);border:1px solid var(--border);border-radius:6px;padding:.3rem .8rem;transition:color .2s;}
.btn-back:hover{color:var(--accent);}
.content{flex:1;overflow-y:auto;padding:1.4rem 1.8rem;}
.content::-webkit-scrollbar{width:5px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}

/* Alert banner */
.alert-warn{background:rgba(255,179,0,.1);border:1px solid rgba(255,179,0,.4);border-radius:10px;padding:1rem 1.2rem;margin-bottom:1.4rem;display:flex;align-items:center;gap:.8rem;font-size:.85rem;color:var(--warn);}
.alert-success{background:rgba(0,229,160,.1);border:1px solid rgba(0,229,160,.3);border-radius:8px;padding:.7rem 1.1rem;font-size:.82rem;color:var(--accent);margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem;}

/* Stats row */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.4rem;}
.sc{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:1.1rem 1.3rem;position:relative;overflow:hidden;}
.sc-label{font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;}
.sc-value{font-family:var(--mono);font-size:2rem;font-weight:700;line-height:1;}
.sc-icon{position:absolute;right:1rem;top:.9rem;font-size:1.6rem;opacity:.18;}
.sc.green{border-color:rgba(0,229,160,.3);} .sc.green .sc-value{color:var(--accent);}
.sc.warn{border-color:rgba(255,179,0,.3);}  .sc.warn  .sc-value{color:var(--warn);}
.sc.blue{border-color:rgba(0,212,255,.3);}  .sc.blue  .sc-value{color:var(--blue);}

/* Sensor cards */
.sensors-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.2rem;margin-bottom:1.4rem;}

.sensor-card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:1.5rem;transition:transform .2s;position:relative;overflow:hidden;}
.sensor-card:hover{transform:translateY(-3px);}
.sensor-card.needs-maintenance{border-color:rgba(255,179,0,.5);box-shadow:0 0 20px rgba(255,179,0,.1);}
.sensor-card.healthy{border-color:rgba(0,229,160,.3);}

.sensor-card::before{content:'';position:absolute;top:0;right:0;width:4px;height:100%;background:var(--accent);}
.sensor-card.needs-maintenance::before{background:var(--warn);}

.sensor-station{font-family:var(--mono);font-size:1rem;font-weight:700;color:var(--accent);margin-bottom:.3rem;letter-spacing:.05em;}
.sensor-card.needs-maintenance .sensor-station{color:var(--warn);}
.sensor-model{font-size:.78rem;color:var(--muted);margin-bottom:1rem;}

.sensor-info{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:1rem;}
.si-item{}
.si-label{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem;}
.si-value{font-size:.82rem;color:var(--text);}

/* Health bar */
.health-bar-wrap{margin-bottom:.9rem;}
.health-bar-label{display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-bottom:.3rem;}
.health-bar-bg{background:rgba(22,59,56,.5);border-radius:10px;height:6px;overflow:hidden;}
.health-bar{height:100%;border-radius:10px;transition:width .6s ease;}
.health-bar.good{background:var(--accent);}
.health-bar.ok{background:var(--blue);}
.health-bar.warn{background:var(--warn);}
.health-bar.bad{background:var(--danger);}

/* Status badge */
.status-badge{display:inline-flex;align-items:center;gap:.3rem;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.8rem;}
.badge-active{background:rgba(0,229,160,.12);color:var(--accent);border:1px solid rgba(0,229,160,.3);}
.badge-maintenance{background:rgba(255,179,0,.12);color:var(--warn);border:1px solid rgba(255,179,0,.3);animation:badgePulse 2s ease-in-out infinite;}
@keyframes badgePulse{50%{box-shadow:0 0 10px rgba(255,179,0,.4);}}

/* Buttons */
.btn-maintain{background:var(--warn);color:#06100f;border:none;border-radius:6px;padding:.45rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;font-family:var(--sans);text-decoration:none;display:inline-block;transition:background .2s;}
.btn-maintain:hover{background:#ffc933;}
.btn-edit{background:rgba(0,229,160,.1);color:var(--accent);border:1px solid rgba(0,229,160,.3);border-radius:6px;padding:.45rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;font-family:var(--sans);text-decoration:none;display:inline-block;transition:background .2s;margin-left:.5rem;}
.btn-edit:hover{background:rgba(0,229,160,.2);}

/* Panel */
.panel{background:var(--panel);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.ph{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--border);}
.ph-title{font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);}
.pb{padding:1.4rem;}

/* Form */
.field{margin-bottom:1rem;}
.lbl{display:block;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem;}
.inp{width:100%;background:rgba(0,229,160,.04);border:1px solid var(--border);border-radius:6px;padding:.6rem .85rem;color:var(--text);font-family:var(--sans);font-size:.9rem;outline:none;appearance:none;transition:border-color .25s;}
.inp::placeholder{color:var(--muted);}
.inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(0,229,160,.15);}
.inp option{background:var(--panel);color:var(--text);}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
.btn-save{width:100%;background:var(--accent);color:#06100f;border:none;border-radius:6px;padding:.75rem;font-family:var(--mono);font-size:.9rem;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-save:hover{background:#33ffb8;}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-logo">
    <h2>🚀 GAS-SIMHOT</h2>
    <p>Sensor Health</p>
  </div>
  <nav class="sb-nav">
    <div class="nav-sec">Navigation</div>
    <a class="nav-item" href="<?= $dash_url ?>">📊 Dashboard</a>
    <a class="nav-item active" href="#">🔧 Sensor Health</a>
    <a class="nav-item" href="<?= base_url() ?>user/profile.php">👤 My Profile</a>
  </nav>
  <div class="sb-foot">
    <div class="user-chip">
      <div class="avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
      <div>
        <div class="u-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="u-role">Manager</div>
      </div>
    </div>
    <a href="<?= $logout_url ?>" class="btn-logout">⏻ Logout</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <span class="topbar-title">MANAGER / SENSOR HEALTH</span>
    <a href="<?= $dash_url ?>" class="btn-back">← Dashboard</a>
  </div>

  <div class="content">

    <?php if ($msg): ?>
      <div class="alert-success">✓ <?= $msg ?></div>
    <?php endif; ?>

    <?php if ($need_maintenance > 0): ?>
      <div class="alert-warn">
        <span style="font-size:1.5rem">⚠️</span>
        <div>
          <strong><?= $need_maintenance ?> sensor<?= $need_maintenance > 1 ? 's' : '' ?> require maintenance!</strong>
          <div style="font-size:.78rem;color:#997700;margin-top:.2rem">Sensors older than 6 months need to be checked and recalibrated.</div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="sc green">
        <div class="sc-label">Total Sensors</div>
        <div class="sc-value"><?= count($sensors) ?></div>
        <span class="sc-icon">🔧</span>
      </div>
      <div class="sc <?= $need_maintenance > 0 ? 'warn' : 'green' ?>">
        <div class="sc-label">Need Maintenance</div>
        <div class="sc-value"><?= $need_maintenance ?></div>
        <span class="sc-icon">⚠️</span>
      </div>
      <div class="sc blue">
        <div class="sc-label">Healthy</div>
        <div class="sc-value"><?= count($sensors) - $need_maintenance ?></div>
        <span class="sc-icon">✅</span>
      </div>
    </div>

    <!-- Sensor Cards -->
    <div class="sensors-grid">
      <?php foreach ($sensors as $s):
        $health = $s['health_pct'];
        $health_class = $health >= 70 ? 'good' : ($health >= 40 ? 'ok' : ($health >= 20 ? 'warn' : 'bad'));
        $card_class   = $s['needs_maintenance'] ? 'needs-maintenance' : 'healthy';
      ?>
      <div class="sensor-card <?= $card_class ?>">
        <div class="sensor-station">📍 <?= htmlspecialchars($s['station_name']) ?></div>
        <div class="sensor-model"><?= htmlspecialchars($s['model']) ?></div>

        <?php if ($s['needs_maintenance']): ?>
          <div class="status-badge badge-maintenance">⚠️ Maintenance Required</div>
        <?php else: ?>
          <div class="status-badge badge-active">✅ Active</div>
        <?php endif; ?>

        <div class="sensor-info">
          <div class="si-item">
            <div class="si-label">Installed</div>
            <div class="si-value"><?= date('M d, Y', strtotime($s['installation_date'])) ?></div>
          </div>
          <div class="si-item">
            <div class="si-label">Days Active</div>
            <div class="si-value" style="color:<?= $s['days_installed'] > 180 ? 'var(--warn)' : 'var(--accent)' ?>"><?= $s['days_installed'] ?> days</div>
          </div>
          <div class="si-item">
            <div class="si-label">Last Maintenance</div>
            <div class="si-value"><?= $s['last_maintenance'] ? date('M d, Y', strtotime($s['last_maintenance'])) : 'Never' ?></div>
          </div>
          <div class="si-item">
            <div class="si-label">Serial No.</div>
            <div class="si-value" style="font-family:var(--mono);font-size:.75rem"><?= $s['serial_number'] ?: 'N/A' ?></div>
          </div>
        </div>

        <div class="health-bar-wrap">
          <div class="health-bar-label">
            <span>Sensor Health</span>
            <span style="color:<?= $health >= 70 ? 'var(--accent)' : ($health >= 40 ? 'var(--blue)' : 'var(--warn)') ?>"><?= $health ?>%</span>
          </div>
          <div class="health-bar-bg">
            <div class="health-bar <?= $health_class ?>" style="width:<?= $health ?>%"></div>
          </div>
        </div>

        <?php if ($s['needs_maintenance']): ?>
          <a href="?maintain=<?= $s['id'] ?>"
             class="btn-maintain"
             onclick="return confirm('Mark <?= htmlspecialchars($s['station_name']) ?> sensor as maintained today?')">
            🔧 Mark as Maintained
          </a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Add/Update Sensor Form -->
    <div class="panel">
      <div class="ph">
        <span class="ph-title">➕ Add / Update Sensor</span>
      </div>
      <div class="pb">
        <form method="POST">
          <div class="row2">
            <div class="field">
              <label class="lbl">Station</label>
              <select name="station_name" class="inp" required>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= $loc ?>"><?= $loc ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label class="lbl">Sensor Model</label>
              <input type="text" name="model" class="inp" placeholder="MQ-2 Gas Sensor" value="MQ-2 Gas Sensor">
            </div>
          </div>
          <div class="row2">
            <div class="field">
              <label class="lbl">Serial Number</label>
              <input type="text" name="serial_number" class="inp" placeholder="SN-XXXX-XXXX">
            </div>
            <div class="field">
              <label class="lbl">Installation Date</label>
              <input type="date" name="installation_date" class="inp" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="row2">
            <div class="field">
              <label class="lbl">Last Maintenance Date</label>
              <input type="date" name="last_maintenance" class="inp">
            </div>
            <div class="field">
              <label class="lbl">Notes</label>
              <input type="text" name="notes" class="inp" placeholder="Optional notes…">
            </div>
          </div>
          <button type="submit" name="save_sensor" class="btn-save">💾 Save Sensor</button>
        </form>
      </div>
    </div>

  </div>
</div>

</body>
</html>