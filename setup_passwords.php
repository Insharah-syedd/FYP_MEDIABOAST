<?php
// ============================================================
// MediaBoost — Password Setup Script
// Run this ONCE to hash all passwords properly
// Then DELETE this file immediately!
// ============================================================
require_once 'includes/config.php';

$db = getDB();

// New secure passwords
$passwords = [
    'staff' => [
        'admin@allmediamarketing.com' => 'Admin@123',
        'sara@allmediamarketing.com'  => 'Admin@123',
        'ali@allmediamarketing.com'   => 'Admin@123',
    ],
    'clients' => [
        'bilal@techstart.pk' => 'Client@123',
    ]
];

$results = [];

// Hash and update staff passwords
foreach ($passwords['staff'] as $email => $pass) {
    $hashed = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("UPDATE users SET password=? WHERE email=?");
    $stmt->execute([$hashed, $email]);
    $results[] = "✅ Staff updated: $email";
}

// Hash and update client passwords
foreach ($passwords['clients'] as $email => $pass) {
    $hashed = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("UPDATE clients SET password=? WHERE email=?");
    $stmt->execute([$hashed, $email]);
    $results[] = "✅ Client updated: $email";
}

?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Password Setup — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0b0f1a;color:#f0f4ff;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#111827;border:1px solid #1e2d45;border-radius:20px;padding:40px;max-width:560px;width:100%}
.title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-bottom:8px;color:#f0f4ff}
.subtitle{color:#6b7a99;font-size:14px;margin-bottom:28px}
.result-item{background:#1a2236;border-radius:10px;padding:12px 16px;margin-bottom:10px;font-size:14px;border-left:3px solid #10b981;color:#6ee7b7}
.warning{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:12px;padding:16px;margin-top:24px;color:#fca5a5;font-size:13px;line-height:1.6}
.warning strong{display:block;margin-bottom:6px;font-size:15px}
.credentials{background:#1a2236;border-radius:12px;padding:20px;margin-top:20px}
.credentials h3{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:14px;color:#f0f4ff}
.cred-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #1e2d45;font-size:13px}
.cred-row:last-child{border-bottom:none}
.cred-email{color:#6b7a99}
.cred-pass{background:rgba(59,130,246,.1);color:#93c5fd;padding:3px 10px;border-radius:6px;font-family:monospace}
.btn{display:inline-block;margin-top:24px;background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-family:'Syne',sans-serif;font-weight:600;font-size:14px}
</style>
</head><body>
<div class="card">
  <div class="title">🔒 Password Security Setup</div>
  <div class="subtitle">All passwords have been encrypted with bcrypt hashing</div>

  <?php foreach($results as $r): ?>
  <div class="result-item"><?= $r ?></div>
  <?php endforeach; ?>

  <div class="credentials">
    <h3>Login Credentials (unchanged)</h3>
    <div class="cred-row"><span class="cred-email">admin@allmediamarketing.com</span><span class="cred-pass">Admin@123</span></div>
    <div class="cred-row"><span class="cred-email">sara@allmediamarketing.com</span><span class="cred-pass">Admin@123</span></div>
    <div class="cred-row"><span class="cred-email">ali@allmediamarketing.com</span><span class="cred-pass">Admin@123</span></div>
    <div class="cred-row"><span class="cred-email">bilal@techstart.pk</span><span class="cred-pass">Client@123</span></div>
  </div>

  <div class="warning">
    <strong>⚠️ IMPORTANT — Delete This File!</strong>
    After running this script, immediately delete this file from your server:<br><br>
    <code style="background:rgba(0,0,0,.3);padding:4px 8px;border-radius:4px;font-size:12px">C:\xampp\htdocs\mediaboost\setup_passwords.php</code>
  </div>

  <a href="index.php" class="btn">Go to Login →</a>
</div>
</body></html>
