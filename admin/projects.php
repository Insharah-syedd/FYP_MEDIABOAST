<?php
require_once '../includes/auth.php';
error_reporting(E_ERROR|E_PARSE);
requireRole(['admin','manager','employee']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();$action=$_POST['action']??'';
    if($action==='add'){$db->prepare("INSERT INTO projects (client_id,title,description,service_type,status,start_date,deadline,assigned_to,progress,budget) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$_POST['client_id'],$_POST['title'],$_POST['description'],$_POST['service_type'],$_POST['status'],$_POST['start_date']?:null,$_POST['deadline']?:null,$_POST['assigned_to']?:null,$_POST['progress']?:0,$_POST['budget']?:null]);$msg='Project added successfully! ✅';}
    if($action==='edit'){$db->prepare("UPDATE projects SET client_id=?,title=?,description=?,service_type=?,status=?,start_date=?,deadline=?,assigned_to=?,progress=?,budget=? WHERE id=?")->execute([$_POST['client_id'],$_POST['title'],$_POST['description'],$_POST['service_type'],$_POST['status'],$_POST['start_date']?:null,$_POST['deadline']?:null,$_POST['assigned_to']?:null,$_POST['progress']?:0,$_POST['budget']?:null,$_POST['project_id']]);$msg='Project updated successfully! ✅';}
    if($action==='delete'){$db->prepare("DELETE FROM projects WHERE id=?")->execute([$_POST['project_id']]);$msg='Project deleted!';}
}
$projects=$db->query("SELECT p.*,c.business_name,u.name AS assigned_name FROM projects p LEFT JOIN clients c ON p.client_id=c.id LEFT JOIN users u ON p.assigned_to=u.id ORDER BY p.created_at DESC")->fetchAll();
$clients=$db->query("SELECT id,business_name FROM clients WHERE is_active=1")->fetchAll();
$users=$db->query("SELECT id,name FROM users WHERE is_active=1")->fetchAll();
$editProject=null;if(!empty($_GET['edit'])){$e=$db->prepare("SELECT * FROM projects WHERE id=?");$e->execute([$_GET['edit']]);$editProject=$e->fetch();}
$statusColors=['pending'=>'amber','in_progress'=>'blue','review'=>'purple','completed'=>'green','paused'=>'muted','cancelled'=>'red'];
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Projects — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--purple:#a855f7;--text:#f0f4ff;--muted:#6b7a99;--sidebar-w:240px;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
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
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}.btn-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red)}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.projects-list{display:flex;flex-direction:column;gap:16px}
.project-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;transition:border-color .2s}
.project-card:hover{border-color:rgba(59,130,246,.3)}
.project-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;gap:12px}
.project-title{font-family:var(--font-head);font-size:16px;font-weight:700;margin-bottom:4px}
.project-client{font-size:12px;color:var(--muted)}
.project-meta{display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.meta-item{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:4px}
.progress-wrap{margin-bottom:16px}
.progress-label{display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px}
.progress-label span:last-child{font-weight:600;color:var(--accent)}
.progress-bar{height:6px;background:var(--border);border-radius:3px;overflow:hidden}
.progress-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .6s ease}
.project-footer{display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--border)}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-pending{background:rgba(245,158,11,.12);color:var(--amber)}.s-in_progress{background:rgba(59,130,246,.12);color:var(--accent)}.s-review{background:rgba(168,85,247,.12);color:var(--purple)}.s-completed{background:rgba(16,185,129,.12);color:var(--green)}.s-paused{background:rgba(107,122,153,.15);color:var(--muted)}.s-cancelled{background:rgba(239,68,68,.12);color:var(--red)}
.service-tag{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(99,102,241,.12);color:#a5b4fc;text-transform:uppercase;letter-spacing:.05em}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center}
.close-btn{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
input,select,textarea{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;width:100%}
input:focus,select:focus,textarea:focus{border-color:var(--accent)}
select option{background:var(--surface2)}
textarea{resize:vertical;min-height:70px}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}
.budget-badge{font-size:13px;font-weight:600;color:var(--green)}
.empty{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:60px;text-align:center;color:var(--muted)}
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
    <div><div class="page-title">📁 Projects</div><div class="page-sub">Track all projects</div></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ New Project</button>
  </div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <?php if(empty($projects)): ?>
  <div class="empty"><div style="font-size:48px;margin-bottom:16px">📁</div><div style="font-size:16px;font-weight:600;margin-bottom:8px">No projects found</div><div>+ New Project se Add your first project</div></div>
  <?php else: ?>
  <div class="projects-list">
    <?php foreach($projects as $p): ?>
    <div class="project-card">
      <div class="project-top">
        <div>
          <div class="project-title"><?php echo htmlspecialchars($p['title']); ?></div>
          <div class="project-client">👥 <?php echo htmlspecialchars($p['business_name']??'Unknown Client'); ?></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
          <span class="service-tag"><?php echo str_replace('_',' ',$p['service_type']); ?></span>
          <span class="status s-<?php echo $p['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$p['status'])); ?></span>
        </div>
      </div>
      <div class="project-meta">
        <?php if($p['start_date']): ?><div class="meta-item">📅 Start: <?php echo date('d M Y',strtotime($p['start_date'])); ?></div><?php endif; ?>
        <?php if($p['deadline']): ?><div class="meta-item">⏰ Deadline: <?php echo date('d M Y',strtotime($p['deadline'])); ?></div><?php endif; ?>
        <?php if($p['assigned_name']): ?><div class="meta-item">👤 <?php echo htmlspecialchars($p['assigned_name']); ?></div><?php endif; ?>
        <?php if($p['budget']): ?><div class="meta-item">💰 Rs. <?php echo number_format($p['budget']); ?></div><?php endif; ?>
      </div>
      <div class="progress-wrap">
        <div class="progress-label"><span>Progress</span><span><?php echo $p['progress']; ?>%</span></div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $p['progress']; ?>%"></div></div>
      </div>
      <div class="project-footer">
        <div style="font-size:12px;color:var(--muted)"><?php echo $p['description']?htmlspecialchars(substr($p['description'],0,80)).'...':''; ?></div>
        <div style="display:flex;gap:6px">
          <a href="projects.php?edit=<?php echo $p['id']; ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
          <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete?')">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
            <button class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New Project <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group full"><label>Project Title *</label><input type="text" name="title" required placeholder="Campaign or project name..."></div>
        <div class="form-group"><label>Client *</label><select name="client_id" required><option value="">Select Client</option><?php foreach($clients as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['business_name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Service Type</label><select name="service_type"><option value="seo">SEO</option><option value="social_media">Social Media</option><option value="web_dev">Web Dev</option><option value="content">Content</option><option value="backlinks">Backlinks</option><option value="other">Other</option></select></div>
        <div class="form-group"><label>Status</label><select name="status"><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="review">Review</option><option value="completed">Completed</option><option value="paused">Paused</option><option value="cancelled">Cancelled</option></select></div>
        <div class="form-group"><label>Assign To</label><select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
        <div class="form-group"><label>Deadline</label><input type="date" name="deadline"></div>
        <div class="form-group"><label>Budget (Rs)</label><input type="number" name="budget" placeholder="15000"></div>
        <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
        <div class="form-group full"><label>Description</label><textarea name="description" placeholder="Project description..."></textarea></div>
      </div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<?php if($editProject): ?>
<div class="modal-overlay open">
  <div class="modal">
    <div class="modal-title">Edit Project <button class="close-btn" onclick="window.location='projects.php'">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="project_id" value="<?php echo $editProject['id']; ?>">
      <div class="form-grid">
        <div class="form-group full"><label>Project Title *</label><input type="text" name="title" required value="<?php echo htmlspecialchars($editProject['title']); ?>"></div>
        <div class="form-group"><label>Client *</label><select name="client_id" required><?php foreach($clients as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $editProject['client_id']==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['business_name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Service Type</label><select name="service_type"><?php foreach(['seo','social_media','web_dev','content','backlinks','other'] as $st): ?><option value="<?php echo $st; ?>" <?php echo $editProject['service_type']===$st?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$st)); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Status</label><select name="status"><?php foreach(['pending','in_progress','review','completed','paused','cancelled'] as $st): ?><option value="<?php echo $st; ?>" <?php echo $editProject['status']===$st?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$st)); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Assign To</label><select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?php echo $u['id']; ?>" <?php echo $editProject['assigned_to']==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?php echo $editProject['start_date']??''; ?>"></div>
        <div class="form-group"><label>Deadline</label><input type="date" name="deadline" value="<?php echo $editProject['deadline']??''; ?>"></div>
        <div class="form-group"><label>Budget (Rs)</label><input type="number" name="budget" value="<?php echo $editProject['budget']??''; ?>"></div>
        <div class="form-group"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?php echo $editProject['progress']; ?>"></div>
        <div class="form-group full"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($editProject['description']??''); ?></textarea></div>
      </div>
      <div class="form-actions"><a href="projects.php" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>function openModal(id){document.getElementById(id).classList.add('open')}function closeModal(id){document.getElementById(id).classList.remove('open')}</script>
</body></html>
