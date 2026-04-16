<?php
/**
 * debug.php - Verify base_url and file paths.
 * DELETE AFTER USE!
 */
echo '<style>body{background:#050d1a;color:#cfe8ff;font-family:monospace;padding:2rem;}
.ok{color:#00e5a0;}.err{color:#ff4c4c;}
h2{color:#00d4ff;margin:1.5rem 0 .5rem;}pre{background:#0b1629;padding:1rem;border-radius:6px;}</style>';

echo '<h1>GAS-SIMHOT Debug</h1>';
echo '<p style="color:#ff4c4c;font-weight:bold">DELETE THIS FILE AFTER CHECKING!</p>';

// Compute base_url same logic as db_connect.php
$proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '/';
$parts  = explode('/', trim($script, '/'));
array_pop($parts);
if (count($parts) > 1) array_pop($parts);
$path   = '/' . implode('/', $parts) . '/';
$base   = $proto . '://' . $host . $path;

echo '<h2>Server Paths</h2><pre>';
echo 'DOCUMENT_ROOT : ' . ($_SERVER['DOCUMENT_ROOT']  ?? 'N/A') . "\n";
echo 'SCRIPT_NAME   : ' . ($_SERVER['SCRIPT_NAME']    ?? 'N/A') . "\n";
echo '__DIR__        : ' . __DIR__ . "\n";
echo '</pre>';

echo '<h2>Computed URLs</h2><pre>';
echo 'base_url()    : <span class="ok">' . $base . "</span>\n";
echo 'login_url()   : <span class="ok">' . $base . "auth/login.php</span>\n";
echo '</pre>';

echo '<h2>File Check</h2><pre>';
$root  = __DIR__ . '/';  // debug.php is in gasleak/ root
$files = [
    'index.php','auth/login.php','auth/logout.php','auth/check_alert.php',
    'config/db_connect.php','core/auth_guard.php','core/log_action.php',
    'admin/admin_dashboard.php','manager/manager_dashboard.php',
    'user/user_dashboard.php','user/register.php','.htaccess',
];
foreach ($files as $f) {
    $ok = file_exists($root . $f);
    echo ($ok ? '<span class="ok">OK  </span>' : '<span class="err">MISSING</span>') . ' ' . $f . "\n";
}
echo '</pre>';

echo '<h2>DB Test</h2><pre>';
$c = @new mysqli('localhost','u442411629_dev_gasleak','Hs29/:E2£+YC','u442411629_gasleak');
if ($c->connect_error) {
    echo '<span class="err">FAILED: ' . htmlspecialchars($c->connect_error) . "</span>\n";
} else {
    echo '<span class="ok">Connected!</span>' . "\n";
    foreach (['users','user_activity_logs','system_status'] as $t) {
        $r = $c->query("SHOW TABLES LIKE '$t'");
        echo ($r && $r->num_rows > 0 ? '<span class="ok">OK  </span>' : '<span class="err">MISSING</span>') . ' ' . $t . "\n";
    }
    $c->close();
}
echo '</pre>';
echo '<p style="color:#ff4c4c">DELETE debug.php now!</p>';