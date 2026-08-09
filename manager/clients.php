<?php
error_reporting(E_ERROR|E_PARSE);
require_once '../includes/auth.php';
requireRole(['admin','manager','employee']);
if(isAdmin()){header('Location: ../admin/clients.php');exit;}
$db=getDB();$user=currentUser();
$clients=$db->query("SELECT c.*,u.name AS manager_name,(SELECT COUNT(*) FROM projects WHERE client_id=c.id) AS project_count FROM clients c LEFT JOIN users u ON c.assigned_to=u.id WHERE c.is_active=1 ORDER BY c.created_at DESC")->fetchAll();
$unread=$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
$new_leads=$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
$pending_bk=$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
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
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px}
.role-tag{margin:12px 20px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:8px 12px;font-size:12px;font-weight:600;color:var(--amber)}
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--amber),#f97316);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.user-info{flex:1}.user-name{font-size:13px;font-weight:600}.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.view-only-badge{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);color:var(--amber);font-size:12px;font-weight:600;padding:6px 14px;border-radius:8px}
.clients-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.client-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;transition:border-color .2s}
.client-card:hover{border-color:rgba(59,130,246,.3)}
.client-head{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.client-avatar{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-weight:800;font-size:18px;color:#fff}
.client-name{font-family:var(--font-head);font-size:15px;font-weight:700}
.client-person{font-size:12px;color:var(--muted)}
.pkg{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.pkg-basic{background:rgba(107,122,153,.15);color:var(--muted)}.pkg-standard{background:rgba(245,158,11,.12);color:var(--amber)}.pkg-premium{background:rgba(16,185,129,.12);color:var(--green)}
.client-info{display:flex;flex-direction:column;gap:7px;margin-bottom:14px}
.info-row{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:8px}
.client-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border)}
.proj-count{font-size:12px;color:var(--muted)}.proj-count span{color:var(--text);font-weight:600}
</style></head><body>
<aside class="sidebar">
  <div class="sidebar-logo"><div class="logo-icon">M</div><span class="logo-name">MediaBoost</span></div>
  <div class="role-tag">📊 Manager Panel</div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="../admin/leads.php" class="nav-item"><span class="nav-icon">🎯</span> Leads <?php if($new_leads>0): ?><span class="nav-badge"><?= $new_leads ?></span><?php endif; ?></a>
    <a href="clients.php" class="nav-item active"><span class="nav-icon">👥</span> Clients</a>
    <a href="projects.php" class="nav-item"><span class="nav-icon">📁</span> Projects</a>
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
  <div class="page-header">
    <div><div class="page-title">👥 Clients</div><div class="page-sub">View all active clients</div></div>
    <span class="view-only-badge">👁 View Only</span>
  </div>
  <div class="clients-grid">
    <?php foreach($clients as $c): ?>
    <div class="client-card">
      <div class="client-head">
        <div class="client-avatar"><?= strtoupper($c['business_name'][0]) ?></div>
        <div>
          <div class="client-name"><?= htmlspecialchars($c['business_name']) ?></div>
          <div class="client-person"><?= htmlspecialchars($c['contact_person']) ?></div>
        </div>
        <span class="pkg pkg-<?= $c['package'] ?>" style="margin-left:auto"><?= ucfirst($c['package']) ?></span>
      </div>
      <div class="client-info">
        <div class="info-row">📧 <?= htmlspecialchars($c['email']) ?></div>
        <div class="info-row">📱 <?= htmlspecialchars($c['phone']??'—') ?></div>
        <div class="info-row">🏙️ <?= htmlspecialchars($c['city']??'—') ?></div>
        <div class="info-row">👤 Manager: <?= htmlspecialchars($c['manager_name']??'Unassigned') ?></div>
      </div>
      <div class="client-footer">
        <div class="proj-count">Projects: <span><?= $c['project_count'] ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($clients)): ?><div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--muted)">No active clients found.</div><?php endif; ?>
  </div>
</main>
</body></html>
