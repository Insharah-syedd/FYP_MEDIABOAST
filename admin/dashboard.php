<?php
require_once '../includes/auth.php';
requireRole(['admin']);
$db=getDB();$user=currentUser();
$stats['total_clients']  =$db->query("SELECT COUNT(*) FROM clients WHERE is_active=1")->fetchColumn();
$stats['total_leads']    =$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$stats['new_leads']      =$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
$stats['active_projects']=$db->query("SELECT COUNT(*) FROM projects WHERE status='in_progress'")->fetchColumn();
$stats['bookings']       =$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$recentLeads=$db->query("SELECT l.*,u.name AS assigned_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id ORDER BY l.created_at DESC LIMIT 6")->fetchAll();
$leadStatuses=$db->query("SELECT status,COUNT(*) as cnt FROM leads GROUP BY status")->fetchAll();
$monthlyLeads=$db->query("SELECT DATE_FORMAT(created_at,'%b') AS month,COUNT(*) AS cnt FROM leads WHERE created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY MONTH(created_at),DATE_FORMAT(created_at,'%b') ORDER BY MONTH(created_at)")->fetchAll();
$recentClients=$db->query("SELECT c.*,u.name AS manager_name FROM clients c LEFT JOIN users u ON c.assigned_to=u.id ORDER BY c.created_at DESC LIMIT 5")->fetchAll();
$notifCount=$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--coral:#f97316;--text:#f0f4ff;--muted:#6b7a99;--sidebar-w:240px;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
body{background:var(--bg);color:var(--text);font-family:var(--font-body);display:flex;min-height:100vh;font-size:14px}
.sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;overflow-y:auto}
.sidebar-logo{display:flex;align-items:center;gap:10px;padding:24px 20px;border-bottom:1px solid var(--border)}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-weight:800;color:#fff;font-size:16px}
.logo-name{font-family:var(--font-head);font-weight:700;font-size:17px;background:linear-gradient(135deg,#fff 30%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav{padding:16px 12px;flex:1}
.nav-section{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:8px 8px 4px;margin-top:8px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;transition:all .15s;margin-bottom:2px}
.nav-item:hover,.nav-item.active{background:rgba(59,130,246,.1);color:var(--accent)}.nav-item.active{font-weight:600}
.nav-icon{font-size:16px;width:20px;text-align:center}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px}
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0}
.user-info{flex:1;overflow:hidden}.user-name{font-size:13px;font-weight:600}.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px;min-width:0}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800;letter-spacing:-.5px}
.page-date{font-size:13px;color:var(--muted);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 4px 14px rgba(59,130,246,.3)}.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}
.header-actions{display:flex;gap:10px}
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;transition:border-color .2s,transform .15s;cursor:default}
.stat-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.stat-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px}
.stat-icon{font-size:22px}
.stat-badge{font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.badge-green{background:rgba(16,185,129,.15);color:var(--green)}.badge-amber{background:rgba(245,158,11,.15);color:var(--amber)}.badge-red{background:rgba(239,68,68,.15);color:var(--red)}.badge-blue{background:rgba(59,130,246,.15);color:var(--accent)}
.stat-value{font-family:var(--font-head);font-size:32px;font-weight:800;line-height:1;margin-bottom:4px}
.stat-label{font-size:12px;color:var(--muted);font-weight:500}
.grid-3{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px}
.card-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between}
.card-link{font-size:12px;color:var(--accent);text-decoration:none;font-family:var(--font-body);font-weight:500}.card-link:hover{text-decoration:underline}
table{width:100%;border-collapse:collapse}
th{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);padding:0 0 12px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:13px 0;border-bottom:1px solid rgba(30,45,69,.5);font-size:13px;vertical-align:middle}
tr:last-child td{border-bottom:none}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-new{background:rgba(59,130,246,.12);color:var(--accent)}.s-contacted{background:rgba(245,158,11,.12);color:var(--amber)}.s-interested{background:rgba(16,185,129,.12);color:var(--green)}.s-negotiation{background:rgba(249,115,22,.12);color:var(--coral)}.s-closed_won{background:rgba(16,185,129,.2);color:var(--green)}.s-closed_lost{background:rgba(239,68,68,.12);color:var(--red)}.s-junk{background:rgba(107,122,153,.15);color:var(--muted)}
.score-wrap{display:flex;align-items:center;gap:8px}.score-bar{flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden}.score-fill{height:100%;border-radius:2px}.score-num{font-size:11px;font-weight:600;min-width:24px;text-align:right}
.chart-wrap{position:relative;height:200px}
.donut-wrap{display:flex;align-items:center;gap:24px}.donut-canvas{width:160px!important;height:160px!important;flex-shrink:0}
.legend{list-style:none;padding:0;display:flex;flex-direction:column;gap:10px;justify-content:center}
.legend li{display:flex;align-items:center;gap:8px;font-size:12px}
.legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.legend-label{flex:1;color:var(--muted);text-transform:capitalize}
.legend-val{font-weight:600;color:var(--text)}
</style>
</head><body>
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
    <div>
      <div class="page-title">Admin Dashboard</div>
      <div class="page-date"><?= date('l, d F Y') ?> — <?= htmlspecialchars($user['name']) ?></div>
    </div>
    <div class="header-actions">
      <a href="leads.php?action=add" class="btn btn-primary">+ New Lead</a>
      <a href="clients.php?action=add" class="btn btn-ghost">+ Add Client</a>
    </div>
  </div>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">👥</span><span class="stat-badge badge-green">Active</span></div><div class="stat-value"><?= $stats['total_clients'] ?></div><div class="stat-label">Total Clients</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">🎯</span><span class="stat-badge badge-blue">Total</span></div><div class="stat-value"><?= $stats['total_leads'] ?></div><div class="stat-label">All Leads</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">🔥</span><span class="stat-badge badge-amber">New</span></div><div class="stat-value"><?= $stats['new_leads'] ?></div><div class="stat-label">New Leads</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">📁</span><span class="stat-badge badge-green">Live</span></div><div class="stat-value"><?= $stats['active_projects'] ?></div><div class="stat-label">Active Projects</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-icon">📅</span><span class="stat-badge badge-red">Pending</span></div><div class="stat-value"><?= $stats['bookings'] ?></div><div class="stat-label">New Bookings</div></div>
  </div>
  <div class="grid-3">
    <div class="card"><div class="card-title">Lead Growth (Last 6 Months) <a href="leads.php" class="card-link">View all</a></div><div class="chart-wrap"><canvas id="lineChart"></canvas></div></div>
    <div class="card"><div class="card-title">Lead Status Breakdown</div><div class="donut-wrap"><canvas id="donutChart" class="donut-canvas"></canvas><ul class="legend" id="donutLegend"></ul></div></div>
  </div>
  <div class="grid-2">
    <div class="card">
      <div class="card-title">Recent Leads <a href="leads.php" class="card-link">See all →</a></div>
      <table><thead><tr><th>Name / Business</th><th>Score</th><th>Status</th><th>Source</th></tr></thead>
      <tbody>
      <?php foreach($recentLeads as $lead): ?>
        <?php $score=$lead['ai_score'];$sc=$score>=75?'#10b981':($score>=50?'#f59e0b':'#ef4444'); ?>
        <tr>
          <td><div style="font-weight:600"><?= htmlspecialchars($lead['name']) ?></div><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($lead['business_name']??'—') ?></div></td>
          <td><div class="score-wrap"><div class="score-bar"><div class="score-fill" style="width:<?= $score ?>%;background:<?= $sc ?>"></div></div><span class="score-num" style="color:<?= $sc ?>"><?= $score ?></span></div></td>
          <td><span class="status s-<?= $lead['status'] ?>"><?= ucfirst(str_replace('_',' ',$lead['status'])) ?></span></td>
          <td style="color:var(--muted);text-transform:capitalize"><?= $lead['source'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <div class="card">
      <div class="card-title">Recent Clients <a href="clients.php" class="card-link">See all →</a></div>
      <table><thead><tr><th>Business</th><th>Package</th><th>Manager</th></tr></thead>
      <tbody>
      <?php foreach($recentClients as $c): ?>
        <tr>
          <td><div style="font-weight:600"><?= htmlspecialchars($c['business_name']) ?></div><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($c['city']??'—') ?></div></td>
          <td><span class="status <?= $c['package']==='premium'?'s-interested':($c['package']==='standard'?'s-contacted':'s-new') ?>"><?= ucfirst($c['package']) ?></span></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($c['manager_name']??'Unassigned') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</main>
<script>
Chart.defaults.color='#6b7a99';Chart.defaults.font.family="'DM Sans',sans-serif";
const lineData=<?= json_encode(array_values($monthlyLeads)) ?>;
new Chart(document.getElementById('lineChart'),{type:'line',data:{labels:lineData.length?lineData.map(d=>d.month):['Apr','May','Jun','Jul','Aug','Sep'],datasets:[{label:'Leads',data:lineData.length?lineData.map(d=>d.cnt):[2,5,3,7,4,8],borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.08)',tension:.4,fill:true,pointBackgroundColor:'#3b82f6',pointRadius:4,borderWidth:2.5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'#1e2d45'},border:{display:false}},y:{grid:{color:'#1e2d45'},border:{display:false},beginAtZero:true,ticks:{precision:0}}}}});
const donutRaw=<?= json_encode(array_values($leadStatuses)) ?>;
const colors={new:'#3b82f6',contacted:'#f59e0b',interested:'#10b981',negotiation:'#f97316',closed_won:'#6ee7b7',closed_lost:'#ef4444',junk:'#6b7a99'};
const labels=donutRaw.map(d=>d.status),values=donutRaw.map(d=>parseInt(d.cnt)),bgs=labels.map(l=>colors[l]||'#6b7a99');
new Chart(document.getElementById('donutChart'),{type:'doughnut',data:{labels,datasets:[{data:values,backgroundColor:bgs,borderWidth:0,hoverOffset:4}]},options:{responsive:false,cutout:'70%',plugins:{legend:{display:false}}}});
const legend=document.getElementById('donutLegend');
labels.forEach((lbl,i)=>{const li=document.createElement('li');li.innerHTML=`<span class="legend-dot" style="background:${bgs[i]}"></span><span class="legend-label">${lbl.replace('_',' ')}</span><span class="legend-val">${values[i]}</span>`;legend.appendChild(li);});
</script>
</body></html>
