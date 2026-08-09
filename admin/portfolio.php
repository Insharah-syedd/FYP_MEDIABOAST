<?php
require_once '../includes/auth.php';
error_reporting(E_ERROR|E_PARSE);
requireRole(['admin','manager']);
$db=getDB();$user=currentUser();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$action=$_POST['action']??'';
  if($action==='add'){$db->prepare("INSERT INTO portfolio (title,category,description,client_name,thumbnail,live_url,results,is_featured) VALUES (?,?,?,?,?,?,?,?)")->execute([$_POST['title'],$_POST['category'],$_POST['description'],$_POST['client_name'],$_POST['thumbnail'],$_POST['live_url'],$_POST['results'],$_POST['is_featured']?:0]);$msg='Portfolio item added successfully! ✅';}
  if($action==='edit'){$db->prepare("UPDATE portfolio SET title=?,category=?,description=?,client_name=?,thumbnail=?,live_url=?,results=?,is_featured=? WHERE id=?")->execute([$_POST['title'],$_POST['category'],$_POST['description'],$_POST['client_name'],$_POST['thumbnail'],$_POST['live_url'],$_POST['results'],$_POST['is_featured']?:0,$_POST['portfolio_id']]);$msg='Updated! ✅';}
  if($action==='delete'){$db->prepare("DELETE FROM portfolio WHERE id=?")->execute([$_POST['portfolio_id']]);$msg='Deleted!';}
}
$items=$db->query("SELECT * FROM portfolio ORDER BY is_featured DESC,sort_order ASC,created_at DESC")->fetchAll();
$editItem=null;if(!empty($_GET['edit'])){$e=$db->prepare("SELECT * FROM portfolio WHERE id=?");$e->execute([$_GET['edit']]);$editItem=$e->fetch();}
$catColors=['web_dev'=>'#3b82f6','seo'=>'#10b981','social_media'=>'#f59e0b','content'=>'#a855f7','backlinks'=>'#f97316'];
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portfolio — MediaBoost</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;--accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--text:#f0f4ff;--muted:#6b7a99;--sidebar-w:240px;--font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif}
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
.logout-btn{font-size:16px;color:var(--muted);text-decoration:none}.logout-btn:hover{color:var(--red,#ef4444)}
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 32px}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.page-title{font-family:var(--font-head);font-size:26px;font-weight:800}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:var(--font-body)}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}.btn-sm{padding:6px 12px;font-size:12px}.btn-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444}
.alert{border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.portfolio-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.portfolio-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:border-color .2s,transform .15s}
.portfolio-card:hover{border-color:rgba(59,130,246,.4);transform:translateY(-3px)}
.card-thumb{height:140px;display:flex;align-items:center;justify-content:center;font-size:48px;position:relative}
.featured-badge{position:absolute;top:10px;right:10px;background:#f59e0b;color:#000;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px}
.card-body{padding:20px}
.card-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:4px}
.card-client{font-size:12px;color:var(--muted);margin-bottom:10px}
.cat-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:10px}
.card-results{font-size:12px;color:var(--green);margin-bottom:14px}
.card-footer{display:flex;gap:8px;padding-top:12px;border-top:1px solid var(--border)}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto}
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
.checkbox-wrap{display:flex;align-items:center;gap:8px;font-size:13px}
.checkbox-wrap input{width:auto}
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
  <div class="page-header"><div class="page-title">🖼️ Portfolio</div><button class="btn btn-primary" onclick="openModal('addModal')">+ Add Project</button></div>
  <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
  <div class="portfolio-grid">
    <?php $catIcons=['web_dev'=>'🌐','seo'=>'🔍','social_media'=>'📱','content'=>'✍️','backlinks'=>'🔗']; ?>
    <?php foreach($items as $item): ?>
    <?php $cc=$catColors[$item['category']]??'#6b7a99'; ?>
    <div class="portfolio-card">
      <div class="card-thumb" style="background:<?php echo $cc; ?>18">
        <span><?php echo $catIcons[$item['category']]??'📂'; ?></span>
        <?php if($item['is_featured']): ?><span class="featured-badge">⭐ Featured</span><?php endif; ?>
      </div>
      <div class="card-body">
        <div class="cat-badge" style="background:<?php echo $cc; ?>18;color:<?php echo $cc; ?>"><?php echo ucfirst(str_replace('_',' ',$item['category'])); ?></div>
        <div class="card-title"><?php echo htmlspecialchars($item['title']); ?></div>
        <div class="card-client">👥 <?php echo htmlspecialchars($item['client_name']??''); ?></div>
        <?php if($item['results']): ?><div class="card-results">✅ <?php echo htmlspecialchars($item['results']); ?></div><?php endif; ?>
        <div class="card-footer">
          <a href="portfolio.php?edit=<?php echo $item['id']; ?>" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center">✏️ Edit</a>
          <form method="POST" onsubmit="return confirm('Delete?')" style="flex:1">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="portfolio_id" value="<?php echo $item['id']; ?>">
            <button class="btn btn-danger btn-sm" style="width:100%;justify-content:center">🗑 Delete</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($items)): ?><div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--muted)"><div style="font-size:48px;margin-bottom:16px">🖼️</div><div>No portfolio items found. Add one using + Add Project.</div></div><?php endif; ?>
  </div>
</main>
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title">New Portfolio Item <button class="close-btn" onclick="closeModal('addModal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group full"><label>Title *</label><input type="text" name="title" required placeholder="Project title"></div>
        <div class="form-group"><label>Category</label><select name="category"><option value="seo">SEO</option><option value="social_media">Social Media</option><option value="web_dev">Web Dev</option><option value="content">Content</option><option value="backlinks">Backlinks</option></select></div>
        <div class="form-group"><label>Client Name</label><input type="text" name="client_name" placeholder="Client business"></div>
        <div class="form-group full"><label>Description</label><textarea name="description" placeholder="Project description..."></textarea></div>
        <div class="form-group full"><label>Results Achieved</label><input type="text" name="results" placeholder="e.g. 200% traffic increase in 3 months"></div>
        <div class="form-group"><label>Live URL</label><input type="url" name="live_url" placeholder="https://..."></div>
        <div class="form-group"><label>Thumbnail URL</label><input type="url" name="thumbnail" placeholder="https://image.url"></div>
        <div class="form-group full"><label class="checkbox-wrap"><input type="checkbox" name="is_featured" value="1"> ⭐ Featured project (show on homepage)</label></div>
      </div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>
<?php if($editItem): ?>
<div class="modal-overlay open">
  <div class="modal">
    <div class="modal-title">Edit Portfolio <button class="close-btn" onclick="window.location='portfolio.php'">✕</button></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="portfolio_id" value="<?php echo $editItem['id']; ?>">
      <div class="form-grid">
        <div class="form-group full"><label>Title *</label><input type="text" name="title" required value="<?php echo htmlspecialchars($editItem['title']); ?>"></div>
        <div class="form-group"><label>Category</label><select name="category"><?php foreach(['seo','social_media','web_dev','content','backlinks'] as $c): ?><option value="<?php echo $c; ?>" <?php echo $editItem['category']===$c?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$c)); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Client Name</label><input type="text" name="client_name" value="<?php echo htmlspecialchars($editItem['client_name']??''); ?>"></div>
        <div class="form-group full"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($editItem['description']??''); ?></textarea></div>
        <div class="form-group full"><label>Results</label><input type="text" name="results" value="<?php echo htmlspecialchars($editItem['results']??''); ?>"></div>
        <div class="form-group"><label>Live URL</label><input type="url" name="live_url" value="<?php echo htmlspecialchars($editItem['live_url']??''); ?>"></div>
        <div class="form-group"><label>Thumbnail URL</label><input type="url" name="thumbnail" value="<?php echo htmlspecialchars($editItem['thumbnail']??''); ?>"></div>
        <div class="form-group full"><label class="checkbox-wrap"><input type="checkbox" name="is_featured" value="1" <?php echo $editItem['is_featured']?'checked':''; ?>> ⭐ Featured</label></div>
      </div>
      <div class="form-actions"><a href="portfolio.php" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>function openModal(id){document.getElementById(id).classList.add('open')}function closeModal(id){document.getElementById(id).classList.remove('open')}</script>
</body></html>
