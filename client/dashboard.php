<?php
require_once '../includes/auth.php';
requireRole(['client']);
$db=getDB();$user=currentUser();
$client=$db->prepare("SELECT * FROM clients WHERE id=?");$client->execute([$user['id']]);$client=$client->fetch();
$projects=$db->prepare("SELECT * FROM projects WHERE client_id=? ORDER BY created_at DESC");$projects->execute([$user['id']]);$projects=$projects->fetchAll();
$reports=$db->prepare("SELECT * FROM reports WHERE client_id=? ORDER BY report_month DESC LIMIT 6");$reports->execute([$user['id']]);$reports=$reports->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Client Portal — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--text:#f0f4ff;--muted:#6b7a99;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
body{background:var(--bg);color:var(--text);font-family:var(--font-body);min-height:100vh}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10}
.logo-wrap{display:flex;align-items:center;gap:10px}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:16px}
.logo-name{font-family:var(--font-head);font-weight:700;font-size:17px;background:linear-gradient(135deg,#fff 30%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.topbar-right{display:flex;align-items:center;gap:16px}
.user-info{text-align:right}
.user-name-top{font-size:14px;font-weight:600}
.user-role-top{font-size:11px;color:var(--muted)}
.user-avatar{width:38px;height:38px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:15px}
.logout-btn{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:var(--red);padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s}
.logout-btn:hover{background:rgba(239,68,68,.2)}
.main{padding:32px;max-width:1100px;margin:0 auto}
.welcome-card{background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:20px;padding:32px;margin-bottom:32px;display:flex;align-items:center;justify-content:space-between}
.welcome-title{font-family:var(--font-head);font-size:24px;font-weight:800;color:#fff;margin-bottom:6px}
.welcome-sub{color:rgba(255,255,255,.75);font-size:14px}
.pkg-badge{background:rgba(255,255,255,.2);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:20px;display:inline-block;margin-top:12px}
.welcome-icon{font-size:64px;opacity:.8}
.section-title{font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:16px;margin-top:32px;display:flex;align-items:center;gap:10px}
.projects-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.project-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;transition:border-color .2s,transform .15s}
.project-card:hover{border-color:rgba(59,130,246,.3);transform:translateY(-2px)}
.project-service{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;background:rgba(59,130,246,.1);color:var(--accent)}
.project-name{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:12px}
.progress-label{display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px}
.progress-label span:last-child{font-weight:700;color:var(--accent)}
.progress-bar{height:8px;background:var(--border);border-radius:4px;overflow:hidden;margin-bottom:14px}
.progress-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .6s ease}
.project-footer{display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid var(--border)}
.status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.s-in_progress{background:rgba(59,130,246,.12);color:var(--accent)}.s-completed{background:rgba(16,185,129,.12);color:var(--green)}.s-pending{background:rgba(245,158,11,.12);color:var(--amber)}.s-paused{background:rgba(107,122,153,.15);color:var(--muted)}.s-review{background:rgba(168,85,247,.12);color:#a855f7}
.deadline{font-size:11px;color:var(--muted)}
.no-data{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:48px;text-align:center}
.no-data-icon{font-size:48px;margin-bottom:14px}
.no-data-title{font-family:var(--font-head);font-size:16px;font-weight:700;margin-bottom:6px}
.no-data-sub{font-size:13px;color:var(--muted)}
.reports-grid{display:flex;flex-direction:column;gap:14px}
.report-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px}
.report-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.report-month{font-family:var(--font-head);font-size:18px;font-weight:700}
.report-client-tag{font-size:12px;color:var(--muted);margin-top:4px}
.report-stats{display:grid;grid-template-columns:repeat(6,1fr);gap:12px}
.stat-box{background:var(--surface2);border-radius:10px;padding:14px;text-align:center}
.stat-val{font-family:var(--font-head);font-size:22px;font-weight:800;color:var(--accent)}
.stat-lbl{font-size:10px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.05em}
.report-summary{font-size:13px;color:var(--muted);margin-top:16px;padding-top:16px;border-top:1px solid var(--border);line-height:1.6}
</style></head><body>
<div class="topbar">
  <div class="logo-wrap"><div class="logo-icon">M</div><span class="logo-name">MediaBoost</span></div>
  <div class="topbar-right">
    <div class="user-info">
      <div class="user-name-top"><?= htmlspecialchars($user['name']) ?></div>
      <div class="user-role-top">Client Portal</div>
    </div>
    <div class="user-avatar"><?= strtoupper($user['name'][0]) ?></div>
    <a href="../logout.php" class="logout-btn">⏻ Logout</a>
  </div>
</div>

<div class="main">
  <!-- Welcome Card -->
  <div class="welcome-card">
    <div>
      <div class="welcome-title">Welcome, <?= htmlspecialchars($client['business_name']??'') ?>! 👋</div>
      <div class="welcome-sub">Track your campaigns, projects and reports all in one place.</div>
      <span class="pkg-badge">⭐ <?= ucfirst($client['package']??'basic') ?> Package</span>
    </div>
    <div class="welcome-icon">🚀</div>
  </div>

  <!-- Projects -->
  <div class="section-title">📁 Your Projects</div>
  <?php if(empty($projects)): ?>
  <div class="no-data"><div class="no-data-icon">📁</div><div class="no-data-title">No Projects Yet</div><div class="no-data-sub">Your agency will assign projects soon. Please contact your manager.</div></div>
  <?php else: ?>
  <div class="projects-grid">
    <?php foreach($projects as $p): ?>
    <div class="project-card">
      <span class="project-service"><?= str_replace('_',' ',$p['service_type']) ?></span>
      <div class="project-name"><?= htmlspecialchars($p['title']) ?></div>
      <div class="progress-label"><span>Progress</span><span><?= $p['progress'] ?>%</span></div>
      <div class="progress-bar"><div class="progress-fill" style="width:<?= $p['progress'] ?>%"></div></div>
      <div class="project-footer">
        <span class="status s-<?= $p['status'] ?>"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
        <?php if($p['deadline']): ?><span class="deadline">📅 <?= date('d M Y',strtotime($p['deadline'])) ?></span><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Reports -->
  <div class="section-title">📈 Campaign Reports</div>
  <?php if(empty($reports)): ?>
  <div class="no-data"><div class="no-data-icon">📊</div><div class="no-data-title">No Reports Yet</div><div class="no-data-sub">Your agency will upload monthly reports here soon.</div></div>
  <?php else: ?>
  <div class="reports-grid">
    <?php foreach($reports as $r):
      $rm=$r['report_month'];if(strlen($rm)===7)$rm.='-01';
    ?>
    <div class="report-card">
      <div class="report-head">
        <div><div class="report-month"><?= date('F Y',strtotime($rm)) ?></div><div class="report-client-tag">Campaign Analytics Report</div></div>
      </div>
      <div class="report-stats">
        <div class="stat-box"><div class="stat-val"><?= number_format($r['seo_clicks']) ?></div><div class="stat-lbl">SEO Clicks</div></div>
        <div class="stat-box"><div class="stat-val"><?= number_format($r['seo_impressions']) ?></div><div class="stat-lbl">Impressions</div></div>
        <div class="stat-box"><div class="stat-val"><?= number_format($r['website_visits']) ?></div><div class="stat-lbl">Visits</div></div>
        <div class="stat-box"><div class="stat-val"><?= number_format($r['fb_reach']) ?></div><div class="stat-lbl">FB Reach</div></div>
        <div class="stat-box"><div class="stat-val"><?= number_format($r['ig_reach']) ?></div><div class="stat-lbl">IG Reach</div></div>
        <div class="stat-box"><div class="stat-val"><?= $r['leads_generated'] ?></div><div class="stat-lbl">Leads</div></div>
      </div>
      <?php if($r['summary']): ?><div class="report-summary">📝 <?= htmlspecialchars($r['summary']) ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body></html>
