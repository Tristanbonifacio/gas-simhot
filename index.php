<?php
/**
 * GAS-SIMHOT — Landing Page / Index
 * Location: index.php (project root)
 */

require_once __DIR__ . '/config/db_connect.php';

// If already logged in, go straight to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_for($_SESSION['role']));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GAS-SIMHOT — Gas Safety Monitoring System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --bg:      #050d1a;
  --panel:   #0b1629;
  --border:  #1a3a5c;
  --accent:  #00d4ff;
  --a2:      #ff4c4c;
  --a3:      #00e5a0;
  --text:    #cfe8ff;
  --muted:   #4a7a9b;
  --mono:    'Share Tech Mono', monospace;
  --sans:    'Exo 2', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  font-family: var(--sans);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* ── Animated grid background ── */
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image:
    linear-gradient(rgba(0,212,255,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,212,255,.04) 1px, transparent 1px);
  background-size: 50px 50px;
  animation: gridScroll 25s linear infinite;
  pointer-events: none;
  z-index: 0;
}
@keyframes gridScroll { to { transform: translateY(50px); } }

/* ── Glow orbs ── */
.orb1 {
  position: fixed; width: 600px; height: 600px; border-radius: 50%;
  background: radial-gradient(circle, rgba(0,212,255,.1) 0%, transparent 70%);
  top: -200px; left: -200px; pointer-events: none; z-index: 0;
  animation: orbFloat 8s ease-in-out infinite alternate;
}
.orb2 {
  position: fixed; width: 500px; height: 500px; border-radius: 50%;
  background: radial-gradient(circle, rgba(0,229,160,.08) 0%, transparent 70%);
  bottom: -150px; right: -150px; pointer-events: none; z-index: 0;
  animation: orbFloat 10s ease-in-out infinite alternate-reverse;
}
@keyframes orbFloat {
  from { transform: scale(1) translate(0,0); }
  to   { transform: scale(1.15) translate(20px, 20px); }
}

/* ── Scan line ── */
.scan {
  position: fixed; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
  animation: scan 5s linear infinite; pointer-events: none; z-index: 1;
}
@keyframes scan { from { top: 0; opacity: .8; } to { top: 100vh; opacity: 0; } }

/* ── Navbar ── */
nav {
  position: relative; z-index: 10;
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.2rem 3rem;
  border-bottom: 1px solid rgba(26,58,92,.6);
  background: rgba(5,13,26,.8);
  backdrop-filter: blur(10px);
}

.nav-brand {
  font-family: var(--mono);
  font-size: 1.1rem;
  color: var(--accent);
  letter-spacing: .1em;
  display: flex; align-items: center; gap: .6rem;
}

.nav-links { display: flex; align-items: center; gap: 1rem; }

.btn-nav-login {
  background: var(--accent);
  color: #050d1a;
  border: none;
  border-radius: 6px;
  padding: .5rem 1.4rem;
  font-family: var(--mono);
  font-size: .85rem;
  font-weight: 700;
  letter-spacing: .08em;
  cursor: pointer;
  text-decoration: none;
  transition: background .2s, box-shadow .2s;
  display: inline-block;
}
.btn-nav-login:hover {
  background: #33deff;
  box-shadow: 0 0 20px rgba(0,212,255,.5);
}

.btn-nav-register {
  background: transparent;
  color: var(--text);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: .5rem 1.4rem;
  font-family: var(--mono);
  font-size: .85rem;
  font-weight: 600;
  letter-spacing: .08em;
  cursor: pointer;
  text-decoration: none;
  transition: border-color .2s, color .2s;
  display: inline-block;
}
.btn-nav-register:hover { border-color: var(--accent); color: var(--accent); }

/* ── Hero ── */
.hero {
  position: relative; z-index: 5;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center;
  padding: 5rem 2rem 4rem;
  min-height: calc(100vh - 70px);
}

.hero-badge {
  display: inline-flex; align-items: center; gap: .5rem;
  background: rgba(0,229,160,.1);
  border: 1px solid rgba(0,229,160,.3);
  color: var(--a3);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  padding: .35rem .9rem;
  border-radius: 20px;
  margin-bottom: 1.8rem;
  animation: fadeInDown .6s ease both;
}

.pulse-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--a3);
  animation: dotPulse 1.5s infinite;
}
@keyframes dotPulse {
  0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(0,229,160,.6); }
  50%      { opacity: .6; box-shadow: 0 0 0 5px rgba(0,229,160,0); }
}

.hero h1 {
  font-family: var(--mono);
  font-size: clamp(2.2rem, 6vw, 4rem);
  font-weight: 700;
  letter-spacing: .06em;
  line-height: 1.15;
  color: var(--text);
  margin-bottom: 1rem;
  animation: fadeInDown .7s ease .1s both;
}

.hero h1 span { color: var(--accent); }

.hero-sub {
  font-size: clamp(.95rem, 2vw, 1.15rem);
  color: var(--muted);
  max-width: 580px;
  line-height: 1.7;
  margin-bottom: 2.5rem;
  animation: fadeInDown .7s ease .2s both;
}

.hero-btns {
  display: flex; align-items: center; gap: 1rem;
  flex-wrap: wrap; justify-content: center;
  animation: fadeInDown .7s ease .3s both;
}

.btn-hero-primary {
  background: var(--accent);
  color: #050d1a;
  border: none;
  border-radius: 8px;
  padding: .85rem 2rem;
  font-family: var(--mono);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .08em;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex; align-items: center; gap: .5rem;
  transition: background .2s, box-shadow .2s, transform .1s;
}
.btn-hero-primary:hover {
  background: #33deff;
  box-shadow: 0 0 30px rgba(0,212,255,.5);
  transform: translateY(-2px);
}

.btn-hero-secondary {
  background: transparent;
  color: var(--text);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: .85rem 2rem;
  font-family: var(--mono);
  font-size: 1rem;
  font-weight: 600;
  letter-spacing: .06em;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex; align-items: center; gap: .5rem;
  transition: border-color .2s, color .2s, transform .1s;
}
.btn-hero-secondary:hover {
  border-color: var(--accent);
  color: var(--accent);
  transform: translateY(-2px);
}

/* ── Stats row ── */
.stats-row {
  display: flex; align-items: center; gap: 2.5rem;
  margin-top: 3.5rem; flex-wrap: wrap; justify-content: center;
  animation: fadeInUp .7s ease .4s both;
}

.stat-item { text-align: center; }
.stat-item .num {
  font-family: var(--mono);
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--accent);
  line-height: 1;
}
.stat-item .lbl {
  font-size: .7rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-top: .3rem;
}

.stat-divider { width: 1px; height: 40px; background: var(--border); }

/* ── Features section ── */
.features {
  position: relative; z-index: 5;
  padding: 5rem 3rem;
  max-width: 1100px;
  margin: 0 auto;
}

.section-label {
  text-align: center;
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: .8rem;
}

.section-title {
  text-align: center;
  font-family: var(--mono);
  font-size: clamp(1.4rem, 3vw, 2rem);
  color: var(--text);
  margin-bottom: .8rem;
  letter-spacing: .04em;
}

.section-sub {
  text-align: center;
  font-size: .95rem;
  color: var(--muted);
  max-width: 500px;
  margin: 0 auto 3rem;
  line-height: 1.6;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.2rem;
}

.feat-card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.8rem;
  transition: transform .25s, box-shadow .25s, border-color .25s;
}

.feat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 32px rgba(0,0,0,.3);
  border-color: rgba(0,212,255,.3);
}

.feat-icon {
  font-size: 2rem;
  margin-bottom: 1rem;
  display: block;
}

.feat-title {
  font-family: var(--mono);
  font-size: .95rem;
  font-weight: 700;
  color: var(--accent);
  letter-spacing: .06em;
  margin-bottom: .6rem;
}

.feat-desc {
  font-size: .85rem;
  color: var(--muted);
  line-height: 1.65;
}

/* ── Roles section ── */
.roles {
  position: relative; z-index: 5;
  padding: 0 3rem 5rem;
  max-width: 1100px;
  margin: 0 auto;
}

.roles-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.2rem;
}

.role-card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 2rem 1.8rem;
  text-align: center;
  transition: transform .25s, border-color .25s;
}

.role-card:hover { transform: translateY(-4px); }
.role-card.r-admin   { border-color: rgba(0,212,255,.25); }
.role-card.r-manager { border-color: rgba(0,229,160,.25); }
.role-card.r-staff   { border-color: rgba(124,111,255,.25); }

.role-icon { font-size: 2.5rem; margin-bottom: 1rem; display: block; }

.role-name {
  font-family: var(--mono);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .08em;
  margin-bottom: .5rem;
}

.r-admin   .role-name { color: var(--accent); }
.r-manager .role-name { color: var(--a3); }
.r-staff   .role-name { color: #7c6fff; }

.role-desc {
  font-size: .82rem;
  color: var(--muted);
  line-height: 1.6;
}

/* ── CTA ── */
.cta {
  position: relative; z-index: 5;
  padding: 4rem 2rem 6rem;
  text-align: center;
}

.cta-box {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 3rem 2rem;
  max-width: 600px;
  margin: 0 auto;
  box-shadow: 0 0 60px rgba(0,212,255,.06);
}

.cta h2 {
  font-family: var(--mono);
  font-size: 1.6rem;
  color: var(--accent);
  letter-spacing: .06em;
  margin-bottom: .8rem;
}

.cta p { font-size: .9rem; color: var(--muted); line-height: 1.7; margin-bottom: 2rem; }

/* ── Footer ── */
footer {
  position: relative; z-index: 5;
  border-top: 1px solid var(--border);
  padding: 1.5rem 3rem;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 1rem;
}

.footer-brand {
  font-family: var(--mono);
  font-size: .85rem;
  color: var(--muted);
  letter-spacing: .08em;
}

.footer-links {
  display: flex; gap: 1.5rem;
}

.footer-links a {
  font-size: .8rem;
  color: var(--muted);
  text-decoration: none;
  transition: color .2s;
}
.footer-links a:hover { color: var(--accent); }

/* ── Animations ── */
@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ── */
@media (max-width: 768px) {
  nav { padding: 1rem 1.5rem; }
  .hero { padding: 3rem 1.5rem; }
  .features, .roles { padding-left: 1.5rem; padding-right: 1.5rem; }
  .roles-grid { grid-template-columns: 1fr; }
  .stat-divider { display: none; }
}
</style>
</head>
<body>
<div class="orb1"></div>
<div class="orb2"></div>
<div class="scan"></div>

<!-- NAVBAR -->
<nav>
  <div class="nav-brand">
    🛡️ GAS-SIMHOT
  </div>
  <div class="nav-links">
    <a href="<?= register_url() ?>" class="btn-nav-register">Register</a>
    <a href="<?= login_url() ?>" class="btn-nav-login">[ LOGIN ]</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-badge">
    <span class="pulse-dot"></span>
    Simulation-Based IoT System
  </div>

  <h1>
    Gas Safety Monitoring<br>
    <span>for Homes using IoT</span>
  </h1>

  <p class="hero-sub">
    GAS-SIMHOT is a real-time LPG gas leak detection and alert system.
    Monitor gas levels, trigger simulated alerts, and notify personnel instantly
    — all from a centralized dashboard.
  </p>

  <div class="hero-btns">
    <a href="<?= login_url() ?>" class="btn-hero-primary">
      🚀 Access Dashboard
    </a>
    <a href="<?= register_url() ?>" class="btn-hero-secondary">
      ✏️ Create Account
    </a>
  </div>

  <div class="stats-row">
    <div class="stat-item">
      <div class="num">3</div>
      <div class="lbl">User Roles</div>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <div class="num">4</div>
      <div class="lbl">Monitor Stations</div>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <div class="num">2s</div>
      <div class="lbl">Real-Time Polling</div>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <div class="num">24/7</div>
      <div class="lbl">System Uptime</div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="features">
  <div class="section-label">System Features</div>
  <h2 class="section-title">Everything You Need</h2>
  <p class="section-sub">A complete gas safety simulation platform with real-time monitoring, alerts, and full audit trail.</p>

  <div class="features-grid">
    <div class="feat-card">
      <span class="feat-icon">🌡️</span>
      <div class="feat-title">Real-Time Gas Monitoring</div>
      <p class="feat-desc">Live PPM gauge updates every 2 seconds. Visual and audio alerts trigger immediately when gas levels exceed safe thresholds.</p>
    </div>
    <div class="feat-card">
      <span class="feat-icon">🗺️</span>
      <div class="feat-title">Live Location Map</div>
      <p class="feat-desc">Admin dashboard shows an interactive map with the exact location of the triggered gas leak alert using OpenStreetMap.</p>
    </div>
    <div class="feat-card">
      <span class="feat-icon">🚨</span>
      <div class="feat-title">Instant Alert System</div>
      <p class="feat-desc">When a leak is detected, the admin receives an emergency sticky alert. Staff get notified once the admin acknowledges.</p>
    </div>
    <div class="feat-card">
      <span class="feat-icon">📋</span>
      <div class="feat-title">Activity Logs & Audit</div>
      <p class="feat-desc">Full audit trail of all actions — logins, leak simulations, resets, and acknowledgments — with timestamps.</p>
    </div>
    <div class="feat-card">
      <span class="feat-icon">👥</span>
      <div class="feat-title">Personnel Management</div>
      <p class="feat-desc">Manager can add, view, and remove personnel. Assign roles and monitoring stations to each team member.</p>
    </div>
    <div class="feat-card">
      <span class="feat-icon">🔐</span>
      <div class="feat-title">Secure Role-Based Access</div>
      <p class="feat-desc">Three-tier access control — Manager, Admin, and Staff — each with their own dedicated dashboard and permissions.</p>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="roles">
  <div class="section-label">Access Levels</div>
  <h2 class="section-title">Three Roles, One System</h2>
  <p class="section-sub">Each role has a dedicated dashboard with specific responsibilities and permissions.</p>

  <div class="roles-grid">
    <div class="role-card r-admin">
      <span class="role-icon">🛡️</span>
      <div class="role-name">ADMINISTRATOR</div>
      <p class="role-desc">Monitors live gas levels, views the alert map, manages activity logs, and acknowledges emergency alerts to notify staff.</p>
    </div>
    <div class="role-card r-manager">
      <span class="role-icon">🚀</span>
      <div class="role-name">MANAGER</div>
      <p class="role-desc">Oversees all personnel, registers new staff and admins, assigns monitoring stations, and reviews the full system audit log.</p>
    </div>
    <div class="role-card r-staff">
      <span class="role-icon">👷</span>
      <div class="role-name">STAFF / USER</div>
      <p class="role-desc">Monitors their assigned station's gas sensor, triggers leak simulations, resets the system, and tracks their own action history.</p>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <div class="cta-box">
    <h2>Ready to Monitor?</h2>
    <p>Login to your dashboard to start monitoring gas levels in real time, or create a new account to get started.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="<?= login_url() ?>" class="btn-hero-primary">🔐 Login Now</a>
      <a href="<?= register_url() ?>" class="btn-hero-secondary">📝 Register</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-brand">🛡️ GAS-SIMHOT &nbsp;·&nbsp; Gas Safety Monitoring for Homes using IoT</div>
  <div class="footer-links">
    <a href="<?= login_url() ?>">Login</a>
    <a href="<?= register_url() ?>">Register</a>
  </div>
</footer>

</body>
</html>