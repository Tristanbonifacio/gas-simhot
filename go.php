<?php
// go.php - Upload this ONE file to gasleak/ folder then visit it
// https://ics-dev.io/gasleak/go.php
// DELETE after use!

$conn = new mysqli('localhost','u442411629_dev_gasleak','Hs29/:E2£+YC','u442411629_gasleak');
if($conn->connect_error) die('DB Error: '.$conn->connect_error);
$conn->set_charset('utf8mb4');

// Wipe and re-insert all users with REAL bcrypt hashes
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("DELETE FROM user_activity_logs");
$conn->query("DELETE FROM users");
$conn->query("SET FOREIGN_KEY_CHECKS=1");
$conn->query("ALTER TABLE users AUTO_INCREMENT=1");
$conn->query("INSERT IGNORE INTO system_status (id) VALUES (1)");
$conn->query("UPDATE system_status SET is_active=0,ppm=0,acknowledged_by_admin=0,ack_time=NULL WHERE id=1");

$list = [
    ['manager','password123','System Manager','manager','Main Office'],
    ['admin',  'password123','Site Admin',    'admin',  'Main Office'],
    ['staff01','password123','Juan Dela Cruz','staff',  'Kitchen'],
    ['staff02','password123','Maria Santos',  'staff',  'Laboratory'],
    ['staff03','password123','Pedro Reyes',   'staff',  'Warehouse'],
];

$s = $conn->prepare("INSERT INTO users (username,password,full_name,role,location) VALUES(?,?,?,?,?)");
$done = [];
foreach($list as [$u,$p,$f,$r,$l]){
    $h = password_hash($p, PASSWORD_BCRYPT);
    $s->bind_param('sssss',$u,$h,$f,$r,$l);
    $s->execute();
    $done[] = [$u,$r,password_verify($p,$h)];
}
$s->close();
$conn->close();

$proto = (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
$host  = $_SERVER['HTTP_HOST'];
$base  = $proto.'://'.$host.'/gasleak/';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>GO</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#050d1a;color:#cfe8ff;font-family:Arial;min-height:100vh;
     display:flex;align-items:center;justify-content:center;padding:2rem}
.box{background:#0b1629;border:1px solid #1a3a5c;border-radius:12px;padding:2rem;max-width:420px;width:100%}
h2{color:#00e5a0;font-size:1.2rem;margin-bottom:1rem}
.row{display:flex;justify-content:space-between;padding:.45rem 0;
     border-bottom:1px solid rgba(26,58,92,.4);font-size:.88rem}
.row:last-of-type{border:none}
.ok{color:#00e5a0;font-weight:700}
.fail{color:#ff4c4c;font-weight:700}
.creds{background:#0d1e30;border-radius:8px;padding:1rem;margin:1.2rem 0;font-size:.85rem;line-height:2}
.btn{display:block;background:#00d4ff;color:#050d1a;border:none;border-radius:8px;
     padding:.85rem;font-size:1rem;font-weight:700;text-decoration:none;text-align:center}
.btn:hover{background:#33deff}
.warn{color:#ff8080;font-size:.78rem;margin-top:.8rem;line-height:1.6}
code{background:#0d1e30;padding:1px 5px;border-radius:3px;color:#00d4ff}
</style></head><body>
<div class="box">
  <h2>✅ Done! All passwords fixed.</h2>
  <?php foreach($done as [$u,$r,$ok]): ?>
  <div class="row">
    <span><strong><?=$u?></strong> (<?=$r?>)</span>
    <span class="<?=$ok?'ok':'fail'?>"><?=$ok?'✅ OK':'❌ FAIL'?></span>
  </div>
  <?php endforeach ?>
  <div class="creds">
    🔑 <strong>Login credentials:</strong><br>
    manager &nbsp;/ password123 → Manager<br>
    admin &nbsp;&nbsp;&nbsp;/ password123 → Admin<br>
    staff01 / password123 → Staff
  </div>
  <a href="<?=htmlspecialchars($base)?>auth/login.php" class="btn">🔐 Login Now</a>
  <p class="warn">⚠️ Delete <code>go.php</code> from your hosting after logging in!</p>
</div>
</body></html>