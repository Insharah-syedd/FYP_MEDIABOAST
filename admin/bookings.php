<?php
require_once '../includes/auth.php';
error_reporting(E_ERROR|E_PARSE);
requireRole(['admin','manager','employee']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$action=$_POST['action']??'';
  if($action==='status'){$db->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$_POST['status'],$_POST['booking_id']]);$msg='Status updated successfully! ✅';}
  if($action==='delete'){$db->prepare("DELETE FROM bookings WHERE id=?")->execute([$_POST['booking_id']]);$msg='Booking deleted!';}
}
// Auto-mark all pending bookings as reviewed when page is opened
if(!isset($_GET['status']) && $_SERVER['REQUEST_METHOD']==='GET') {
    $db->query("UPDATE bookings SET status='reviewed' WHERE status='pending'");
}
$filter=$_GET['status']??'';
$where=$filter?"WHERE status='$filter'":'';
$bookings=$db->query("SELECT b.*,s.name AS service_name FROM bookings b LEFT JOIN services s ON b.service_id=s.id $where ORDER BY b.created_at DESC")->fetchAll();
$counts=['all'=>$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),'pending'=>$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),'reviewed'=>$db->query("SELECT COUNT(*) FROM bookings WHERE status='reviewed'")->fetchColumn(),'converted'=>$db->query("SELECT COUNT(*) FROM bookings WHERE status='converted'")->fetchColumn()];
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bookings — MediaBoost</title>
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
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}.btn-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red)}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;text-decoration:none;color:inherit;transition:border-color .2s,transform .15s;display:block}
.stat-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.stat-value{font-family:var(--font-head);font-size:32px;font-weight:800;line-height:1;margin-bottom:4px}
.stat-label{font-size:12px;color:var(--muted)}
.stat-icon{font-size:22px;margin-bottom:10px}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:800px}
th{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);padding:0 12px 12px 0;text-align:left;border-bottom:1px solid var(--border)}
td{padding:14px 12px 14px 0;border-bottom:1px solid rgba(30,45,69,.5);font-size:13px;vertical-align:middle}
tr:last-child td{border-bottom:none}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-pending{background:rgba(245,158,11,.12);color:var(--amber)}.s-reviewed{background:rgba(59,130,246,.12);color:var(--accent)}.s-converted{background:rgba(16,185,129,.12);color:var(--green)}.s-rejected{background:rgba(239,68,68,.12);color:var(--red)}
.budget-map{font-size:12px;color:var(--muted)}
select.inline-select{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:4px 8px;color:var(--text);font-size:11px;outline:none}
select.inline-select option{background:var(--surface2)}
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
  <div class="page-header"><div><div class="page-title">📅 Bookings</div><div class="page-sub">Service requests from website</div></div></div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <div class="stats-grid">
    <a href="bookings.php" class="stat-card"><div class="stat-icon">📅</div><div class="stat-value"><?php echo $counts['all']; ?></div><div class="stat-label">Total Bookings</div></a>
    <a href="bookings.php?status=pending" class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?php echo $counts['pending']; ?></div><div class="stat-label">Pending</div></a>
    <a href="bookings.php?status=reviewed" class="stat-card"><div class="stat-icon">👀</div><div class="stat-value"><?php echo $counts['reviewed']; ?></div><div class="stat-label">Reviewed</div></a>
    <a href="bookings.php?status=converted" class="stat-card"><div class="stat-icon">✅</div><div class="stat-value"><?php echo $counts['converted']; ?></div><div class="stat-label">Converted</div></a>
  </div>
  <div class="card">
    <table>
      <thead><tr><th>Name / Business</th><th>Contact</th><th>Service</th><th>Budget</th><th>Message</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($bookings)): ?><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px">No bookings found</td></tr><?php endif; ?>
      <?php foreach($bookings as $b): ?>
      <tr>
        <td><div style="font-weight:600"><?php echo htmlspecialchars($b['name']); ?></div><div style="font-size:11px;color:var(--muted)"><?php echo htmlspecialchars($b['business_name']??''); ?></div></td>
        <td><div><?php echo htmlspecialchars($b['phone']??'—'); ?></div><div style="font-size:11px;color:var(--muted)"><?php echo htmlspecialchars($b['email']); ?></div></td>
        <td style="color:var(--muted)"><?php echo htmlspecialchars($b['service_name']??'—'); ?></td>
        <td class="budget-map"><?php echo str_replace(['under_50k','50k_150k','150k_500k','500k_plus'],['<50K','50-150K','150-500K','500K+'],$b['budget_range']??'—'); ?></td>
        <td style="color:var(--muted);max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars(substr($b['message']??'—',0,50)); ?></td>
        <td style="color:var(--muted);font-size:12px"><?php echo date('d M Y',strtotime($b['created_at'])); ?></td>
        <td><span class="status s-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
        <td>
          <form method="POST" style="display:flex;gap:6px;align-items:center">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
            <select class="inline-select" name="status" onchange="this.form.submit()">
              <?php foreach(['pending','reviewed','converted','rejected'] as $s): ?><option value="<?php echo $s; ?>" <?php echo $b['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
</body></html>
