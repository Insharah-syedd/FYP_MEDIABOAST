<?php
error_reporting(E_ERROR|E_PARSE);
require_once 'includes/auth.php';
if(isLoggedIn()) { redirectByRole($_SESSION['user_role']); }
$error='';$msg=$_GET['msg']??'';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    $email=trim($_POST['email']??'');
    $password=$_POST['password']??'';
    if(!$email||!$password){ $error='Email and password are required.'; }
    else {
        $result=loginUser($email,$password);
        if($result['success']){ redirectByRole($result['role']); }
        else { $error=$result['message']; }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>MediaBoost — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--text:#f0f4ff;--muted:#6b7a99;--red:#ef4444;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
body{background:var(--bg);color:var(--text);font-family:var(--font-body);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:48px 48px;opacity:.4;pointer-events:none}
.orb{position:fixed;border-radius:50%;filter:blur(90px);pointer-events:none;animation:drift 12s ease-in-out infinite alternate}
.orb-1{width:500px;height:500px;background:rgba(59,130,246,.12);top:-150px;left:-100px}
.orb-2{width:400px;height:400px;background:rgba(99,102,241,.1);bottom:-100px;right:-80px;animation-delay:-6s}
@keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(30px,20px) scale(1.05)}}
.card{position:relative;z-index:10;background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:48px 44px;width:100%;max-width:440px;box-shadow:0 32px 80px rgba(0,0,0,.5);animation:slideUp .5s cubic-bezier(.16,1,.3,1) both}
@keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.logo{display:flex;align-items:center;gap:10px;margin-bottom:36px}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;font-family:var(--font-head);color:#fff;box-shadow:0 0 20px rgba(59,130,246,.35)}
.logo-text{font-family:var(--font-head);font-size:22px;font-weight:700;background:linear-gradient(135deg,#fff 30%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
h1{font-family:var(--font-head);font-size:28px;font-weight:800;margin-bottom:6px}
.subtitle{color:var(--muted);font-size:14px;margin-bottom:32px}
.field{margin-bottom:20px}
label{display:block;font-size:12px;font-weight:500;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
input{width:100%;background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:14px 16px;color:var(--text);font-family:var(--font-body);font-size:15px;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
input::placeholder{color:var(--muted)}
.alert-error{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
.alert-info{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#fcd34d}
.btn{width:100%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:12px;padding:15px;font-family:var(--font-head);font-size:16px;font-weight:600;cursor:pointer;transition:opacity .2s,transform .15s,box-shadow .2s;box-shadow:0 4px 20px rgba(59,130,246,.3);margin-top:8px}
.btn:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 8px 28px rgba(59,130,246,.4)}
.roles{display:flex;gap:8px;margin-top:28px;padding-top:24px;border-top:1px solid var(--border)}
.role-pill{flex:1;background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:10px 8px;text-align:center;font-size:11px;font-weight:500;color:var(--muted);cursor:pointer;transition:all .2s;user-select:none}
.role-pill:hover{border-color:var(--accent);color:var(--accent);background:rgba(59,130,246,.06)}
.role-pill strong{display:block;font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px}
.demo-note{margin-top:16px;text-align:center;font-size:12px;color:var(--muted)}
.demo-note code{background:var(--surface2);border:1px solid var(--border);border-radius:6px;padding:2px 6px;font-size:11px;color:#a5b4fc}
</style></head><body>
<div class="orb orb-1"></div><div class="orb orb-2"></div>
<div class="card">
  <div class="logo"><div class="logo-icon">M</div><span class="logo-text">MediaBoost</span></div>
  <h1>Welcome Back 👋</h1>
  <p class="subtitle">All Media Marketing — Management System</p>
  <?php if($error): ?><div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if($msg==='session_expired'): ?><div class="alert-info">⏱ Session expired. Please login again.</div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="field"><label>Email Address</label><input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required autocomplete="email"></div>
    <div class="field"><label>Password</label><input type="password" name="password" placeholder="••••••••" required autocomplete="current-password"></div>
    <button type="submit" class="btn">Sign In →</button>
  </form>
  <div class="roles">
    <div class="role-pill" onclick="fill('admin@allmediamarketing.com','Admin@123')"><strong>🛡 Admin</strong>Full access</div>
    <div class="role-pill" onclick="fill('sara@allmediamarketing.com','Admin@123')"><strong>📊 Manager</strong>Leads + reports</div>
    <div class="role-pill" onclick="fill('bilal@techstart.pk','Client@123')"><strong>👤 Client</strong>Project view</div>
  </div>
  <p class="demo-note">Demo password: <code>Admin@123</code> — Click role to auto-fill</p>
</div>
<script>function fill(e,p){document.querySelector('[name=email]').value=e;document.querySelector('[name=password]').value=p;}</script>
</body></html>
