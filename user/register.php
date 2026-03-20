<?php
/**
 * Registration page.
 * Location: user/register.php
 */

require_once __DIR__ . '/../config/db_connect.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_for($_SESSION['role']));
    exit();
}

$error     = '';
$locations = ['Kitchen', 'Laboratory', 'Warehouse', 'Main Office'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn  = trim($_POST['full_name'] ?? '');
    $un  = trim($_POST['username']  ?? '');
    $pw  = trim($_POST['password']  ?? '');
    $ro  = in_array($_POST['role'] ?? '', ['staff','admin','manager']) ? $_POST['role'] : 'staff';
    $lo  = in_array($_POST['location'] ?? '', $locations) ? $_POST['location'] : 'General Area';

    if (!$fn || !$un || !$pw) {
        $error = 'All fields are required.';
    } elseif (strlen($pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db   = db();
        $chk  = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $chk->bind_param('s', $un);
        $chk->execute();
        $chk->store_result();
        $exists = $chk->num_rows > 0;
        $chk->close();

        if ($exists) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $ins  = $db->prepare(
                "INSERT INTO users (full_name, username, password, role, location) VALUES (?,?,?,?,?)"
            );
            $ins->bind_param('sssss', $fn, $un, $hash, $ro, $lo);
            $ins->execute();
            $ins->close();
            header('Location: ' . login_url() . '?msg=Account+created!+Please+login.');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — GAS-SIMHOT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#050d1a;--panel:#0b1629;--border:#1a3a5c;--accent:#00d4ff;--a2:#ff4c4c;--a3:#00e5a0;--text:#cfe8ff;--muted:#4a7a9b;--mono:'Share Tech Mono',monospace;--sans:'Exo 2',sans-serif;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:var(--sans);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,.04) 1px,transparent 1px);background-size:40px 40px;animation:gs 20s linear infinite;pointer-events:none;}
@keyframes gs{to{transform:translateY(40px)}}
.orb{position:fixed;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(0,229,160,.1) 0%,transparent 70%);bottom:-150px;right:-150px;pointer-events:none;}
.wrap{position:relative;z-index:10;width:100%;max-width:500px;padding:0 1rem;}
.card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:2.2rem;box-shadow:0 0 60px rgba(0,229,160,.06),0 24px 48px rgba(0,0,0,.5);animation:ci .45s ease both;}
@keyframes ci{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.logo{width:60px;height:60px;border-radius:50%;border:2px solid var(--a3);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.6rem;box-shadow:0 0 20px rgba(0,229,160,.3);}
h1{font-family:var(--mono);font-size:1.3rem;letter-spacing:.1em;text-align:center;color:var(--a3);}
.sub{font-size:.72rem;text-align:center;color:var(--muted);margin-top:.3rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:1.6rem;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
.field{margin-bottom:.9rem;}
label{display:block;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;}
input,select{width:100%;background:rgba(0,229,160,.04);border:1px solid var(--border);border-radius:6px;padding:.6rem .85rem;color:var(--text);font-family:var(--sans);font-size:.9rem;outline:none;appearance:none;transition:border-color .25s;}
input::placeholder{color:var(--muted);}
input:focus,select:focus{border-color:var(--a3);box-shadow:0 0 0 3px rgba(0,229,160,.15);}
select option{background:var(--panel);color:var(--text);}
.btn{width:100%;background:var(--a3);color:#050d1a;border:none;border-radius:6px;padding:.8rem;font-family:var(--mono);font-size:.95rem;font-weight:700;letter-spacing:.08em;cursor:pointer;margin-top:.4rem;transition:background .2s,box-shadow .2s;}
.btn:hover{background:#33ffb8;box-shadow:0 0 20px rgba(0,229,160,.5);}
.alert{border-radius:6px;padding:.65rem .9rem;font-size:.82rem;margin-bottom:1.1rem;background:rgba(255,76,76,.12);border:1px solid rgba(255,76,76,.4);color:#ff8080;}
.footer{text-align:center;font-size:.8rem;color:var(--muted);margin-top:1.2rem;}
.footer a{color:var(--accent);text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="orb"></div>
<div class="wrap">
  <div class="card">
    <div class="logo">⚗️</div>
    <h1>CREATE ACCOUNT</h1>
    <p class="sub">GAS-SIMHOT Personnel Registration</p>

    <?php if ($error): ?>
      <div class="alert">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="row2">
        <div class="field">
          <label>Full Name</label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="Juan Dela Cruz" required>
        </div>
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="username123" required>
        </div>
      </div>
      <div class="field">
        <label>Password <span style="color:var(--muted);font-size:.65rem">(min. 6 chars)</span></label>
        <input type="password" name="password" placeholder="••••••••" minlength="6" required>
      </div>
      <div class="row2">
        <div class="field">
          <label>Role</label>
          <select name="role" required>
            <option value="staff"   <?= ($_POST['role'] ?? '') === 'staff'   ? 'selected':'' ?>>👷 Staff</option>
            <option value="admin"   <?= ($_POST['role'] ?? '') === 'admin'   ? 'selected':'' ?>>🛡️ Admin</option>
            <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected':'' ?>>🚀 Manager</option>
          </select>
        </div>
        <div class="field">
          <label>Station</label>
          <select name="location" required>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= $loc ?>" <?= ($_POST['location'] ?? '') === $loc ? 'selected':'' ?>><?= $loc ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn">[ REGISTER ACCOUNT ]</button>
    </form>

    <p class="footer">
      Already have an account? <a href="<?= login_url() ?>">Login here</a>
    </p>
  </div>
</div>
</body>
</html>
