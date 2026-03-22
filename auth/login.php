<?php
require_once __DIR__ . '/../config/db_connect.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_for($_SESSION['role']));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_sel = strtolower(trim($_POST['role'] ?? ''));

    if (!$username || !$password || !$role_sel) {
        $error = 'All fields are required.';
    } else {
        $db   = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
        $stmt->bind_param('ss', $username, $role_sel);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $hash    = $user['password'];
            $pass_ok = false;

            // Case 1: Real bcrypt hash — normal verify
            if (str_starts_with($hash, '$2y$') && !str_contains($hash, 'YourHash')) {
                $pass_ok = password_verify($password, $hash);
            }

            // Case 2: Fake placeholder hash from migration.sql
            // Any password works, then we upgrade to real bcrypt
            if (!$pass_ok && str_contains($hash, 'YourHash')) {
                // Accept any password and upgrade hash
                $pass_ok  = true;
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $uid_fix  = (int)$user['id'];
                $esc_hash = $db->real_escape_string($new_hash);
                $db->query("UPDATE users SET password='$esc_hash' WHERE id=$uid_fix");
            }

            // Case 3: Plain text password stored in DB
            if (!$pass_ok && $hash === $password) {
                $pass_ok  = true;
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $uid_fix  = (int)$user['id'];
                $esc_hash = $db->real_escape_string($new_hash);
                $db->query("UPDATE users SET password='$esc_hash' WHERE id=$uid_fix");
            }

            if ($pass_ok) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['location']  = $user['location'];

                $uid = (int)$user['id'];
                $db->query("INSERT INTO user_activity_logs (user_id, action) VALUES ($uid, 'Logged In')");

                header('Location: ' . dashboard_for($user['role']));
                exit();
            }
        }

        $error = 'Invalid username, password, or role. Please try again.';
    }
}

$msg = htmlspecialchars($_GET['msg'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GAS-SIMHOT — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#050d1a;--panel:#0b1629;--border:#1a3a5c;--accent:#00d4ff;--danger:#ff4c4c;--success:#00e5a0;--text:#cfe8ff;--muted:#4a7a9b;--mono:'Share Tech Mono',monospace;--sans:'Exo 2',sans-serif;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:var(--sans);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,.04) 1px,transparent 1px);background-size:40px 40px;animation:gs 20s linear infinite;pointer-events:none;}
@keyframes gs{to{transform:translateY(40px)}}
.orb{position:fixed;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(0,212,255,.12) 0%,transparent 70%);top:-150px;left:-150px;animation:op 6s ease-in-out infinite alternate;pointer-events:none;}
@keyframes op{from{transform:scale(1);opacity:.6}to{transform:scale(1.2);opacity:1}}
.scan{position:fixed;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--accent),transparent);animation:sc 4s linear infinite;pointer-events:none;}
@keyframes sc{from{top:0;opacity:.8}to{top:100vh;opacity:0}}
.wrap{position:relative;z-index:10;width:100%;max-width:420px;padding:0 1rem;}
.card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:2.5rem 2rem;box-shadow:0 0 60px rgba(0,212,255,.08),0 24px 48px rgba(0,0,0,.5);animation:ci .5s ease both;}
@keyframes ci{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.logo{width:72px;height:72px;border-radius:50%;border:2px solid var(--accent);display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:2rem;animation:gl 3s ease-in-out infinite alternate;}
@keyframes gl{from{box-shadow:0 0 12px rgba(0,212,255,.2)}to{box-shadow:0 0 32px rgba(0,212,255,.6)}}
h1{font-family:var(--mono);font-size:1.5rem;letter-spacing:.1em;text-align:center;color:var(--accent);}
.sub{font-size:.72rem;text-align:center;color:var(--muted);margin-top:.3rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:1.8rem;}
.field{margin-bottom:1.1rem;}
label{display:block;font-size:.7rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem;}
input,select{width:100%;background:rgba(0,212,255,.04);border:1px solid var(--border);border-radius:6px;padding:.65rem .9rem;color:var(--text);font-family:var(--sans);font-size:.93rem;outline:none;appearance:none;transition:border-color .25s,box-shadow .25s;}
input::placeholder{color:var(--muted);}
input:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(0,212,255,.15);}
select option{background:var(--panel);color:var(--text);}
.btn{width:100%;background:var(--accent);color:#050d1a;border:none;border-radius:6px;padding:.8rem;font-family:var(--mono);font-size:1rem;font-weight:700;letter-spacing:.08em;cursor:pointer;margin-top:.5rem;transition:background .2s,box-shadow .2s,transform .1s;}
.btn:hover{background:#33deff;box-shadow:0 0 20px rgba(0,212,255,.5);}
.btn:active{transform:scale(.98);}
.alert{border-radius:6px;padding:.65rem .9rem;font-size:.83rem;margin-bottom:1.2rem;}
.alert-danger{background:rgba(255,76,76,.12);border:1px solid rgba(255,76,76,.4);color:#ff8080;}
.alert-success{background:rgba(0,229,160,.10);border:1px solid rgba(0,229,160,.4);color:var(--success);}
.footer{text-align:center;font-size:.8rem;color:var(--muted);margin-top:1.4rem;}
.footer a{color:var(--accent);text-decoration:none;font-weight:600;}
.footer a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="orb"></div>
<div class="scan"></div>
<div class="wrap">
  <div class="card">
    <div class="logo">🛡️</div>
    <h1>GAS-SIMHOT</h1>
    <p class="sub">Gas Safety Monitoring System</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
      <div class="alert alert-success">✓ <?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Enter username" autocomplete="username" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password"
               placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <div class="field">
        <label>Login As</label>
        <select name="role" required>
          <option value="staff"   <?= ($_POST['role'] ?? '') === 'staff'   ? 'selected' : '' ?>>👷 Staff / User</option>
          <option value="admin"   <?= ($_POST['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>🛡️ Administrator</option>
          <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>🚀 Manager</option>
        </select>
      </div>
      <button type="submit" class="btn">[ ACCESS SYSTEM ]</button>
    </form>

    <p class="footer">
      New personnel? <a href="<?= register_url() ?>">Create Account</a>
    </p>
  </div>
</div>
</body>
</html>