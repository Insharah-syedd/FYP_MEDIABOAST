<?php
error_reporting(E_ERROR|E_PARSE);
require_once '../includes/auth.php';
requireRole(['admin','manager','employee']);
$db=getDB();$user=currentUser();

// Mark all as read when page is opened
$db->query("UPDATE notifications SET is_read=1");

// Delete single
if(isset($_GET['delete'])){
    $db->prepare("DELETE FROM notifications WHERE id=?")->execute([$_GET['delete']]);
    header('Location: notifications.php');exit;
}
// Delete all
if(isset($_GET['clear_all'])){
    $db->query("DELETE FROM notifications");
    header('Location: notifications.php');exit;
}

$notifications=$db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 50")->fetchAll();
$unread=$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
$new_leads=$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
$pending_bk=$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notifications — MediaBoost</title>
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
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.user-info{flex:1}.user-name{font-size:13px;font-weight:600}.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--red);color:var(--red)}
.notif-list{display:flex;flex-direction:column;gap:10px}
.notif-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;display:flex;align-items:flex-start;gap:14px;transition:border-color .2s}
.notif-card:hover{border-color:rgba(59,130,246,.2)}
.notif-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.icon-new_lead{background:rgba(59,130,246,.12)}.icon-booking{background:rgba(249,115,22,.12)}.icon-system{background:rgba(107,122,153,.12)}.icon-lead_status{background:rgba(245,158,11,.12)}.icon-project_update{background:rgba(99,102,241,.12)}.icon-report_ready{background:rgba(16,185,129,.12)}
.notif-body{flex:1}
.notif-msg{font-size:14px;color:var(--text);margin-bottom:5px;line-height:1.5}
.notif-time{font-size:11px;color:var(--muted)}
.del-btn{color:var(--muted);text-decoration:none;font-size:16px;transition:color .15s;flex-shrink:0;padding:4px}
.del-btn:hover{color:var(--red)}
.empty-state{text-align:center;padding:80px;color:var(--muted)}
.empty-icon{font-size:60px;margin-bottom:16px}
.empty-title{font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:8px;color:var(--text)}
.all-read-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;color:var(--green);margin-bottom:20px}
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
    <div><div class="page-title">🔔 Notifications</div><div class="page-sub">All system activity alerts — marked as read on open</div></div>
    <?php if(!empty($notifications)): ?>
    <a href="notifications.php?clear_all=1" class="btn btn-ghost" onclick="return confirm('Delete all notifications?')">🗑 Clear All</a>
    <?php endif; ?>
  </div>

  <div class="all-read-badge">✅ All notifications marked as read</div>

  <?php if(empty($notifications)): ?>
  <div class="empty-state"><div class="empty-icon">🔔</div><div class="empty-title">No Notifications</div><div>New lead and booking alerts will appear here.</div></div>
  <?php else: ?>
  <div class="notif-list">
    <?php
    $icons=['new_lead'=>'🎯','lead_status'=>'🔄','project_update'=>'📁','report_ready'=>'📊','booking'=>'📅','system'=>'⚙️'];
    foreach($notifications as $n):
      $icon=$icons[$n['type']]??'🔔';
      $t=time()-strtotime($n['created_at']);
      $time=$t<60?'Just now':($t<3600?floor($t/60).' min ago':($t<86400?floor($t/3600).' hours ago':date('d M Y, h:i A',strtotime($n['created_at']))));
    ?>
    <div class="notif-card">
      <div class="notif-icon icon-<?= $n['type'] ?>"><?= $icon ?></div>
      <div class="notif-body">
        <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
        <div class="notif-time">🕐 <?= $time ?></div>
      </div>
      <a href="notifications.php?delete=<?= $n['id'] ?>" class="del-btn" onclick="return confirm('Delete?')" title="Delete">🗑</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
</body></html>
