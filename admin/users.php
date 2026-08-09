<?php
require_once '../includes/auth.php';
error_reporting(E_ERROR|E_PARSE);
requireRole(['admin']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$action=$_POST['action']??'';
  if($action==='add'){$db->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,?,?)")->execute([$_POST['name'],$_POST['email'],$_POST['password'],$_POST['role'],$_POST['phone']]);$msg='User added successfully! ✅';}
  if($action==='edit'){$pass=$_POST['password']??'';if($pass){$db->prepare("UPDATE users SET name=?,email=?,password=?,role=?,phone=? WHERE id=?")->execute([$_POST['name'],$_POST['email'],$pass,$_POST['role'],$_POST['phone'],$_POST['user_id']]);}else{$db->prepare("UPDATE users SET name=?,email=?,role=?,phone=? WHERE id=?")->execute([$_POST['name'],$_POST['email'],$_POST['role'],$_POST['phone'],$_POST['user_id']]);}$msg='User updated successfully! ✅';}
  if($action==='toggle'){$db->prepare("UPDATE users SET is_active=NOT is_active WHERE id=?")->execute([$_POST['user_id']]);$msg='Status changed!';}
}
$users=$db->query("SELECT u.*,(SELECT COUNT(*) FROM leads WHERE assigned_to=u.id) AS lead_count,(SELECT COUNT(*) FROM projects WHERE assigned_to=u.id) AS project_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
$editUser=null;if(!empty($_GET['edit'])){$e=$db->prepare("SELECT * FROM users WHERE id=?");$e->execute([$_GET['edit']]);$editUser=$e->fetch();}
$roleColors=['admin'=>'#ef4444','manager'=>'#f59e0b','employee'=>'#10b981'];
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>User Management — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--text:#f0f4ff;--muted:#6b7a99;--sidebar-w:240px;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
body{background:var(--bg);color:var(--text);font-family:var(--font-body);display:flex;min-height:100vh;font-size:14px}
.sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);position:fixed;top:0;left:0;height:100vh;overflow-y:auto;display:flex;flex-direction:column}
.sidebar-logo{display:flex;align-items:center;gap:10px;padding:24px 20px;border-bottom:1px solid var(--border)}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:16px}
.logo-name{font-family:var(--font-head);font-weight:700;font-size:17px;background:linear-gradient(135deg,#fff 30%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav{padding:16px 12px;flex:1}.nav-section{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:8px 8px 4px;margin-top:8px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;transition:all .15s;margin-bottom:2px}
.nav-item:hover,.nav-item.active{background:rgba(59,130,246,.1);color:var(--accent)}.nav-item.active{font-weight:600}
.nav-icon{font-size:16px;width:20px;text-align:center}
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.user-info{flex:1}.user-name{font-size:13px;font-weight:600}.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.users-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.user-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;transition:border-color .2s}
.user-card:hover{border-color:rgba(59,130,246,.3)}
.user-head{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.u-avatar{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-weight:800;font-size:20px;color:#fff;flex-shrink:0}
.u-name{font-family:var(--font-head);font-size:15px;font-weight:700}
.u-email{font-size:12px;color:var(--muted)}
.role-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:12px}
.user-stats{display:flex;gap:16px;margin-bottom:16px}
.u-stat{text-align:center;flex:1;background:var(--surface2);border-radius:10px;padding:10px}
.u-stat-val{font-family:var(--font-head);font-size:20px;font-weight:800}
.u-stat-lbl{font-size:10px;color:var(--muted)}
.user-footer{display:flex;gap:8px;padding-top:14px;border-top:1px solid var(--border)}
.inactive-overlay{opacity:.5}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center}
.close-btn{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
input,select{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;width:100%}
input:focus,select:focus{border-color:var(--accent)}
select option{background:var(--surface2)}
.hint{font-size:11px;color:var(--muted);margin-top:3px}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}
</style></head><body>
<aside class="sidebar">
  <div class="sidebar-logo"><div class="logo-icon">M</div><span class="logo-name">MediaBoost</span></div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':''; ?>"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="leads.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='leads.php'?'active':''; ?>">
      <span class="nav-icon">🎯</span> Lead Management
      <?php $__nb=$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn(); if($__nb>0): ?><span class="nav-badge"><?php echo $__nb; ?></span><?php endif; ?>
    </a>
    <a href="clients.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='clients.php'?'active':''; ?>"><span class="nav-icon">👥</span> Clients</a>
    <a href="projects.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='projects.php'?'active':''; ?>"><span class="nav-icon">📁</span> Projects</a>
    <div class="nav-section">Reports</div>
    <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='reports.php'?'active':''; ?>"><span class="nav-icon">📈</span> Analytics Reports</a>
    <a href="bookings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='bookings.php'?'active':''; ?>">
      <span class="nav-icon">📅</span> Bookings
      <?php $_nb_book=$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(); if($_nb_book>0): ?><span class="nav-badge"><?php echo $_nb_book; ?></span><?php endif; ?>
    </a>
    <div class="nav-section">Settings</div>
    <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='users.php'?'active':''; ?>"><span class="nav-icon">⚙️</span> User Management</a>
    <a href="portfolio.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='portfolio.php'?'active':''; ?>"><span class="nav-icon">🖼️</span> Portfolio</a>
    <a href="services.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='services.php'?'active':''; ?>"><span class="nav-icon">🛠️</span> Services</a>
    <a href="notifications.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='notifications.php'?'active':''; ?>">
      <span class="nav-icon">🔔</span> Notifications
      <?php $_nb_notif=$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn(); if($_nb_notif>0): ?><span class="nav-badge"><?php echo $_nb_notif; ?></span><?php endif; ?>
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="user-avatar"><?php echo strtoupper($user['name'][0]); ?></div>
    <div class="user-info"><div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div><div class="user-role"><?php echo $user['role']; ?></div></div>
    <a href="../logout.php" class="logout-btn" title="Logout">⏻</a>
  </div>
</aside>
<main class="main">
  <div class="page-header"><div class="page-title">⚙️ User Management</div><button class="btn btn-primary" onclick="openModal('addModal')">+ Add User</button></div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <div class="users-grid">
    <?php foreach($users as $u): ?>
    <?php $rc=$roleColors[$u['role']]??'#6b7a99';?>
    <div class="user-card <?php echo !$u['is_active']?'inactive-overlay':''; ?>">
      <div class="user-head">
        <div class="u-avatar" style="background:linear-gradient(135deg,<?php echo $rc; ?>,<?php echo $rc; ?>88)"><?php echo strtoupper($u['name'][0]); ?></div>
        <div><div class="u-name"><?php echo htmlspecialchars($u['name']); ?></div><div class="u-email"><?php echo htmlspecialchars($u['email']); ?></div></div>
      </div>
      <span class="role-badge" style="background:<?php echo $rc; ?>22;color:<?php echo $rc; ?>"><?php echo ucfirst($u['role']); ?></span>
      <div class="user-stats">
        <div class="u-stat"><div class="u-stat-val"><?php echo $u['lead_count']; ?></div><div class="u-stat-lbl">Leads</div></div>
        <div class="u-stat"><div class="u-stat-val"><?php echo $u['project_count']; ?></div><div class="u-stat-lbl">Projects</div></div>
      </div>
      <?php if($u['phone']): ?><div style="font-size:12px;color:var(--muted);margin-bottom:12px">📱 <?php echo htmlspecialchars($u['phone']); ?></div><?php endif; ?>
      <div class="user-footer">
        <a href="users.php?edit=<?php echo $u['id']; ?>" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center">✏️ Edit</a>
        <form method="POST" style="flex:1">
          <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <button class="btn btn-ghost btn-sm" style="width:100%;justify-content:center"><?php echo $u['is_active']?'🔴 Deactivate':'🟢 Activate'; ?></button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New User <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Name *</label><input type="text" name="name" required placeholder="Full name"></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="email@example.com"></div>
      <div class="form-group"><label>Password *</label><input type="text" name="password" required placeholder="Login password"></div>
      <div class="form-group"><label>Role</label><select name="role"><option value="employee">Employee</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="03001234567"></div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<?php if($editUser): ?>
<div class="modal-overlay open">
  <div class="modal">
    <div class="modal-title">Edit User <button class="close-btn" onclick="window.location='users.php'">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
      <div class="form-group"><label>Name *</label><input type="text" name="name" required value="<?php echo htmlspecialchars($editUser['name']); ?>"></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" required value="<?php echo htmlspecialchars($editUser['email']); ?>"></div>
      <div class="form-group"><label>New Password</label><input type="text" name="password" placeholder="Leave blank to keep current password"><div class="hint">Leave blank to keep unchanged</div></div>
      <div class="form-group"><label>Role</label><select name="role"><?php foreach(['employee','manager','admin'] as $r): ?><option value="<?php echo $r; ?>" <?php echo $editUser['role']===$r?'selected':''; ?>><?php echo ucfirst($r); ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($editUser['phone']??''); ?>"></div>
      <div class="form-actions"><a href="users.php" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>function openModal(id){document.getElementById(id).classList.add('open')}function closeModal(id){document.getElementById(id).classList.remove('open')}</script>
</body></html>
