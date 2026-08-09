<?php
require_once '../includes/auth.php';
requireRole(['admin','manager']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$action=$_POST['action']??'';
  if($action==='add'){$db->prepare("INSERT INTO services (name,description,price) VALUES (?,?,?)")->execute([$_POST['name'],$_POST['description'],$_POST['price']?:null]);$msg='Service added successfully! ✅';}
  if($action==='edit'){$db->prepare("UPDATE services SET name=?,description=?,price=?,is_active=? WHERE id=?")->execute([$_POST['name'],$_POST['description'],$_POST['price']?:null,$_POST['is_active']?:0,$_POST['service_id']]);$msg='Updated! ✅';}
  if($action==='delete'){$db->prepare("DELETE FROM services WHERE id=?")->execute([$_POST['service_id']]);$msg='Deleted!';}
}
$services=$db->query("SELECT * FROM services ORDER BY is_active DESC,id ASC")->fetchAll();
$editSvc=null;if(!empty($_GET['edit'])){$e=$db->prepare("SELECT * FROM services WHERE id=?");$e->execute([$_GET['edit']]);$editSvc=$e->fetch();}
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Services — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--red:#ef4444;--text:#f0f4ff;--muted:#6b7a99;--sidebar-w:240px;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
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
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}.btn-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red)}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.svc-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;transition:border-color .2s,transform .15s}
.svc-card:hover{border-color:rgba(59,130,246,.3);transform:translateY(-2px)}
.svc-card.inactive{opacity:.5}
.svc-icon{font-size:36px;margin-bottom:14px}
.svc-name{font-family:var(--font-head);font-size:16px;font-weight:700;margin-bottom:6px}
.svc-desc{font-size:12px;color:var(--muted);margin-bottom:14px;line-height:1.5}
.svc-price{font-family:var(--font-head);font-size:22px;font-weight:800;color:var(--green);margin-bottom:16px}
.svc-price span{font-size:12px;color:var(--muted);font-family:var(--font-body);font-weight:400}
.svc-footer{display:flex;gap:8px;padding-top:14px;border-top:1px solid var(--border)}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:460px;max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center}
.close-btn{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
input,select,textarea{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;width:100%}
input:focus,select:focus,textarea:focus{border-color:var(--accent)}
select option{background:var(--surface2)}
textarea{resize:vertical;min-height:70px}
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
  <div class="page-header"><div class="page-title">🛠️ Services</div><button class="btn btn-primary" onclick="openModal('addModal')">+ Add Service</button></div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <div class="services-grid">
    <?php $icons=['SEO Optimization'=>'🔍','Social Media Marketing'=>'📱','Web Development'=>'🌐','Content Writing'=>'✍️','Backlink Building'=>'🔗']; ?>
    <?php foreach($services as $s): ?>
    <div class="svc-card <?php echo !$s['is_active']?'inactive':''; ?>">
      <div class="svc-icon"><?php echo $icons[$s['name']]??'🛠️'; ?></div>
      <div class="svc-name"><?php echo htmlspecialchars($s['name']); ?></div>
      <div class="svc-desc"><?php echo htmlspecialchars($s['description']??''); ?></div>
      <?php if($s['price']): ?><div class="svc-price">Rs. <?php echo number_format($s['price']); ?> <span>/ month</span></div><?php endif; ?>
      <div class="svc-footer">
        <a href="services.php?edit=<?php echo $s['id']; ?>" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center">✏️ Edit</a>
        <form method="POST" onsubmit="return confirm('Delete?')" style="flex:1">
          <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
          <button class="btn btn-danger btn-sm" style="width:100%;justify-content:center">🗑</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New Service <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Service Name *</label><input type="text" name="name" required placeholder="SEO Optimization"></div>
      <div class="form-group"><label>Description</label><textarea name="description" placeholder="Service description..."></textarea></div>
      <div class="form-group"><label>Price (Rs/month)</label><input type="number" name="price" placeholder="15000"></div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<?php if($editSvc): ?>
<div class="modal-overlay open">
  <div class="modal">
    <div class="modal-title">Edit Service <button class="close-btn" onclick="window.location='services.php'">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="service_id" value="<?php echo $editSvc['id']; ?>">
      <div class="form-group"><label>Name *</label><input type="text" name="name" required value="<?php echo htmlspecialchars($editSvc['name']); ?>"></div>
      <div class="form-group"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($editSvc['description']??''); ?></textarea></div>
      <div class="form-group"><label>Price (Rs)</label><input type="number" name="price" value="<?php echo $editSvc['price']??''; ?>"></div>
      <div class="form-group"><label>Status</label><select name="is_active"><option value="1" <?php echo $editSvc['is_active']?'selected':''; ?>>Active</option><option value="0" <?php echo !$editSvc['is_active']?'selected':''; ?>>Inactive</option></select></div>
      <div class="form-actions"><a href="services.php" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>function openModal(id){document.getElementById(id).classList.add('open')}function closeModal(id){document.getElementById(id).classList.remove('open')}</script>
</body></html>
