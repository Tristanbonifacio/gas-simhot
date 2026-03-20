<?php
/**
 * Manager Dashboard.
 * Location: manager/manager_dashboard.php
 */

require_once __DIR__ . '/../core/auth_guard.php';
guard('manager');

$user = current_user();
$db   = db();

// Delete user
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $db->query("DELETE FROM users WHERE id = $del_id AND role != 'manager'");
    header('Location: ' . base_url() . 'manager/manager_dashboard.php?msg=User+removed');
    exit();
}

// Add user
if (isset($_POST['add_user'])) {
    $fn  = $db->real_escape_string(trim($_POST['full_name']));
    $un  = $db->real_escape_string(trim($_POST['username']));
    $pw  = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $ro  = in_array($_POST['role'], ['staff','admin']) ? $_POST['role'] : 'staff';
    $lo  = $db->real_escape_string(trim($_POST['location']));

    $stmt = $db->prepare("INSERT INTO users (full_name, username, password, role, location) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $fn, $un, $pw, $ro, $lo);
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: ' . base_url() . 'manager/manager_dashboard.php?msg=Personnel+added');
        exit();
    }
    $add_error = $db->error;
    $stmt->close();
}

// Stats
$total_users  = (int)$db->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_staff  = (int)$db->query("SELECT COUNT(*) c FROM users WHERE role='staff'")->fetch_assoc()['c'];
$total_admins = (int)$db->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$is_active    = (int)$db->query("SELECT is_active FROM system_status WHERE id=1")->fetch_assoc()['is_active'];
$users        = $db->query("SELECT * FROM users ORDER BY role DESC, full_name ASC");
$audit        = $db->query("
    SELECT l.action, l.created_at, u.full_name, u.role
    FROM user_activity_logs l JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC LIMIT 30
");

$msg        = htmlspecialchars($_GET['msg'] ?? '');
$add_error  = $add_error ?? '';
$locations  = ['Kitchen','Laboratory','Warehouse','Main Office'];
$check_url  = base_url() . 'auth/check_alert.php';
$logout_url = logout_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manager Dashboard — GAS-SIMHOT</title>
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
.sb-badge{display:inline-block;background:rgba(0,229,160,.12);border:1px solid rgba(0,229,160,.3);color:var(--accent);font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:2px 8px;border-radius:20px;margin-top:.5rem;}
.sb-nav{flex:1;padding:1rem 0;}
.nav-sec{padding:.4rem 1.4rem .2rem;font-size:.63rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1.4rem;color:var(--text);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .2s,color .2s;border-left:3px solid transparent;cursor:pointer;}
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
.live-badge{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--blue);font-family:var(--mono);}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--blue);animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(0,212,255,.6)}50%{opacity:.6;box-shadow:0 0 0 6px rgba(0,212,255,0)}}
.content{flex:1;overflow-y:auto;padding:1.4rem 1.8rem;}
.content::-webkit-scrollbar{width:5px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.4rem;}
.sc{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:1.1rem 1.3rem;position:relative;overflow:hidden;transition:transform .2s;}
.sc:hover{transform:translateY(-3px);}
.sc-label{font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;}
.sc-value{font-family:var(--mono);font-size:2rem;font-weight:700;line-height:1;}
.sc-icon{position:absolute;right:1rem;top:.9rem;font-size:1.6rem;opacity:.18;}
.sc.green{border-color:rgba(0,229,160,.3);} .sc.green .sc-value{color:var(--accent);}
.sc.blue{border-color:rgba(0,212,255,.3);} .sc.blue .sc-value{color:var(--blue);}
.sc.warn{border-color:rgba(255,179,0,.3);} .sc.warn .sc-value{color:var(--warn);}
.sc.red{border-color:rgba(255,76,76,.3);} .sc.red .sc-value{color:var(--danger);}
.grid2{display:grid;grid-template-columns:2fr 1fr;gap:1.2rem;}
.panel{background:var(--panel);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:1.2rem;}
.ph{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--border);}
.ph-title{font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);}
.pb{padding:1rem 1.2rem;}
.tbl-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:.83rem;}
thead th{font-size:.66rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:.55rem .8rem;text-align:left;border-bottom:1px solid var(--border);}
tbody tr{border-bottom:1px solid rgba(22,59,56,.6);transition:background .15s;}
tbody tr:hover{background:rgba(0,229,160,.04);}
tbody td{padding:.5rem .8rem;vertical-align:middle;}
.tag{display:inline-block;padding:2px 7px;border-radius:4px;font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;}
.tag-staff{background:rgba(0,212,255,.12);color:var(--blue);}
.tag-admin{background:rgba(255,179,0,.12);color:var(--warn);}
.tag-manager{background:rgba(0,229,160,.12);color:var(--accent);}
.tag-muted{background:rgba(61,122,114,.15);color:var(--muted);}
.audit-scroll{max-height:420px;overflow-y:auto;}
.audit-scroll::-webkit-scrollbar{width:4px;}
.audit-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.audit-item{padding:.6rem 0;border-bottom:1px solid rgba(22,59,56,.5);}
.audit-item:last-child{border-bottom:none;}
.audit-action{font-size:.82rem;font-weight:500;}
.audit-meta{font-size:.7rem;color:var(--muted);margin-top:.2rem;font-family:var(--mono);}
.btn{display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;font-weight:600;padding:.4rem .9rem;border-radius:6px;border:none;cursor:pointer;font-family:var(--sans);text-decoration:none;transition:background .2s,transform .1s;}
.btn:active{transform:scale(.97);}
.btn-primary{background:var(--accent);color:#06100f;}
.btn-primary:hover{background:#33ffb8;}
.btn-danger{background:rgba(255,76,76,.15);color:var(--danger);border:1px solid rgba(255,76,76,.3);}
.btn-danger:hover{background:rgba(255,76,76,.25);}
.btn-sm{font-size:.75rem;padding:.3rem .65rem;}
.alert-bar{border-radius:8px;padding:.7rem 1.1rem;font-size:.82rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;}
.alert-bar.success{background:rgba(0,229,160,.1);border:1px solid rgba(0,229,160,.3);color:var(--accent);}
.alert-bar.danger{background:rgba(255,76,76,.1);border:1px solid rgba(255,76,76,.3);color:var(--danger);}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:5000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--panel);border:1px solid var(--border);border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.5);animation:mIn .25s ease both;}
@keyframes mIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
.m-head{padding:1.1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.m-head h5{font-size:.95rem;font-weight:700;color:var(--accent);font-family:var(--mono);letter-spacing:.06em;}
.m-close{background:none;border:none;color:var(--muted);font-size:1.3rem;cursor:pointer;line-height:1;}
.m-close:hover{color:var(--text);}
.m-body{padding:1.4rem;}
.m-foot{padding:.9rem 1.4rem;border-top:1px solid var(--border);}
.field{margin-bottom:1rem;}
.lbl{display:block;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem;}
.inp{width:100%;background:rgba(0,229,160,.04);border:1px solid var(--border);border-radius:6px;padding:.6rem .85rem;color:var(--text);font-family:var(--sans);font-size:.9rem;outline:none;transition:border-color .25s;appearance:none;}
.inp::placeholder{color:var(--muted);}
.inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(0,229,160,.15);}
.inp option{background:var(--panel);color:var(--text);}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-logo">
    <h2>🚀 GAS-SIMHOT</h2>
    <p>Manager Control</p>
    <span class="sb-badge">Super User</span>
  </div>
  <nav class="sb-nav">
    <div class="nav-sec">Overview</div>
    <a class="nav-item active" href="#">📊 Dashboard</a>
    <div class="nav-sec" style="margin-top:.8rem">Personnel</div>
    <a class="nav-item" href="#personnel" onclick="document.getElementById('personnel').scrollIntoView({behavior:'smooth'});return false;">👥 Manage Users</a>
    <a class="nav-item" href="#" onclick="openModal()">➕ Add Personnel</a>
    <div class="nav-sec" style="margin-top:.8rem">Audit</div>
    <a class="nav-item" href="#audit" onclick="document.getElementById('audit').scrollIntoView({behavior:'smooth'});return false;">🔍 System Audit</a>
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
    <span class="topbar-title">MANAGER / DASHBOARD</span>
    <div style="display:flex;align-items:center;gap:1.2rem">
      <div class="live-badge"><span class="live-dot"></span>LIVE · <span id="clock">--:--:--</span></div>
      <button class="btn btn-primary btn-sm" onclick="openModal()">➕ Add Personnel</button>
    </div>
  </div>

  <div class="content">
    <?php if ($msg): ?><div class="alert-bar success">✓ <?= $msg ?></div><?php endif; ?>
    <?php if ($add_error): ?><div class="alert-bar danger">⚠ <?= htmlspecialchars($add_error) ?></div><?php endif; ?>

    <div class="stats-grid">
      <div class="sc green"><div class="sc-label">Total Personnel</div><div class="sc-value"><?= $total_users ?></div><span class="sc-icon">👥</span></div>
      <div class="sc blue"><div class="sc-label">Staff Members</div><div class="sc-value"><?= $total_staff ?></div><span class="sc-icon">👷</span></div>
      <div class="sc warn"><div class="sc-label">Administrators</div><div class="sc-value"><?= $total_admins ?></div><span class="sc-icon">🛡️</span></div>
      <div class="sc <?= $is_active ? 'red' : 'green' ?>" id="sys-card">
        <div class="sc-label">System Status</div>
        <div class="sc-value" id="sys-status"><?= $is_active ? 'ALERT' : 'SAFE' ?></div>
        <span class="sc-icon"><?= $is_active ? '🚨' : '✅' ?></span>
      </div>
    </div>

    <div class="grid2">
      <div class="panel" id="personnel">
        <div class="ph">
          <span class="ph-title">👥 Personnel Registry</span>
          <button class="btn btn-primary btn-sm" onclick="openModal()">➕ Add</button>
        </div>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>Name</th><th>Role</th><th>Station</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
              <tr>
                <td><div style="font-weight:600"><?= htmlspecialchars($u['full_name']) ?></div>
                    <div style="font-size:.72rem;color:var(--muted)">@<?= htmlspecialchars($u['username']) ?></div></td>
                <td><span class="tag tag-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($u['location']) ?></td>
                <td>
                  <?php if ($u['role'] !== 'manager'): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Remove <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">🗑 Remove</a>
                  <?php else: ?>
                    <span style="font-size:.72rem;color:var(--muted)">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel" id="audit">
        <div class="ph"><span class="ph-title">🔍 System Audit</span><span style="font-size:.72rem;color:var(--muted)">Last 30</span></div>
        <div class="pb">
          <div class="audit-scroll">
          <?php while ($a = $audit->fetch_assoc()):
            $isLeak = stripos($a['action'], 'Leak') !== false;
          ?>
            <div class="audit-item">
              <div class="audit-action" style="<?= $isLeak ? 'color:var(--danger)' : '' ?>">
                <?= $isLeak ? '🚨 ' : '' ?><?= htmlspecialchars($a['action']) ?>
              </div>
              <div class="audit-meta">
                <?= htmlspecialchars($a['full_name']) ?>
                <span class="tag tag-<?= $a['role'] ?>" style="margin-left:.3rem"><?= strtoupper($a['role']) ?></span>
                &nbsp;·&nbsp; <?= date('M d, H:i', strtotime($a['created_at'])) ?>
              </div>
            </div>
          <?php endwhile; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div class="m-head">
      <h5>➕ REGISTER PERSONNEL</h5>
      <button class="m-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST">
      <div class="m-body">
        <div class="row2">
          <div class="field"><label class="lbl">Full Name</label><input type="text" name="full_name" class="inp" placeholder="Juan Dela Cruz" required></div>
          <div class="field"><label class="lbl">Username</label><input type="text" name="username" class="inp" placeholder="username" required></div>
        </div>
        <div class="field"><label class="lbl">Password</label><input type="password" name="password" class="inp" placeholder="Min. 6 characters" minlength="6" required></div>
        <div class="row2">
          <div class="field">
            <label class="lbl">Role</label>
            <select name="role" class="inp" required>
              <option value="staff">👷 Staff</option>
              <option value="admin">🛡️ Administrator</option>
            </select>
          </div>
          <div class="field">
            <label class="lbl">Station</label>
            <select name="location" class="inp" required>
              <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc ?>"><?= $loc ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="m-foot">
        <button type="submit" name="add_user" class="btn btn-primary" style="width:100%">💾 Save Personnel</button>
      </div>
    </form>
  </div>
</div>

<script>
const CHECK_URL = '<?= $check_url ?>';
setInterval(() => {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-PH',{hour12:false});
}, 1000);
function openModal()  { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
setInterval(() => {
  fetch(CHECK_URL).then(r => r.json()).then(d => {
    const el = document.getElementById('sys-status');
    const card = document.getElementById('sys-card');
    if (d.is_active === 1) { el.textContent = 'ALERT'; card.className = 'sc red'; }
    else                   { el.textContent = 'SAFE';  card.className = 'sc green'; }
  }).catch(()=>{});
}, 3000);
</script>
</body>
</html>
