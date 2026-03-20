<?php
/**
 * GAS-SIMHOT — setup.php
 * Run this ONCE to complete the setup.
 * Visit: https://ics-dev.io/gasleak/setup.php
 * DELETE after running!
 */

// ── DB credentials ─────────────────────────────────────────────
$db_host = 'localhost';
$db_user = 'u442411629_dev_gasleak';
$db_pass = 'Hs29/:E2£+YC';
$db_name = 'u442411629_gasleak';

$results = [];
$errors  = [];

// ── 1. Create all .htaccess files ──────────────────────────────
$root = __DIR__ . '/';

$htaccess_files = [

    // Root .htaccess — main entry, no directory listing
    $root . '.htaccess' => '# GAS-SIMHOT Root
Options -Indexes
DirectoryIndex index.php

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>',

    // auth/ — allow login.php etc to be accessed
    $root . 'auth/.htaccess' => 'Options -Indexes
Allow from all',

    // admin/ — allow dashboard access
    $root . 'admin/.htaccess' => 'Options -Indexes
Allow from all',

    // manager/ — allow dashboard access
    $root . 'manager/.htaccess' => 'Options -Indexes
Allow from all',

    // user/ — allow dashboard access
    $root . 'user/.htaccess' => 'Options -Indexes
Allow from all',

    // config/ — BLOCK direct browser access (security)
    $root . 'config/.htaccess' => 'Options -Indexes
Order Allow,Deny
Deny from all',

    // core/ — block direct access except log_action.php & seed.php
    $root . 'core/.htaccess' => 'Options -Indexes
<Files "*.php">
    Order Allow,Deny
    Deny from all
</Files>
<Files "log_action.php">
    Order Allow,Deny
    Allow from all
</Files>
<Files "seed.php">
    Order Allow,Deny
    Allow from all
</Files>',

    // db/ — BLOCK direct access to SQL files
    $root . 'db/.htaccess' => 'Options -Indexes
Order Allow,Deny
Deny from all',
];

foreach ($htaccess_files as $path => $content) {
    $folder = dirname($path);
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    if (file_put_contents($path, $content) !== false) {
        $results[] = '✅ Created: ' . str_replace($root, '', $path);
    } else {
        $errors[] = '❌ Failed to create: ' . str_replace($root, '', $path) . ' (check folder permissions)';
    }
}

// ── 2. Seed the database ────────────────────────────────────────
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    $errors[] = '❌ DB Connection failed: ' . $conn->connect_error;
} else {
    $conn->set_charset('utf8mb4');

    // Ensure system_status row exists
    $conn->query("INSERT IGNORE INTO system_status (id) VALUES (1)");
    $results[] = '✅ system_status row ensured';

    // Seed demo accounts
    $accounts = [
        ['manager', 'password123', 'System Manager', 'manager', 'Main Office'],
        ['admin',   'password123', 'Site Admin',     'admin',   'Main Office'],
        ['staff01', 'password123', 'Juan Dela Cruz', 'staff',   'Kitchen'],
        ['staff02', 'password123', 'Maria Santos',   'staff',   'Laboratory'],
        ['staff03', 'password123', 'Pedro Reyes',    'staff',   'Warehouse'],
    ];

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO users (username, password, full_name, role, location) VALUES (?,?,?,?,?)"
    );

    foreach ($accounts as [$un, $pw, $fn, $role, $loc]) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $stmt->bind_param('sssss', $un, $hash, $fn, $role, $loc);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $results[] = '✅ Seeded account: ' . $un . ' (' . $role . ')';
        } else {
            $results[] = '⚠️ Already exists: ' . $un . ' (skipped)';
        }
    }
    $stmt->close();
    $conn->close();
}

// ── 3. Compute base URL ─────────────────────────────────────────
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script  = $_SERVER['SCRIPT_NAME'] ?? '/';
$parts   = explode('/', trim($script, '/'));
array_pop($parts);
if (count($parts) > 1) array_pop($parts);
$base_url = $proto . '://' . $host . '/' . implode('/', $parts) . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GAS-SIMHOT Setup</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#050d1a;color:#cfe8ff;font-family:'Segoe UI',Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
.wrap{width:100%;max-width:620px;}
.card{background:#0b1629;border:1px solid #1a3a5c;border-radius:12px;padding:2rem;margin-bottom:1.2rem;}
h1{font-size:1.4rem;color:#00d4ff;margin-bottom:.3rem;letter-spacing:.05em;}
.sub{font-size:.8rem;color:#4a7a9b;margin-bottom:1.5rem;}
.item{padding:.5rem 0;border-bottom:1px solid rgba(26,58,92,.5);font-size:.88rem;line-height:1.5;}
.item:last-child{border-bottom:none;}
.ok{color:#00e5a0;}
.warn{color:#ffb300;}
.err{color:#ff4c4c;}
.creds{background:#0d1e30;border-radius:8px;padding:1.2rem;margin-top:1rem;}
.creds h3{color:#00d4ff;font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.8rem;}
.cred-row{display:flex;justify-content:space-between;padding:.35rem 0;border-bottom:1px solid rgba(26,58,92,.4);font-size:.85rem;}
.cred-row:last-child{border-bottom:none;}
.cred-role{color:#4a7a9b;font-size:.75rem;background:rgba(0,212,255,.08);padding:2px 8px;border-radius:4px;}
.btn{display:inline-block;background:#00d4ff;color:#050d1a;border:none;border-radius:8px;padding:.8rem 1.8rem;font-size:1rem;font-weight:700;text-decoration:none;letter-spacing:.05em;transition:background .2s,box-shadow .2s;cursor:pointer;margin-top:1rem;width:100%;text-align:center;}
.btn:hover{background:#33deff;box-shadow:0 0 20px rgba(0,212,255,.4);}
.warn-box{background:rgba(255,76,76,.1);border:1px solid rgba(255,76,76,.3);border-radius:8px;padding:1rem;font-size:.82rem;color:#ff8080;margin-top:1rem;line-height:1.6;}
.section-title{font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#4a7a9b;margin-bottom:.8rem;}
</style>
</head>
<body>
<div class="wrap">

  <div class="card">
    <h1>🛡️ GAS-SIMHOT Setup Complete</h1>
    <p class="sub">All systems configured and ready to run.</p>

    <div class="section-title">Setup Results</div>
    <?php foreach ($results as $r): ?>
      <div class="item <?= strpos($r,'⚠️') !== false ? 'warn' : 'ok' ?>"><?= htmlspecialchars($r) ?></div>
    <?php endforeach; ?>

    <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $e): ?>
        <div class="item err"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="creds">
      <h3>🔑 Login Credentials</h3>
      <div class="cred-row">
        <span><strong>manager</strong> / password123</span>
        <span class="cred-role">Manager</span>
      </div>
      <div class="cred-row">
        <span><strong>admin</strong> / password123</span>
        <span class="cred-role">Admin</span>
      </div>
      <div class="cred-row">
        <span><strong>staff01</strong> / password123</span>
        <span class="cred-role">Staff</span>
      </div>
      <div class="cred-row">
        <span><strong>staff02</strong> / password123</span>
        <span class="cred-role">Staff</span>
      </div>
      <div class="cred-row">
        <span><strong>staff03</strong> / password123</span>
        <span class="cred-role">Staff</span>
      </div>
    </div>

    <a href="<?= htmlspecialchars($base_url) ?>" class="btn">🚀 Go to GAS-SIMHOT</a>

    <div class="warn-box">
      ⚠️ <strong>IMPORTANT:</strong> Delete <code>setup.php</code> and <code>debug.php</code>
      from your hosting after this. They expose server information.
    </div>
  </div>

</div>
</body>
</html>