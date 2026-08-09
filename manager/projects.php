<?php
error_reporting(E_ERROR|E_PARSE);
require_once '../includes/auth.php';
requireRole(['admin','manager','employee']);
if(isAdmin()){header('Location: ../admin/projects.php');exit;}
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    if(($_POST['action']??'')==='update_progress'){
        $db->prepare("UPDATE projects SET progress=?,status=? WHERE id=?")->execute([$_POST['progress'],$_POST['status'],$_POST['project_id']]);
        $msg='Project updated! ✅';
    }
}
$projects=$db->query("SELECT p.*,c.business_name FROM projects p LEFT JOIN clients c ON p.client_id=c.id ORDER BY p.status ASC,p.created_at DESC")->fetchAll();
$unread=$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
$new_leads=$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
$pending_bk=$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Projects — MediaBoost</title>
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
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px}
.role-tag{margin:12px 20px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:8px 12px;font-size:12px;font-weight:600;color:var(--amber)}
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--amber),#f97316);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.user-info{flex:1}.user-name{font-size:13px;font-weight:600}.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800;margin-bottom:4px}
.page-sub{font-size:13px;color:var(--muted);margin-bottom:24px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.projects-list{display:flex;flex-direction:column;gap:14px}
.project-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px}
.project-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px}
.project-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:4px}
.project-client{font-size:12px;color:var(--muted)}
.service-tag{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(99,102,241,.12);color:#a5b4fc;text-transform:uppercase}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-pending{background:rgba(245,158,11,.12);color:var(--amber)}.s-in_progress{background:rgba(59,130,246,.12);color:var(--accent)}.s-review{background:rgba(168,85,247,.12);color:#a855f7}.s-completed{background:rgba(16,185,129,.12);color:var(--green)}.s-paused{background:rgba(107,122,153,.15);color:var(--muted)}
.progress-section{margin:14px 0}
.progress-label{display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px}
.progress-label span:last-child{font-weight:700;color:var(--accent)}
.progress-bar{height:8px;background:var(--border);border-radius:4px;overflow:hidden}
.progress-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .5s ease}
.update-form{display:flex;align-items:center;gap:10px;padding-top:14px;border-top:1px solid var(--border);flex-wrap:wrap}
.update-form label{font-size:12px;font-weight:600;color:var(--muted)}
.update-form input[type=range]{flex:1;min-width:100px;accent-color:var(--accent)}
.update-form select,.update-form .prog-val{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:6px 10px;color:var(--text);font-size:12px;outline:none}
</style></head><body>
<aside class="sidebar">
  <div class="sidebar-logo"><div class="logo-icon">M</div><span class="logo-name">MediaBoost</span></div>
  <div class="role-tag">📊 Manager Panel</div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="../admin/leads.php" class="nav-item"><span class="nav-icon">🎯</span> Leads <?php if($new_leads>0): ?><span class="nav-badge"><?= $new_leads ?></span><?php endif; ?></a>
    <a href="clients.php" class="nav-item"><span class="nav-icon">👥</span> Clients</a>
    <a href="projects.php" class="nav-item active"><span class="nav-icon">📁</span> Projects</a>
    <div class="nav-section">Reports</div>
    <a href="reports.php" class="nav-item"><span class="nav-icon">📈</span> Analytics Reports</a>
    <a href="bookings.php" class="nav-item"><span class="nav-icon">📅</span> Bookings <?php if($pending_bk>0): ?><span class="nav-badge"><?= $pending_bk ?></span><?php endif; ?></a>
    <div class="nav-section">Alerts</div>
    <a href="notifications.php" class="nav-item"><span class="nav-icon">🔔</span> Notifications <?php if($unread>0): ?><span class="nav-badge"><?= $unread ?></span><?php endif; ?></a>
  </nav>
  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper($user['name'][0]) ?></div>
    <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['name']) ?></div><div class="user-role"><?= $user['role'] ?></div></div>
    <a href="../logout.php" class="logout-btn">⏻</a>
  </div>
</aside>
<main class="main">
  <div class="page-title">📁 Projects</div>
  <div class="page-sub">Update project progress and status</div>
  <?php if($msg): ?><div class="alert"><?= $msg ?></div><?php endif; ?>
  <div class="projects-list">
    <?php foreach($projects as $p): ?>
    <div class="project-card">
      <div class="project-top">
        <div>
          <div class="project-title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="project-client">👥 <?= htmlspecialchars($p['business_name']??'—') ?></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <span class="service-tag"><?= str_replace('_',' ',$p['service_type']) ?></span>
          <span class="status s-<?= $p['status'] ?>"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
        </div>
      </div>
      <div class="progress-section">
        <div class="progress-label"><span>Progress</span><span id="val-<?= $p['id'] ?>"><?= $p['progress'] ?>%</span></div>
        <div class="progress-bar"><div class="progress-fill" id="bar-<?= $p['id'] ?>" style="width:<?= $p['progress'] ?>%"></div></div>
      </div>
      <form method="POST" class="update-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="update_progress">
        <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
        <label>Progress:</label>
        <input type="range" name="progress" min="0" max="100" value="<?= $p['progress'] ?>"
          oninput="document.getElementById('val-<?= $p['id'] ?>').textContent=this.value+'%';document.getElementById('bar-<?= $p['id'] ?>').style.width=this.value+'%'">
        <label>Status:</label>
        <select name="status">
          <?php foreach(['pending','in_progress','review','completed','paused','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Update</button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if(empty($projects)): ?><div style="text-align:center;padding:60px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:14px">No projects found.</div><?php endif; ?>
  </div>
</main>
</body></html>
