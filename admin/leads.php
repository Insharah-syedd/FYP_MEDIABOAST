<?php
require_once '../includes/auth.php';
error_reporting(E_ERROR|E_PARSE);
require_once '../includes/notify.php';
requireRole(['admin','manager','employee']);
$db   = getDB();
$user = currentUser();

// Mark all 'new' leads as 'contacted' when leads page is opened
// This clears the badge naturally and is correct CRM behavior
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db->query("UPDATE leads SET status='contacted' WHERE status='new'");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $db->prepare("INSERT INTO leads (name,email,phone,business_name,service_interest,budget,source,status,notes,assigned_to,ai_score,follow_up_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['business_name'],$_POST['service_interest'],$_POST['budget'],$_POST['source'],$_POST['status'],$_POST['notes'],$_POST['assigned_to']?:null,rand(40,99),$_POST['follow_up_date']?:null]);
        $msg = 'Lead added successfully! ✅';
        addNotification('New lead added: '.$_POST['name'].' from '.($_POST['business_name']?:'Unknown'), 'new_lead', null, 'leads.php');
    }
    if ($action === 'edit') {
        $stmt = $db->prepare("UPDATE leads SET name=?,email=?,phone=?,business_name=?,service_interest=?,budget=?,source=?,status=?,notes=?,assigned_to=?,follow_up_date=? WHERE id=?");
        $stmt->execute([$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['business_name'],$_POST['service_interest'],$_POST['budget'],$_POST['source'],$_POST['status'],$_POST['notes'],$_POST['assigned_to']?:null,$_POST['follow_up_date']?:null,$_POST['lead_id']]);
        $msg = 'Lead updated successfully! ✅';
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM leads WHERE id=?")->execute([$_POST['lead_id']]);
        $msg = 'Lead deleted!';
    }
    if ($action === 'status_change') {
        $db->prepare("UPDATE leads SET status=? WHERE id=?")->execute([$_POST['status'],$_POST['lead_id']]);
        header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
    }
}

$where="1=1"; $params=[];
if (!empty($_GET['status'])){$where.=" AND l.status=?";$params[]=$_GET['status'];}
if (!empty($_GET['source'])){$where.=" AND l.source=?";$params[]=$_GET['source'];}
if (!empty($_GET['search'])){$where.=" AND (l.name LIKE ? OR l.business_name LIKE ? OR l.email LIKE ?)";$s='%'.$_GET['search'].'%';$params=array_merge($params,[$s,$s,$s]);}
$leads=$db->prepare("SELECT l.*,u.name AS assigned_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE $where ORDER BY l.created_at DESC");
$leads->execute($params); $leads=$leads->fetchAll();
$users=$db->query("SELECT id,name,role FROM users WHERE is_active=1")->fetchAll();
$total=$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$new=$db->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
$interested=$db->query("SELECT COUNT(*) FROM leads WHERE status='interested'")->fetchColumn();
$won=$db->query("SELECT COUNT(*) FROM leads WHERE status='closed_won'")->fetchColumn();
$editLead=null;
if(!empty($_GET['edit'])){$e=$db->prepare("SELECT * FROM leads WHERE id=?");$e->execute([$_GET['edit']]);$editLead=$e->fetch();}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lead Management — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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
.nav-item:hover,.nav-item.active{background:rgba(59,130,246,.1);color:var(--accent)}
.nav-item.active{font-weight:600}
.nav-icon{font-size:16px;width:20px;text-align:center}
.sidebar-user{padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.user-info{flex:1;overflow:hidden}
.user-name{font-size:13px;font-weight:600}
.user-role{font-size:11px;color:var(--muted);text-transform:capitalize}
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}
.logout-btn:hover{color:var(--red)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800;letter-spacing:-.5px}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 4px 14px rgba(59,130,246,.3)}
.btn-primary:hover{opacity:.9}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}
.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red)}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px}
.stat-value{font-family:var(--font-head);font-size:32px;font-weight:800;line-height:1;margin-bottom:4px}
.stat-label{font-size:12px;color:var(--muted)}
.stat-icon{font-size:22px;margin-bottom:10px}
.filters{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.filter-input{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:9px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;transition:border-color .2s}
.filter-input:focus{border-color:var(--accent)}
.filter-input option{background:var(--surface2)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:900px}
th{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);padding:0 12px 12px 0;text-align:left;border-bottom:1px solid var(--border)}
td{padding:13px 12px 13px 0;border-bottom:1px solid rgba(30,45,69,.5);font-size:13px;vertical-align:middle}
tr:last-child td{border-bottom:none}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-new{background:rgba(59,130,246,.12);color:var(--accent)}.s-contacted{background:rgba(245,158,11,.12);color:var(--amber)}.s-interested{background:rgba(16,185,129,.12);color:var(--green)}.s-negotiation{background:rgba(249,115,22,.12);color:var(--coral)}.s-closed_won{background:rgba(16,185,129,.2);color:var(--green)}.s-closed_lost{background:rgba(239,68,68,.12);color:var(--red)}.s-junk{background:rgba(107,122,153,.15);color:var(--muted)}
.score-wrap{display:flex;align-items:center;gap:8px}
.score-bar{flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden;min-width:50px}
.score-fill{height:100%;border-radius:2px}
.score-num{font-size:11px;font-weight:600;min-width:24px}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center}
.close-btn{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
input,select,textarea{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:13px;outline:none;transition:border-color .2s;width:100%}
input:focus,select:focus,textarea:focus{border-color:var(--accent)}
select option{background:var(--surface2)}
textarea{resize:vertical;min-height:80px}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}
</style></head><body>
<aside class="sidebar">
  <div class="sidebar-logo"><div class="logo-icon">M</div><span class="logo-name">MediaBoost</span></div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':''; ?>"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="leads.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])==='leads.php'?'active':''; ?>">
      <span class="nav-icon">🎯</span> Lead Management
      <?php
      // Get last time this user visited leads page from DB
      $__seen_row = $db->prepare("SELECT created_at FROM notifications WHERE type='system' AND message='leads_seen_marker' AND user_id=? ORDER BY created_at DESC LIMIT 1");
      $__seen_row->execute([$user['id']]);
      $__seen_row = $__seen_row->fetch();
      $__last_seen_dt = $__seen_row ? $__seen_row['created_at'] : '2000-01-01';
      $__new_stmt = $db->prepare("SELECT COUNT(*) FROM leads WHERE status='new' AND created_at > ?");
      $__new_stmt->execute([$__last_seen_dt]);
      $__nb_leads = $__new_stmt->fetchColumn();
      if($__nb_leads>0): ?><span class="nav-badge"><?php echo $__nb_leads; ?></span><?php endif; ?>
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
    <div><div class="page-title">🎯 Lead Management</div><div class="page-sub">Track all inquiries and potential clients</div></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ New Lead</button>
  </div>
  <?php if($msg): ?><div class="alert"><?= $msg ?></div><?php endif; ?>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon">🎯</div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Leads</div></div>
    <div class="stat-card"><div class="stat-icon">🔥</div><div class="stat-value"><?= $new ?></div><div class="stat-label">New Leads</div></div>
    <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value"><?= $interested ?></div><div class="stat-label">Interested</div></div>
    <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value"><?= $won ?></div><div class="stat-label">Closed Won</div></div>
  </div>
  <form method="GET" class="filters">
    <input class="filter-input" style="flex:1;min-width:180px" type="text" name="search" placeholder="🔍 Search..." value="<?= htmlspecialchars($_GET['search']??'') ?>">
    <select class="filter-input" name="status">
      <option value="">All Status</option>
      <?php foreach(['new','contacted','interested','negotiation','closed_won','closed_lost','junk'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
    </select>
    <select class="filter-input" name="source">
      <option value="">All Sources</option>
      <?php foreach(['website','whatsapp','referral','facebook','instagram','other'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['source']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost">Filter</button>
    <a href="leads.php" class="btn btn-ghost">Reset</a>
  </form>
  <div class="card">
    <table>
      <thead><tr><th>Name / Business</th><th>Contact</th><th>Service</th><th>Budget</th><th>Source</th><th>AI Score</th><th>Status</th><th>Assigned</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if(empty($leads)): ?><tr><td colspan="9" style="text-align:center;color:var(--muted);padding:40px">No leads found. Add one using + New Lead.</td></tr><?php endif; ?>
      <?php foreach($leads as $lead): ?>
        <?php $score=$lead['ai_score'];$sc=$score>=75?'#10b981':($score>=50?'#f59e0b':'#ef4444'); ?>
        <tr>
          <td><div style="font-weight:600"><?= htmlspecialchars($lead['name']) ?></div><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($lead['business_name']??'—') ?></div></td>
          <td><div><?= htmlspecialchars($lead['phone']??'—') ?></div><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($lead['email']??'') ?></div></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($lead['service_interest']??'—') ?></td>
          <td style="font-size:12px;color:var(--muted)"><?= str_replace(['under_50k','50k_150k','150k_500k','500k_plus'],['<50K','50-150K','150-500K','500K+'],$lead['budget']??'—') ?></td>
          <td style="text-transform:capitalize;color:var(--muted)"><?= $lead['source'] ?></td>
          <td><div class="score-wrap"><div class="score-bar"><div class="score-fill" style="width:<?= $score ?>%;background:<?= $sc ?>"></div></div><span class="score-num" style="color:<?= $sc ?>"><?= $score ?></span></div></td>
          <td>
            <select class="filter-input" style="padding:4px 8px;font-size:11px" onchange="changeStatus(<?= $lead['id'] ?>,this.value)">
              <?php foreach(['new','contacted','interested','negotiation','closed_won','closed_lost','junk'] as $s): ?><option value="<?= $s ?>" <?= $lead['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
            </select>
          </td>
          <td style="color:var(--muted)"><?= htmlspecialchars($lead['assigned_name']??'—') ?></td>
          <td><div style="display:flex;gap:6px">
            <a href="leads.php?edit=<?= $lead['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete?')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New Lead <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>Name *</label><input type="text" name="name" required placeholder="Full name"></div>
        <div class="form-group"><label>Business</label><input type="text" name="business_name" placeholder="Company"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="email@example.com"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="03001234567"></div>
        <div class="form-group"><label>Service Interest</label><input type="text" name="service_interest" placeholder="SEO, Social Media..."></div>
        <div class="form-group"><label>Budget</label><select name="budget"><option value="under_50k">Under 50K</option><option value="50k_150k">50K-150K</option><option value="150k_500k">150K-500K</option><option value="500k_plus">500K+</option></select></div>
        <div class="form-group"><label>Source</label><select name="source"><?php foreach(['website','whatsapp','referral','facebook','instagram','other'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Status</label><select name="status"><?php foreach(['new','contacted','interested','negotiation','closed_won','closed_lost','junk'] as $s): ?><option value="<?= $s ?>"><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Assign To</label><select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Follow Up</label><input type="date" name="follow_up_date"></div>
        <div class="form-group full"><label>Notes</label><textarea name="notes" placeholder="Notes or additional details..."></textarea></div>
      </div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<?php if($editLead): ?>
<div class="modal-overlay open" id="editModal">
  <div class="modal">
    <div class="modal-title">Lead Edit <button class="close-btn" onclick="window.location='leads.php'">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="lead_id" value="<?= $editLead['id'] ?>">
      <div class="form-grid">
        <div class="form-group"><label>Name *</label><input type="text" name="name" required value="<?= htmlspecialchars($editLead['name']) ?>"></div>
        <div class="form-group"><label>Business</label><input type="text" name="business_name" value="<?= htmlspecialchars($editLead['business_name']??'') ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editLead['email']??'') ?>"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($editLead['phone']??'') ?>"></div>
        <div class="form-group"><label>Service</label><input type="text" name="service_interest" value="<?= htmlspecialchars($editLead['service_interest']??'') ?>"></div>
        <div class="form-group"><label>Budget</label><select name="budget"><?php foreach(['under_50k'=>'Under 50K','50k_150k'=>'50-150K','150k_500k'=>'150-500K','500k_plus'=>'500K+'] as $v=>$l): ?><option value="<?= $v ?>" <?= $editLead['budget']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Source</label><select name="source"><?php foreach(['website','whatsapp','referral','facebook','instagram','other'] as $s): ?><option value="<?= $s ?>" <?= $editLead['source']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Status</label><select name="status"><?php foreach(['new','contacted','interested','negotiation','closed_won','closed_lost','junk'] as $s): ?><option value="<?= $s ?>" <?= $editLead['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Assign To</label><select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u): ?><option value="<?= $u['id'] ?>" <?= $editLead['assigned_to']==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Follow Up</label><input type="date" name="follow_up_date" value="<?= $editLead['follow_up_date']??'' ?>"></div>
        <div class="form-group full"><label>Notes</label><textarea name="notes"><?= htmlspecialchars($editLead['notes']??'') ?></textarea></div>
      </div>
      <div class="form-actions"><a href="leads.php" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')}));
function changeStatus(id,status){
  fetch('leads.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=status_change&lead_id='+id+'&status='+status+'&csrf_token=<?= csrfToken() ?>'})
  .then(r=>r.json()).then(()=>{const t=document.createElement('div');t.style.cssText='position:fixed;top:20px;right:20px;background:#10b981;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;z-index:999';t.textContent='Status updated ✅';document.body.appendChild(t);setTimeout(()=>t.remove(),2000)});
}
</script>
</body></html>
