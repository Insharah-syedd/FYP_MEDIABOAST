<?php
error_reporting(E_ERROR | E_PARSE);
require_once '../includes/auth.php';
requireRole(['admin','manager','employee']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$action=$_POST['action']??'';
  if($action==='add'){
    $rm = $_POST['report_month'] ?? '';
    // input[type=month] returns YYYY-MM format, convert to YYYY-MM-01
    if(strlen($rm) === 7) { $report_month = $rm . '-01'; }
    elseif(strlen($rm) === 10) { $report_month = $rm; }
    else { $report_month = date('Y-m-01'); }
    $db->prepare("INSERT INTO reports (client_id,project_id,report_month,seo_clicks,seo_impressions,seo_position,fb_reach,fb_engagement,ig_reach,ig_engagement,website_visits,leads_generated,summary,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
      $_POST['client_id'],
      $_POST['project_id']?:null,
      $report_month,
      $_POST['seo_clicks']??0,
      $_POST['seo_impressions']??0,
      $_POST['seo_position']??0,
      $_POST['fb_reach']??0,
      $_POST['fb_engagement']??0,
      $_POST['ig_reach']??0,
      $_POST['ig_engagement']??0,
      $_POST['website_visits']??0,
      $_POST['leads_generated']??0,
      $_POST['summary']??'',
      $user['id']
    ]);
    $msg='Report added successfully! ✅';
  }
  if($action==='delete'){$db->prepare("DELETE FROM reports WHERE id=?")->execute([$_POST['report_id']]);$msg='Report deleted!';}
}
$reports=$db->query("SELECT r.*,c.business_name FROM reports r LEFT JOIN clients c ON r.client_id=c.id ORDER BY r.report_month DESC")->fetchAll();
$clients=$db->query("SELECT id,business_name FROM clients WHERE is_active=1")->fetchAll();
$projects=$db->query("SELECT id,title,client_id FROM projects")->fetchAll();
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reports — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
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
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}.btn-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red)}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px}
.card-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:16px}
.chart-wrap{height:200px;position:relative}
.reports-list{display:flex;flex-direction:column;gap:14px;margin-top:24px}
.report-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px}
.report-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.report-month{font-family:var(--font-head);font-size:16px;font-weight:700}
.report-client{font-size:12px;color:var(--muted)}
.stats-row{display:grid;grid-template-columns:repeat(6,1fr);gap:12px}
.stat-box{background:var(--surface2);border-radius:10px;padding:12px;text-align:center}
.stat-val{font-family:var(--font-head);font-size:20px;font-weight:800;color:var(--accent)}
.stat-lbl{font-size:10px;color:var(--muted);margin-top:2px}
.summary-text{font-size:12px;color:var(--muted);margin-top:12px;padding-top:12px;border-top:1px solid var(--border)}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center}
.close-btn{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{display:flex;flex-direction:column;gap:6px}.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
input,select,textarea{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;width:100%}
input:focus,select:focus,textarea:focus{border-color:var(--accent)}
select option{background:var(--surface2)}
textarea{resize:vertical;min-height:70px}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}
.empty{text-align:center;padding:40px;color:var(--muted)}
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
  <div class="page-header"><div class="page-title">📈 Analytics Reports</div><button class="btn btn-primary" onclick="openModal('addModal')">+ Add Report</button></div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <?php if(!empty($reports)): ?>
  <div class="chart-grid">
    <div class="card"><div class="card-title">SEO Clicks (Last 6 months)</div><div class="chart-wrap"><canvas id="seoChart"></canvas></div></div>
    <div class="card"><div class="card-title">Website Visits</div><div class="chart-wrap"><canvas id="visitsChart"></canvas></div></div>
  </div>
  <?php endif; ?>
  <div class="reports-list">
    <?php if(empty($reports)): ?><div class="empty"><div style="font-size:48px;margin-bottom:16px">📊</div><div style="font-size:16px;font-weight:600;margin-bottom:8px">No reports found</div><div>+ Add Report se Add your first report</div></div><?php endif; ?>
    <?php foreach($reports as $r): ?>
    <div class="report-card">
      <div class="report-head">
        <div><div class="report-month"><?php $rm = $r['report_month'];
          if(strlen($rm) === 7) $rm .= '-01';
          echo date('F Y', strtotime($rm)); ?></div><div class="report-client">👥 <?php echo htmlspecialchars($r['business_name']??''); ?></div></div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete?')">
          <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
          <button class="btn btn-danger btn-sm">🗑 Delete</button>
        </form>
      </div>
      <div class="stats-row">
        <div class="stat-box"><div class="stat-val"><?php echo number_format($r['seo_clicks']); ?></div><div class="stat-lbl">SEO Clicks</div></div>
        <div class="stat-box"><div class="stat-val"><?php echo number_format($r['seo_impressions']); ?></div><div class="stat-lbl">Impressions</div></div>
        <div class="stat-box"><div class="stat-val"><?php echo number_format($r['website_visits']); ?></div><div class="stat-lbl">Visits</div></div>
        <div class="stat-box"><div class="stat-val"><?php echo number_format($r['fb_reach']); ?></div><div class="stat-lbl">FB Reach</div></div>
        <div class="stat-box"><div class="stat-val"><?php echo number_format($r['ig_reach']); ?></div><div class="stat-lbl">IG Reach</div></div>
        <div class="stat-box"><div class="stat-val"><?php echo $r['leads_generated']; ?></div><div class="stat-lbl">Leads</div></div>
      </div>
      <?php if($r['summary']): ?><div class="summary-text">📝 <?php echo htmlspecialchars($r['summary']); ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</main>
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New Report <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>Client *</label><select name="client_id" required><option value="">Select</option><?php foreach($clients as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['business_name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Report Month *</label><input type="month" name="report_month" required></div>
        <div class="form-group"><label>SEO Clicks</label><input type="number" name="seo_clicks" value="0"></div>
        <div class="form-group"><label>Impressions</label><input type="number" name="seo_impressions" value="0"></div>
        <div class="form-group"><label>Website Visits</label><input type="number" name="website_visits" value="0"></div>
        <div class="form-group"><label>Leads Generated</label><input type="number" name="leads_generated" value="0"></div>
        <div class="form-group"><label>FB Reach</label><input type="number" name="fb_reach" value="0"></div>
        <div class="form-group"><label>FB Engagement</label><input type="number" name="fb_engagement" value="0"></div>
        <div class="form-group"><label>IG Reach</label><input type="number" name="ig_reach" value="0"></div>
        <div class="form-group"><label>IG Engagement</label><input type="number" name="ig_engagement" value="0"></div>
        <div class="form-group full"><label>Summary</label><textarea name="summary" placeholder="What was achieved this month..."></textarea></div>
      </div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<script>
Chart.defaults.color='#6b7a99';Chart.defaults.font.family="'DM Sans',sans-serif";
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
<?php if(!empty($reports)): ?>
const rData=<?php echo json_encode(array_slice(array_reverse($reports),0,6)); ?>;
const labels=rData.map(r=>{const d=r.report_month?r.report_month.slice(0,7):'';if(!d)return'N/A';const p=d.split('-');return new Date(parseInt(p[0]),parseInt(p[1])-1,1).toLocaleString('default',{month:'short',year:'2-digit'});});
new Chart(document.getElementById('seoChart'),{type:'bar',data:{labels,datasets:[{label:'Clicks',data:rData.map(r=>r.seo_clicks),backgroundColor:'rgba(59,130,246,.6)',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'#1e2d45'},border:{display:false}},y:{grid:{color:'#1e2d45'},border:{display:false},beginAtZero:true}}}});
new Chart(document.getElementById('visitsChart'),{type:'line',data:{labels,datasets:[{label:'Visits',data:rData.map(r=>r.website_visits),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.08)',fill:true,tension:.4,borderWidth:2.5,pointBackgroundColor:'#10b981',pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'#1e2d45'},border:{display:false}},y:{grid:{color:'#1e2d45'},border:{display:false},beginAtZero:true}}}});
<?php endif; ?>
</script>
</body></html>
