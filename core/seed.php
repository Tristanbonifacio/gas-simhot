<?php
/**
 * seed.php — Run ONCE to insert demo accounts with hashed passwords.
 * Visit: https://ics-dev.io/gasleak/core/seed.php
 * DELETE this file after running on production.
 *
 * Location: core/seed.php
 */

require_once __DIR__ . '/../config/db_connect.php';

$db = db();

$accounts = [
    ['manager', 'password123', 'System Manager', 'manager', 'Main Office'],
    ['admin',   'password123', 'Site Admin',     'admin',   'Main Office'],
    ['staff01', 'password123', 'Juan Dela Cruz', 'staff',   'Kitchen'],
    ['staff02', 'password123', 'Maria Santos',   'staff',   'Laboratory'],
    ['staff03', 'password123', 'Pedro Reyes',    'staff',   'Warehouse'],
];

$stmt = $db->prepare(
    "INSERT IGNORE INTO users (username, password, full_name, role, location)
     VALUES (?, ?, ?, ?, ?)"
);

$results = [];
foreach ($accounts as [$un, $pw, $fn, $role, $loc]) {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt->bind_param('sssss', $un, $hash, $fn, $role, $loc);
    $stmt->execute();
    $results[] = $stmt->affected_rows > 0
        ? "✅ Seeded: <b>$un</b> ($role)"
        : "⚠️ Skipped (already exists): <b>$un</b>";
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Seed — GAS-SIMHOT</title>
    <style>
        body { background:#050d1a; color:#cfe8ff; font-family:Arial; display:flex;
               align-items:center; justify-content:center; height:100vh; margin:0; }
        .box { background:#0b1629; border:1px solid #163350; border-radius:10px;
               padding:2rem 2.5rem; max-width:480px; width:100%; }
        h2  { color:#00d4ff; margin-bottom:1rem; }
        li  { margin:.4rem 0; line-height:1.6; }
        .creds { background:#0d1e30; border-radius:6px; padding:1rem; margin-top:1rem; font-size:.9rem; }
        .creds b { color:#00e5a0; }
        a   { color:#00d4ff; }
    </style>
</head>
<body>
<div class="box">
    <h2>🌱 Database Seeded</h2>
    <ul>
        <?php foreach ($results as $r): ?>
            <li><?= $r ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="creds">
        <b>Login credentials (password: password123)</b><br><br>
        manager / password123 → Manager<br>
        admin &nbsp;&nbsp;/ password123 → Admin<br>
        staff01 / password123 → Staff<br>
        staff02 / password123 → Staff<br>
        staff03 / password123 → Staff
    </div>
    <p style="margin-top:1rem">
        <a href="<?= login_url() ?>">→ Go to Login</a>
    </p>
    <p style="color:#ff4c4c;font-size:.8rem;margin-top:.5rem">
        ⚠️ Delete this file after seeding on production.
    </p>
</div>
</body>
</html>