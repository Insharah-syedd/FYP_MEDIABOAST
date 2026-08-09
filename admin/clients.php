<?php
require_once '../includes/auth.php';
error_reporting(E_ERROR|E_PARSE);
requireRole(['admin','manager','employee']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();$action=$_POST['action']??'';
    if($action==='add'){$db->prepare("INSERT INTO clients (business_name,contact_person,email,password,phone,city,industry,package,assigned_to) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$_POST['business_name'],$_POST['contact_person'],$_POST['email'],$_POST['password'],$_POST['phone'],$_POST['city'],$_POST['industry'],$_POST['package'],$_POST['assigned_to']?:null]);$msg='Client added successfully! ✅';}
    if($action==='edit'){$pass=$_POST['password']??'';if($pass){$db->prepare("UPDATE clients SET business_name=?,contact_person=?,email=?,password=?,phone=?,city=?,industry=?,package=?,assigned_to=? WHERE id=?")->execute([$_POST['business_name'],$_POST['contact_person'],$_POST['email'],$pass,$_POST['phone'],$_POST['city'],$_POST['industry'],$_POST['package'],$_POST['assigned_to']?:null,$_POST['client_id']]);}else{$db->prepare("UPDATE clients SET business_name=?,contact_person=?,email=?,phone=?,city=?,industry=?,package=?,assigned_to=? WHERE id=?")->execute([$_POST['business_name'],$_POST['contact_person'],$_POST['email'],$_POST['phone'],$_POST['city'],$_POST['industry'],$_POST['package'],$_POST['assigned_to']?:null,$_POST['client_id']]);}$msg='Updated! ✅';}
    if($action==='toggle'){$db->prepare("UPDATE clients SET is_active=NOT is_active WHERE id=?")->execute([$_POST['client_id']]);$msg='Status changed!';}
}
$clients=$db->query("SELECT c.*,u.name AS manager_name,(SELECT COUNT(*) FROM projects WHERE client_id=c.id) AS project_count FROM clients c LEFT JOIN users u ON c.assigned_to=u.id ORDER BY c.created_at DESC")->fetchAll();
$users=$db->query("SELECT id,name FROM users WHERE is_active=1")->fetchAll();
$editClient=null;if(!empty($_GET['edit'])){$e=$db->prepare("SELECT * FROM clients WHERE id=?");$e->execute([$_GET['edit']]);$editClient=$e->fetch();}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clients — MediaBoost</title>
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
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800;letter-spacing:-.5px}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}
.clients-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.client-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;transition:border-color .2s,transform .15s}
.client-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.client-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px}
.client-avatar{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-weight:800;font-size:20px;color:#fff;flex-shrink:0}
.client-name{font-family:var(--font-head);font-size:16px;font-weight:700;margin-bottom:2px}
.client-person{font-size:12px;color:var(--muted)}
.pkg{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.pkg-basic{background:rgba(107,122,153,.15);color:var(--muted)}.pkg-standard{background:rgba(245,158,11,.12);color:var(--amber)}.pkg-premium{background:rgba(16,185,129,.12);color:var(--green)}
.client-info{display:flex;flex-direction:column;gap:8px;margin-bottom:16px}
.info-row{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted)}
.client-footer{display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--border)}
.project-count{font-size:12px;color:var(--muted)}.project-count span{color:var(--text);font-weight:600}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center}
.close-btn{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
input,select{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;width:100%}
input:focus,select:focus{border-color:var(--accent)}
select option{background:var(--surface2)}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}
.hint{font-size:11px;color:var(--muted);margin-top:3px}
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
  <div class="page-header">
    <div><div class="page-title">👥 Clients</div><div class="page-sub">Manage all clients</div></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Client</button>
  </div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <div class="clients-grid">
    <?php foreach($clients as $c): ?>
    <div class="client-card">
      <div class="client-head">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="client-avatar"><?php echo strtoupper($c['business_name'][0]); ?></div>
          <div><div class="client-name"><?php echo htmlspecialchars($c['business_name']); ?></div><div class="client-person"><?php echo htmlspecialchars($c['contact_person']); ?></div></div>
        </div>
        <span class="pkg pkg-<?php echo $c['package']; ?>"><?php echo ucfirst($c['package']); ?></span>
      </div>
      <div class="client-info">
        <div class="info-row">📧 <?php echo htmlspecialchars($c['email']); ?></div>
        <div class="info-row">📱 <?php echo htmlspecialchars($c['phone']??'—'); ?></div>
        <div class="info-row">🏙️ <?php echo htmlspecialchars($c['city']??'—'); ?></div>
        <div class="info-row">👤 Manager: <?php echo htmlspecialchars($c['manager_name']??'Unassigned'); ?></div>
      </div>
      <div class="client-footer">
        <div class="project-count">Projects: <span><?php echo $c['project_count']; ?></span></div>
        <div style="display:flex;gap:6px">
          <a href="clients.php?edit=<?php echo $c['id']; ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="client_id" value="<?php echo $c['id']; ?>">
            <button class="btn btn-ghost btn-sm"><?php echo $c['is_active']?'🔴 Deactivate':'🟢 Activate'; ?></button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($clients)): ?><p style="color:var(--muted);grid-column:1/-1;text-align:center;padding:40px">No clients found.</p><?php endif; ?>
  </div>
</main>
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New Client <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>Business Name *</label><input type="text" name="business_name" required></div>
        <div class="form-group"><label>Contact Person *</label><input type="text" name="contact_person" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Login Password *</label><input type="text" name="password" required><div class="hint">For client portal login</div></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
        <div class="form-group"><label>City</label><input type="text" name="city"></div>
        <div class="form-group"><label>Industry</label><input type="text" name="industry"></div>
        <div class="form-group"><label>Package</label><select name="package"><option value="basic">Basic</option><option value="standard">Standard</option><option value="premium">Premium</option></select></div>
        <div class="form-group full"><label>Assign Manager</label><select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<?php if($editClient): ?>
<div class="modal-overlay open">
  <div class="modal">
    <div class="modal-title">Edit Client <button class="close-btn" onclick="window.location='clients.php'">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">
      <div class="form-grid">
        <div class="form-group"><label>Business Name *</label><input type="text" name="business_name" required value="<?php echo htmlspecialchars($editClient['business_name']); ?>"></div>
        <div class="form-group"><label>Contact Person *</label><input type="text" name="contact_person" required value="<?php echo htmlspecialchars($editClient['contact_person']); ?>"></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required value="<?php echo htmlspecialchars($editClient['email']); ?>"></div>
        <div class="form-group"><label>New Password</label><input type="text" name="password" placeholder="Leave blank to keep current password"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($editClient['phone']??''); ?>"></div>
        <div class="form-group"><label>City</label><input type="text" name="city" value="<?php echo htmlspecialchars($editClient['city']??''); ?>"></div>
        <div class="form-group"><label>Industry</label><input type="text" name="industry" value="<?php echo htmlspecialchars($editClient['industry']??''); ?>"></div>
        <div class="form-group"><label>Package</label><select name="package"><?php foreach(['basic','standard','premium'] as $p): ?><option value="<?php echo $p; ?>" <?php echo $editClient['package']===$p?'selected':''; ?>><?php echo ucfirst($p); ?></option><?php endforeach; ?></select></div>
        <div class="form-group full"><label>Assign Manager</label><select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?php echo $u['id']; ?>" <?php echo $editClient['assigned_to']==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['name']); ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="form-actions"><a href="clients.php" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>function openModal(id){document.getElementById(id).classList.add('open')}function closeModal(id){document.getElementById(id).classList.remove('open')}</script>
</body></html>
