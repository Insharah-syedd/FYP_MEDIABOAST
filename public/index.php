<?php
require_once '../includes/config.php';
$db = getDB();
$services = $db->query("SELECT * FROM services WHERE is_active=1")->fetchAll();
$portfolio = $db->query("SELECT * FROM portfolio ORDER BY is_featured DESC, sort_order ASC LIMIT 9")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Media Marketing — Digital Growth Agency</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0b0f1a;--surface:#111827;--surface2:#1a2236;--border:#1e2d45;
  --accent:#3b82f6;--accent2:#6366f1;--green:#10b981;--amber:#f59e0b;
  --text:#f0f4ff;--muted:#6b7a99;
  --font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif;
}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font-body);line-height:1.6}

/* NAV */
nav{position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(11,15,26,.85);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:68px}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.nav-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-weight:800;color:#fff;font-size:17px}
.nav-logo-text{font-family:var(--font-head);font-weight:700;font-size:18px;background:linear-gradient(135deg,#fff 30%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-links{display:flex;align-items:center;gap:32px}
.nav-links a{color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:color .2s}
.nav-links a:hover{color:var(--text)}
.nav-cta{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;transition:opacity .2s,transform .15s;font-family:var(--font-head)}
.nav-cta:hover{opacity:.9;transform:translateY(-1px)}

/* HERO */
.hero{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:100px 40px 60px;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:48px 48px;opacity:.3;pointer-events:none}
.orb{position:absolute;border-radius:50%;filter:blur(100px);pointer-events:none}
.orb-1{width:600px;height:600px;background:rgba(59,130,246,.1);top:-200px;left:-100px}
.orb-2{width:500px;height:500px;background:rgba(99,102,241,.08);bottom:-150px;right:-100px}
.hero-content{position:relative;z-index:1;max-width:800px}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);border-radius:20px;padding:6px 16px;font-size:13px;font-weight:500;color:var(--accent);margin-bottom:24px}
.hero-badge span{width:6px;height:6px;border-radius:50%;background:var(--accent);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.hero h1{font-family:var(--font-head);font-size:clamp(40px,6vw,72px);font-weight:800;line-height:1.1;letter-spacing:-1.5px;margin-bottom:20px}
.hero h1 span{background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:18px;color:var(--muted);max-width:560px;margin:0 auto 36px;line-height:1.7}
.hero-btns{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;padding:14px 28px;border-radius:12px;text-decoration:none;font-size:15px;font-weight:600;font-family:var(--font-head);transition:all .2s;box-shadow:0 4px 20px rgba(59,130,246,.3)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(59,130,246,.4)}
.btn-outline{border:1px solid var(--border);color:var(--text);padding:14px 28px;border-radius:12px;text-decoration:none;font-size:15px;font-weight:600;font-family:var(--font-head);transition:all .2s}
.btn-outline:hover{border-color:var(--accent);color:var(--accent)}
.hero-stats{display:flex;gap:48px;justify-content:center;margin-top:64px;padding-top:48px;border-top:1px solid var(--border)}
.stat-item{text-align:center}
.stat-num{font-family:var(--font-head);font-size:36px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-lbl{font-size:13px;color:var(--muted);margin-top:4px}

/* SECTIONS */
section{padding:100px 40px}
.container{max-width:1100px;margin:0 auto}
.section-badge{display:inline-block;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:600;color:var(--accent);letter-spacing:.05em;text-transform:uppercase;margin-bottom:16px}
.section-title{font-family:var(--font-head);font-size:clamp(28px,4vw,44px);font-weight:800;letter-spacing:-1px;margin-bottom:16px;line-height:1.2}
.section-sub{font-size:16px;color:var(--muted);max-width:560px;line-height:1.7}
.text-center{text-align:center}.text-center .section-sub{margin:0 auto}

/* SERVICES */
.services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-top:48px}
.service-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;transition:border-color .2s,transform .2s;position:relative;overflow:hidden}
.service-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2));opacity:0;transition:opacity .2s}
.service-card:hover{border-color:rgba(59,130,246,.3);transform:translateY(-4px)}
.service-card:hover::before{opacity:1}
.service-icon{font-size:40px;margin-bottom:20px}
.service-name{font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:10px}
.service-desc{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:20px}
.service-price{font-family:var(--font-head);font-size:22px;font-weight:800;color:var(--green)}
.service-price span{font-size:13px;color:var(--muted);font-family:var(--font-body);font-weight:400}

/* WHY US */
.why-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;margin-top:48px}
.why-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px}
.why-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:16px}
.why-title{font-family:var(--font-head);font-size:16px;font-weight:700;margin-bottom:8px}
.why-text{font-size:13px;color:var(--muted);line-height:1.6}

/* PORTFOLIO */
.portfolio-filter{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:32px;margin-top:48px}
.filter-btn{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:8px 18px;font-size:13px;font-weight:500;color:var(--muted);cursor:pointer;transition:all .2s;font-family:var(--font-body)}
.filter-btn.active,.filter-btn:hover{background:rgba(59,130,246,.1);border-color:var(--accent);color:var(--accent)}
.portfolio-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.portfolio-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:border-color .2s,transform .2s}
.portfolio-card:hover{border-color:rgba(59,130,246,.3);transform:translateY(-4px)}
.portfolio-thumb{height:140px;display:flex;align-items:center;justify-content:center;font-size:48px;position:relative}
.portfolio-featured{position:absolute;top:10px;right:10px;background:var(--amber);color:#000;font-size:10px;font-weight:700;padding:3px 8px;border-radius:8px}
.portfolio-body{padding:20px}
.portfolio-cat{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em}
.portfolio-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:6px}
.portfolio-client{font-size:12px;color:var(--muted);margin-bottom:8px}
.portfolio-result{font-size:12px;color:var(--green)}

/* PROCESS */
.process-steps{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;margin-top:48px;position:relative}
.step-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;text-align:center;position:relative}
.step-num{width:40px;height:40px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-weight:800;font-size:16px;color:#fff;margin:0 auto 16px}
.step-title{font-family:var(--font-head);font-size:15px;font-weight:700;margin-bottom:8px}
.step-text{font-size:13px;color:var(--muted);line-height:1.6}

/* BOOKING FORM */
#booking{background:var(--surface)}
.booking-wrap{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start}
.booking-info h2{font-family:var(--font-head);font-size:36px;font-weight:800;letter-spacing:-1px;margin-bottom:16px}
.booking-info p{color:var(--muted);line-height:1.7;margin-bottom:32px}
.contact-items{display:flex;flex-direction:column;gap:16px}
.contact-item{display:flex;align-items:center;gap:14px;font-size:14px}
.contact-icon{width:40px;height:40px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.form-card{background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:36px}
.form-title{font-family:var(--font-head);font-size:20px;font-weight:700;margin-bottom:24px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.form-group.full{grid-column:1/-1}
.form-group label{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
.form-group input,.form-group select,.form-group textarea{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:12px 14px;color:var(--text);font-family:var(--font-body);font-size:14px;outline:none;transition:border-color .2s;width:100%}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent)}
.form-group select option{background:var(--surface2)}
.form-group textarea{resize:vertical;min-height:100px}
.submit-btn{width:100%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:12px;padding:15px;font-family:var(--font-head);font-size:16px;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:0 4px 20px rgba(59,130,246,.3)}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(59,130,246,.4)}
.success-msg{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:12px;padding:16px 20px;color:#6ee7b7;font-size:14px;margin-bottom:20px;display:none}
.error-msg{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:16px 20px;color:#fca5a5;font-size:14px;margin-bottom:20px;display:none}

/* TESTIMONIALS */
.testi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-top:48px}
.testi-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px}
.testi-stars{color:var(--amber);font-size:16px;margin-bottom:14px}
.testi-text{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:20px;font-style:italic}
.testi-author{display:flex;align-items:center;gap:12px}
.testi-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:#fff}
.testi-name{font-weight:600;font-size:14px}
.testi-company{font-size:12px;color:var(--muted)}

/* FOOTER */
footer{background:var(--surface);border-top:1px solid var(--border);padding:60px 40px 32px}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;max-width:1100px;margin:0 auto 48px}
.footer-brand p{color:var(--muted);font-size:14px;margin-top:14px;line-height:1.7;max-width:260px}
.footer-col h4{font-family:var(--font-head);font-size:14px;font-weight:700;margin-bottom:16px}
.footer-col a{display:block;color:var(--muted);text-decoration:none;font-size:13px;margin-bottom:10px;transition:color .2s}
.footer-col a:hover{color:var(--accent)}
.footer-bottom{max-width:1100px;margin:0 auto;padding-top:32px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:13px;color:var(--muted)}
.footer-bottom a{color:var(--muted);text-decoration:none}.footer-bottom a:hover{color:var(--accent)}

@media(max-width:768px){
  nav{padding:0 20px}.nav-links{display:none}
  section{padding:60px 20px}
  .hero{padding:100px 20px 60px}
  .booking-wrap{grid-template-columns:1fr}
  .footer-grid{grid-template-columns:1fr 1fr}
  .hero-stats{gap:24px}
  .form-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="#" class="nav-logo">
    <div class="nav-logo-icon">M</div>
    <span class="nav-logo-text">MediaBoost</span>
  </a>
  <div class="nav-links">
    <a href="#services">Services</a>
    <a href="#portfolio">Portfolio</a>
    <a href="#process">Process</a>
    <a href="#booking">Contact</a>
    <a href="../index.php" class="nav-cta">Client Login</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="hero-content">
    <div class="hero-badge"><span></span> Pakistan's Leading Digital Agency</div>
    <h1>Grow Your Business<br>With <span>Digital Marketing</span></h1>
    <p>We help Pakistani businesses scale online through SEO, Social Media, Web Development and result-driven digital campaigns.</p>
    <div class="hero-btns">
      <a href="#booking" class="btn-primary">Get Free Consultation →</a>
      <a href="#portfolio" class="btn-outline">View Our Work</a>
    </div>
    <div class="hero-stats">
      <div class="stat-item"><div class="stat-num">150+</div><div class="stat-lbl">Clients Served</div></div>
      <div class="stat-item"><div class="stat-num">98%</div><div class="stat-lbl">Client Satisfaction</div></div>
      <div class="stat-item"><div class="stat-num">5+</div><div class="stat-lbl">Years Experience</div></div>
      <div class="stat-item"><div class="stat-num">3x</div><div class="stat-lbl">Average ROI</div></div>
    </div>
  </div>
</section>

<!-- SERVICES -->
<section id="services">
  <div class="container">
    <div class="text-center">
      <div class="section-badge">Our Services</div>
      <h2 class="section-title">Everything You Need to<br>Dominate Online</h2>
      <p class="section-sub">From SEO to social media, we provide end-to-end digital marketing solutions tailored for your business.</p>
    </div>
    <div class="services-grid">
      <?php
      $svcIcons=['SEO Optimization'=>'🔍','Social Media Marketing'=>'📱','Web Development'=>'🌐','Content Writing'=>'✍️','Backlink Building'=>'🔗'];
      foreach($services as $s):
        $icon=$svcIcons[$s['name']]??'🛠️';
      ?>
      <div class="service-card">
        <div class="service-icon"><?= $icon ?></div>
        <div class="service-name"><?= htmlspecialchars($s['name']) ?></div>
        <div class="service-desc"><?= htmlspecialchars($s['description']??'Professional digital marketing service tailored to grow your business online.') ?></div>
        <?php if($s['price']): ?>
        <div class="service-price">Rs. <?= number_format($s['price']) ?> <span>/ month</span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if(empty($services)): ?>
      <div class="service-card"><div class="service-icon">🔍</div><div class="service-name">SEO Optimization</div><div class="service-desc">Rank higher on Google and drive organic traffic to your website with our proven SEO strategies.</div><div class="service-price">Rs. 15,000 <span>/ month</span></div></div>
      <div class="service-card"><div class="service-icon">📱</div><div class="service-name">Social Media Marketing</div><div class="service-desc">Grow your brand on Facebook, Instagram and TikTok with targeted campaigns and engaging content.</div><div class="service-price">Rs. 12,000 <span>/ month</span></div></div>
      <div class="service-card"><div class="service-icon">🌐</div><div class="service-name">Web Development</div><div class="service-desc">Modern, fast and mobile-friendly websites that convert visitors into customers.</div><div class="service-price">Rs. 50,000 <span>/ month</span></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- WHY US -->
<section style="padding:60px 40px;background:var(--surface)">
  <div class="container">
    <div class="text-center">
      <div class="section-badge">Why Choose Us</div>
      <h2 class="section-title">Results That Speak<br>For Themselves</h2>
    </div>
    <div class="why-grid">
      <div class="why-card"><div class="why-icon" style="background:rgba(59,130,246,.1)">📊</div><div class="why-title">Data-Driven Strategy</div><div class="why-text">Every decision backed by analytics and real data, not guesswork.</div></div>
      <div class="why-card"><div class="why-icon" style="background:rgba(16,185,129,.1)">⚡</div><div class="why-title">Fast Results</div><div class="why-text">We focus on quick wins while building long-term sustainable growth.</div></div>
      <div class="why-card"><div class="why-icon" style="background:rgba(245,158,11,.1)">🎯</div><div class="why-title">Targeted Campaigns</div><div class="why-text">Reach your exact audience with laser-focused targeting strategies.</div></div>
      <div class="why-card"><div class="why-icon" style="background:rgba(168,85,247,.1)">📱</div><div class="why-title">Full Transparency</div><div class="why-text">Monthly reports so you always know exactly where your money is going.</div></div>
    </div>
  </div>
</section>

<!-- PORTFOLIO -->
<section id="portfolio">
  <div class="container">
    <div class="text-center">
      <div class="section-badge">Our Work</div>
      <h2 class="section-title">Projects We're<br>Proud Of</h2>
      <p class="section-sub">Real results for real businesses across Pakistan.</p>
    </div>
    <div class="portfolio-filter">
      <button class="filter-btn active" onclick="filterPortfolio('all',this)">All</button>
      <button class="filter-btn" onclick="filterPortfolio('seo',this)">SEO</button>
      <button class="filter-btn" onclick="filterPortfolio('social_media',this)">Social Media</button>
      <button class="filter-btn" onclick="filterPortfolio('web_dev',this)">Web Dev</button>
      <button class="filter-btn" onclick="filterPortfolio('content',this)">Content</button>
    </div>
    <div class="portfolio-grid" id="portfolioGrid">
      <?php
      $catColors=['web_dev'=>'#3b82f6','seo'=>'#10b981','social_media'=>'#f59e0b','content'=>'#a855f7','backlinks'=>'#f97316'];
      $catIcons=['web_dev'=>'🌐','seo'=>'🔍','social_media'=>'📱','content'=>'✍️','backlinks'=>'🔗'];
      foreach($portfolio as $p):
        $cc=$catColors[$p['category']]??'#6b7a99';
        $ci=$catIcons[$p['category']]??'📂';
      ?>
      <div class="portfolio-card" data-cat="<?= $p['category'] ?>">
        <div class="portfolio-thumb" style="background:<?= $cc ?>18">
          <span><?= $ci ?></span>
          <?php if($p['is_featured']): ?><span class="portfolio-featured">⭐ Featured</span><?php endif; ?>
        </div>
        <div class="portfolio-body">
          <span class="portfolio-cat" style="background:<?= $cc ?>18;color:<?= $cc ?>"><?= ucfirst(str_replace('_',' ',$p['category'])) ?></span>
          <div class="portfolio-title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="portfolio-client">👥 <?= htmlspecialchars($p['client_name']??'') ?></div>
          <?php if($p['results']): ?><div class="portfolio-result">✅ <?= htmlspecialchars($p['results']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($portfolio)): ?>
      <div class="portfolio-card"><div class="portfolio-thumb" style="background:rgba(16,185,129,.08)"><span>🔍</span></div><div class="portfolio-body"><span class="portfolio-cat" style="background:rgba(16,185,129,.1);color:#10b981">SEO</span><div class="portfolio-title">E-commerce SEO Campaign</div><div class="portfolio-client">👥 Style PK</div><div class="portfolio-result">✅ 200% traffic increase in 3 months</div></div></div>
      <div class="portfolio-card"><div class="portfolio-thumb" style="background:rgba(59,130,246,.08)"><span>🌐</span></div><div class="portfolio-body"><span class="portfolio-cat" style="background:rgba(59,130,246,.1);color:#3b82f6">Web Dev</span><div class="portfolio-title">Restaurant Website</div><div class="portfolio-client">👥 Lahori Dhaba</div><div class="portfolio-result">✅ 98 PageSpeed score</div></div></div>
      <div class="portfolio-card"><div class="portfolio-thumb" style="background:rgba(245,158,11,.08)"><span>📱</span></div><div class="portfolio-body"><span class="portfolio-cat" style="background:rgba(245,158,11,.1);color:#f59e0b">Social Media</span><div class="portfolio-title">Social Media Growth</div><div class="portfolio-client">👥 TechStart PK</div><div class="portfolio-result">✅ 5000 new followers in 60 days</div></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- PROCESS -->
<section id="process" style="background:var(--surface)">
  <div class="container">
    <div class="text-center">
      <div class="section-badge">How We Work</div>
      <h2 class="section-title">Our Simple 4-Step<br>Process</h2>
      <p class="section-sub">From consultation to results — a clear, transparent process every step of the way.</p>
    </div>
    <div class="process-steps">
      <div class="step-card"><div class="step-num">1</div><div class="step-title">Free Consultation</div><div class="step-text">We discuss your business goals, target audience and current digital presence.</div></div>
      <div class="step-card"><div class="step-num">2</div><div class="step-title">Strategy & Plan</div><div class="step-text">Our team creates a custom digital marketing strategy tailored to your needs.</div></div>
      <div class="step-card"><div class="step-num">3</div><div class="step-title">Execute & Launch</div><div class="step-text">We implement the strategy across all channels with precision and speed.</div></div>
      <div class="step-card"><div class="step-num">4</div><div class="step-title">Report & Optimize</div><div class="step-text">Monthly reports with real data. We continuously optimize for better results.</div></div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section>
  <div class="container">
    <div class="text-center">
      <div class="section-badge">Testimonials</div>
      <h2 class="section-title">What Our Clients Say</h2>
    </div>
    <div class="testi-grid">
      <div class="testi-card"><div class="testi-stars">★★★★★</div><div class="testi-text">"All Media Marketing transformed our online presence. Our website traffic increased by 300% in just 4 months. Highly recommended!"</div><div class="testi-author"><div class="testi-avatar">B</div><div><div class="testi-name">Bilal Raza</div><div class="testi-company">TechStart PK, Lahore</div></div></div></div>
      <div class="testi-card"><div class="testi-stars">★★★★★</div><div class="testi-text">"Their social media team is incredible. We went from 500 to 8000 followers on Instagram in 2 months. Sales have doubled!"</div><div class="testi-author"><div class="testi-avatar">F</div><div><div class="testi-name">Fatima Malik</div><div class="testi-company">Style Boutique, Karachi</div></div></div></div>
      <div class="testi-card"><div class="testi-stars">★★★★★</div><div class="testi-text">"Professional team, transparent reporting and real results. Best digital agency in Pakistan. Worth every rupee!"</div><div class="testi-author"><div class="testi-avatar">U</div><div><div class="testi-name">Usman Ghani</div><div class="testi-company">Ghani Corp, Islamabad</div></div></div></div>
    </div>
  </div>
</section>

<!-- BOOKING FORM -->
<section id="booking">
  <div class="container">
    <div class="booking-wrap">
      <div class="booking-info">
        <div class="section-badge">Get Started</div>
        <h2>Book a Free<br>Consultation</h2>
        <p>Ready to grow your business? Fill out the form and our team will get back to you within 24 hours with a customized plan.</p>
        <div class="contact-items">
          <div class="contact-item"><div class="contact-icon">📧</div><div><div style="font-weight:600;font-size:14px">Email Us</div><div style="color:var(--muted);font-size:13px">info@allmediamarketing.com</div></div></div>
          <div class="contact-item"><div class="contact-icon">📱</div><div><div style="font-weight:600;font-size:14px">WhatsApp</div><div style="color:var(--muted);font-size:13px">+92 300 1234567</div></div></div>
          <div class="contact-item"><div class="contact-icon">📍</div><div><div style="font-weight:600;font-size:14px">Office</div><div style="color:var(--muted);font-size:13px">Lahore, Pakistan</div></div></div>
          <div class="contact-item"><div class="contact-icon">⏰</div><div><div style="font-weight:600;font-size:14px">Working Hours</div><div style="color:var(--muted);font-size:13px">Mon–Sat, 9am–6pm PKT</div></div></div>
        </div>
      </div>
      <div class="form-card">
        <div class="form-title">📋 Service Booking Form</div>
        <div class="success-msg" id="successMsg">✅ Thank you! We'll contact you within 24 hours.</div>
        <div class="error-msg" id="errorMsg">⚠ Something went wrong. Please try again.</div>
        <form id="bookingForm" method="POST" action="booking_submit.php">
          <div class="form-row">
            <div class="form-group"><label>Full Name *</label><input type="text" name="name" required placeholder="Your full name"></div>
            <div class="form-group"><label>Phone *</label><input type="text" name="phone" required placeholder="03001234567"></div>
          </div>
          <div class="form-group full"><label>Email Address *</label><input type="email" name="email" required placeholder="your@email.com"></div>
          <div class="form-group full"><label>Business Name</label><input type="text" name="business_name" placeholder="Your company name"></div>
          <div class="form-row">
            <div class="form-group"><label>Service Interested In</label>
              <select name="service_id">
                <option value="">Select a service</option>
                <?php foreach($services as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group"><label>Monthly Budget</label>
              <select name="budget_range">
                <option value="under_50k">Under Rs. 50,000</option>
                <option value="50k_150k">Rs. 50,000 – 150,000</option>
                <option value="150k_500k">Rs. 150,000 – 500,000</option>
                <option value="500k_plus">Rs. 500,000+</option>
              </select>
            </div>
          </div>
          <div class="form-group full"><label>Tell Us About Your Business</label><textarea name="message" placeholder="Describe your business, goals and what you're looking to achieve..."></textarea></div>
          <button type="submit" class="submit-btn">Send Request — It's Free! 🚀</button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div class="footer-brand">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:14px">M</div>
        <span style="font-family:var(--font-head);font-weight:700;font-size:16px">MediaBoost</span>
      </div>
      <p>Pakistan's trusted digital marketing agency helping businesses grow online since 2019.</p>
    </div>
    <div class="footer-col">
      <h4>Services</h4>
      <a href="#services">SEO Optimization</a>
      <a href="#services">Social Media</a>
      <a href="#services">Web Development</a>
      <a href="#services">Content Writing</a>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <a href="#portfolio">Portfolio</a>
      <a href="#process">Our Process</a>
      <a href="#booking">Contact Us</a>
      <a href="../index.php">Client Login</a>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <a href="#">info@allmediamarketing.com</a>
      <a href="#">+92 300 1234567</a>
      <a href="#">Lahore, Pakistan</a>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> All Media Marketing. All rights reserved.</span>
    <span>Made with ❤️ in Pakistan</span>
  </div>
</footer>

<script>
// Portfolio filter
function filterPortfolio(cat, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.portfolio-card').forEach(card => {
    if (cat === 'all' || card.dataset.cat === cat) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

// Booking form AJAX
document.getElementById('bookingForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('.submit-btn');
  btn.textContent = 'Sending...';
  btn.disabled = true;
  fetch('booking_submit.php', {
    method: 'POST',
    body: new FormData(this)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('successMsg').style.display = 'block';
      document.getElementById('errorMsg').style.display = 'none';
      this.reset();
    } else {
      document.getElementById('errorMsg').style.display = 'block';
      document.getElementById('successMsg').style.display = 'none';
    }
    btn.textContent = 'Send Request — It\'s Free! 🚀';
    btn.disabled = false;
  })
  .catch(() => {
    document.getElementById('errorMsg').style.display = 'block';
    btn.textContent = 'Send Request — It\'s Free! 🚀';
    btn.disabled = false;
  });
});

// Navbar scroll effect
window.addEventListener('scroll', () => {
  const nav = document.querySelector('nav');
  nav.style.borderBottomColor = window.scrollY > 50 ? 'rgba(59,130,246,.2)' : 'var(--border)';
});
</script>
</body>
</html>
