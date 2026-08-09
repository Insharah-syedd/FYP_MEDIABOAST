<?php
error_reporting(E_ERROR|E_PARSE);
require_once '../includes/auth.php';
requireRole(['admin','manager','employee']);
if(isAdmin()){header('Location: ../admin/dashboard.php');exit;}
$db=getDB();$user=currentUser();

$total_leads =$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$new_leads   =$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
$my_leads    =$db->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to=?");$my_leads->execute([$user['id']]);$my_leads=$my_leads->fetchColumn();
$active_proj =$db->query("SELECT COUNT(*) FROM projects WHERE status='in_progress'")->fetchColumn();
$pending_bk  =$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$unread      =$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
$won_leads   =$db->query("SELECT COUNT(*) FROM leads WHERE status='closed_won'")->fetchColumn();

$recentLeads=$db->query("SELECT l.*,u.name AS assigned_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id ORDER BY l.created_at DESC LIMIT 8")->fetchAll();
$recentProjects=$db->query("SELECT p.*,c.business_name FROM projects p LEFT JOIN clients c ON p.client_id=c.id WHERE p.status='in_progress' ORDER BY p.updated_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manager Dashboard — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--coral:#f97316;--text:#f0f4ff;--muted:#6b7a99;--sidebar-w:240px;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
body{background:var(--bg);color:var(--text);font-family:var(--font-body);display:flex;min-height:100vh;font-size:14px}
.sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);position:fixed;top:0;left:0;height:100vh;overflow-y:auto;display:flex;flex-direction:column}
.sidebar-logo{display:flex;align-items:center;gap:10px;padding:24px 20px;border-bottom:1px solid var(--border)}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:16px}
.logo-name{font-family:var(--font-head);font-weight:700;font-size:17px;background:linear-gradient(135deg,#fff 30%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav{padding:16px 12px;flex:1}
.nav-section{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:8px 8px 4px;margin-top:8px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;transition:all .15s;margin-bottom:2px}
.nav-item:hover,.nav-item.active{background:rgba(59,130,246,.1);color:var(--accent)}.nav-item.active{font-weight:600}
.nav-icon{font-size:16px;width:20px;text-align:center}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
.role-tag{margin:12px 20px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:8px 12px;font-size:12px;font-weight:600;color:var(--amber);display:flex;align-items:center;gap:6px}
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--amber),var(--coral));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.user-info{flex:1}.user-name{font-size:13px;font-weight:600}.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none;transition:color .15s}.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800;letter-spacing:-.5px}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 4px 14px rgba(59,130,246,.3)}.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;transition:border-color .2s,transform .15s;cursor:default}
.stat-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.stat-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px}
.stat-icon{font-size:22px}
.stat-badge{font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.badge-blue{background:rgba(59,130,246,.15);color:var(--accent)}.badge-amber{background:rgba(245,158,11,.15);color:var(--amber)}.badge-green{background:rgba(16,185,129,.15);color:var(--green)}.badge-red{background:rgba(239,68,68,.15);color:var(--red)}
.stat-value{font-family:var(--font-head);font-size:32px;font-weight:800;line-height:1;margin-bottom:4px}
.stat-label{font-size:12px;color:var(--muted)}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px}
.card-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between}
.card-link{font-size:12px;color:var(--accent);text-decoration:none;font-family:var(--font-body)}.card-link:hover{text-decoration:underline}
table{width:100%;border-collapse:collapse}
th{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);padding:0 0 12px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:12px 0;border-bottom:1px solid rgba(30,45,69,.5);font-size:13px;vertical-align:middle}
tr:last-child td{border-bottom:none}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-new{background:rgba(59,130,246,.12);color:var(--accent)}.s-contacted{background:rgba(245,158,11,.12);color:var(--amber)}.s-interested{background:rgba(16,185,129,.12);color:var(--green)}.s-negotiation{background:rgba(249,115,22,.12);color:var(--coral)}.s-closed_won{background:rgba(16,185,129,.2);color:var(--green)}.s-closed_lost{background:rgba(239,68,68,.12);color:var(--red)}.s-junk{background:rgba(107,122,153,.15);color:var(--muted)}
.s-in_progress{background:rgba(59,130,246,.12);color:var(--accent)}.s-completed{background:rgba(16,185,129,.12);color:var(--green)}.s-pending{background:rgba(245,158,11,.12);color:var(--amber)}
.progress-bar{height:5px;background:var(--border);border-radius:3px;overflow:hidden;margin-top:6px}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));border-radius:3px}
.inline-select{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:4px 8px;color:var(--text);font-size:11px;outline:none;cursor:pointer}
.inline-select option{background:var(--surface2)}
.notif-banner{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:12px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:13px}
.notif-banner .bell{font-size:20px;animation:bellShake .5s ease infinite alternate}
@keyframes bellShake{from{transform:rotate(-10deg)}to{transform:rotate(10deg)}}
</style></head><body>

<aside class="sidebar">
  <div class="sidebar-logo"><div class="logo-icon">M</div><span class="logo-name">MediaBoost</span></div>
  <div class="role-tag">📊 Manager Panel</div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="../admin/leads.php" class="nav-item"><span class="nav-icon">🎯</span> Leads <?php if($new_leads>0): ?><span class="nav-badge"><?= $new_leads ?></span><?php endif; ?></a>
    <a href="clients.php" class="nav-item"><span class="nav-icon">👥</span> Clients</a>
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
    <a href="../logout.php" class="logout-btn" title="Logout">⏻</a>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <div>
      <div class="page-title">Manager Dashboard</div>
      <div class="page-sub"><?= date('l, d F Y') ?> — Welcome, <?= htmlspecialchars($user['name']) ?>!</div>
    </div>
    <a href="../admin/leads.php" class="btn btn-primary">+ Add New Lead</a>
  </div>

  <?php if($unread>0): ?>
  <div class="notif-banner">
    <span class="bell">🔔</span>
    <span>You have <strong><?= $unread ?> unread notification<?= $unread>1?'s':'' ?></strong> — <a href="notifications.php" style="color:var(--accent)">View all</a></span>
  </div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">🎯</span><span class="stat-badge badge-blue">Total</span></div><div class="stat-value"><?= $total_leads ?></div><div class="stat-label">Total Leads</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">🔥</span><span class="stat-badge badge-amber">New</span></div><div class="stat-value"><?= $new_leads ?></div><div class="stat-label">New Leads</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">✅</span><span class="stat-badge badge-green">Won</span></div><div class="stat-value"><?= $won_leads ?></div><div class="stat-label">Closed Won</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">📁</span><span class="stat-badge badge-blue">Live</span></div><div class="stat-value"><?= $active_proj ?></div><div class="stat-label">Active Projects</div></div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-title">Recent Leads <a href="../admin/leads.php" class="card-link">See all →</a></div>
      <table>
        <thead><tr><th>Name</th><th>Status</th><th>Change</th></tr></thead>
        <tbody>
        <?php foreach($recentLeads as $lead): ?>
        <tr>
          <td><div style="font-weight:600"><?= htmlspecialchars($lead['name']) ?></div><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($lead['business_name']??'—') ?></div></td>
          <td><span class="status s-<?= $lead['status'] ?>"><?= ucfirst(str_replace('_',' ',$lead['status'])) ?></span></td>
          <td>
            <form method="POST" action="../admin/leads.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="status_change">
              <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
              <select class="inline-select" name="status" onchange="this.form.submit()">
                <?php foreach(['new','contacted','interested','negotiation','closed_won','closed_lost','junk'] as $s): ?>
                <option value="<?= $s ?>" <?= $lead['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($recentLeads)): ?><tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px">No leads yet</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-title">Active Projects <a href="projects.php" class="card-link">See all →</a></div>
      <table>
        <thead><tr><th>Project</th><th>Client</th><th>Progress</th></tr></thead>
        <tbody>
        <?php foreach($recentProjects as $p): ?>
        <tr>
          <td style="font-weight:600;font-size:12px;max-width:130px"><?= htmlspecialchars($p['title']) ?></td>
          <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($p['business_name']??'—') ?></td>
          <td style="min-width:80px">
            <div style="font-size:11px;font-weight:600;margin-bottom:4px"><?= $p['progress'] ?>%</div>
            <div class="progress-bar"><div class="progress-fill" style="width:<?= $p['progress'] ?>%"></div></div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($recentProjects)): ?><tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px">No active projects</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body></html>
