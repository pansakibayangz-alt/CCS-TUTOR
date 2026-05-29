<?php
// index.php — JRMSU Front Page / Landing Page
// No session required — this is the public-facing entry point
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JRMSU — Learning Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Crimson+Pro:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>

/* ═══════════════════════════════════════════
   CSS VARIABLES — JRMSU NAVY + GOLD IDENTITY
═══════════════════════════════════════════ */
:root {
  --navy-deep:   #040f1c;
  --navy:        #071828;
  --navy-mid:    #0b2540;
  --navy-light:  #0f3460;
  --gold:        #FFD700;
  --gold-dim:    #c9a800;
  --gold-pale:   rgba(255, 215, 0, 0.12);
  --gold-glow:   rgba(255, 215, 0, 0.35);
  --white:       #f5f0e8;
  --muted:       rgba(245, 240, 232, 0.6);
  --border:      rgba(255, 215, 0, 0.15);
}

/* ═══════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  font-family: 'Poppins', sans-serif;
  background: var(--navy-deep);
  color: var(--white);
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* ═══════════════════════════════════════════
   ANIMATED STAR CANVAS BACKGROUND
═══════════════════════════════════════════ */
#starfield {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  pointer-events: none;
  z-index: 0;
}

/* ═══════════════════════════════════════════
   NOISE TEXTURE OVERLAY
═══════════════════════════════════════════ */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  background-size: 200px 200px;
  pointer-events: none;
  z-index: 1;
  opacity: 0.4;
}

/* ═══════════════════════════════════════════
   HEADER / NAVBAR
═══════════════════════════════════════════ */
header {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2.5rem;
  height: 68px;
  background: rgba(4, 15, 28, 0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-seal {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 2px solid var(--gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Cinzel', serif;
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--gold);
  letter-spacing: 0.5px;
  text-align: center;
  line-height: 1.2;
  padding: 4px;
}

.header-title {
  font-family: 'Cinzel', serif;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--gold);
  letter-spacing: 1.5px;
  text-transform: uppercase;
  line-height: 1.3;
}

.header-title span {
  display: block;
  font-size: 0.65rem;
  font-weight: 600;
  color: var(--muted);
  letter-spacing: 2px;
}

.header-nav {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.header-nav a {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--muted);
  text-decoration: none;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  transition: color 0.2s;
}

.header-nav a:hover { color: var(--gold); }

.btn-login {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 22px;
  background: var(--gold);
  color: var(--navy-deep);
  font-family: 'Poppins', sans-serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  text-decoration: none;
  border: none;
  cursor: pointer;
  clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
  transition: background 0.2s, transform 0.15s;
}

.btn-login:hover {
  background: #ffe54d;
  transform: translateY(-1px);
}

/* ═══════════════════════════════════════════
   HERO SECTION
═══════════════════════════════════════════ */
.hero {
  position: relative;
  z-index: 2;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 120px 2rem 80px;
  text-align: center;
  overflow: hidden;
}

/* Diagonal gold accent line */
.hero::after {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 2px;
  height: 100%;
  background: linear-gradient(to bottom, transparent, var(--gold), transparent);
  opacity: 0.3;
}

.hero-inner {
  max-width: 860px;
  position: relative;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 2rem;
  opacity: 0;
  animation: fadeUp 0.8s 0.2s forwards;
}

.hero-eyebrow::before,
.hero-eyebrow::after {
  content: '';
  display: block;
  width: 36px;
  height: 1px;
  background: var(--gold);
  opacity: 0.6;
}

.hero-headline {
  font-family: 'Cinzel', serif;
  font-size: clamp(2.8rem, 6vw, 5rem);
  font-weight: 900;
  line-height: 1.05;
  letter-spacing: -0.5px;
  color: var(--white);
  margin-bottom: 0.3rem;
  opacity: 0;
  animation: fadeUp 0.8s 0.4s forwards;
}

.hero-headline .gold-text {
  color: var(--gold);
  display: block;
}

.hero-sub {
  font-family: 'Crimson Pro', serif;
  font-style: italic;
  font-size: clamp(1.1rem, 2.2vw, 1.5rem);
  color: var(--muted);
  margin: 1.2rem 0 2.8rem;
  line-height: 1.6;
  opacity: 0;
  animation: fadeUp 0.8s 0.6s forwards;
}

.hero-cta-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
  opacity: 0;
  animation: fadeUp 0.8s 0.8s forwards;
}

.btn-primary-hero {
  padding: 14px 36px;
  background: var(--gold);
  color: var(--navy-deep);
  font-family: 'Poppins', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  text-decoration: none;
  clip-path: polygon(10px 0%, 100% 0%, calc(100% - 10px) 100%, 0% 100%);
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.btn-primary-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.2);
  transform: translateX(-100%);
  transition: transform 0.3s;
}

.btn-primary-hero:hover::before { transform: translateX(0); }
.btn-primary-hero:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,215,0,0.3); }

.btn-ghost-hero {
  padding: 13px 32px;
  background: transparent;
  color: var(--white);
  font-family: 'Poppins', sans-serif;
  font-size: 0.85rem;
  font-weight: 500;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  text-decoration: none;
  border: 1px solid var(--border);
  clip-path: polygon(10px 0%, 100% 0%, calc(100% - 10px) 100%, 0% 100%);
  transition: all 0.2s;
}

.btn-ghost-hero:hover {
  border-color: var(--gold);
  color: var(--gold);
  background: var(--gold-pale);
}

/* Gold horizontal rule */
.gold-rule {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin: 3.5rem auto 0;
  opacity: 0;
  animation: fadeUp 0.8s 1s forwards;
}

.gold-rule::before,
.gold-rule::after {
  content: '';
  flex: 1;
  max-width: 120px;
  height: 1px;
  background: linear-gradient(to right, transparent, var(--gold));
}
.gold-rule::after { background: linear-gradient(to left, transparent, var(--gold)); }

.gold-rule-diamond {
  width: 8px; height: 8px;
  background: var(--gold);
  transform: rotate(45deg);
}

/* ═══════════════════════════════════════════
   STATS BAR
═══════════════════════════════════════════ */
.stats-bar {
  position: relative;
  z-index: 2;
  background: rgba(11, 37, 64, 0.7);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  backdrop-filter: blur(12px);
  padding: 2rem 0;
}

.stats-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
}

.stat-item {
  text-align: center;
  padding: 1.2rem 1rem;
  border-right: 1px solid var(--border);
  position: relative;
}

.stat-item:last-child { border-right: none; }

.stat-number {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--gold);
  line-height: 1;
  margin-bottom: 0.4rem;
}

.stat-label {
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--muted);
}

/* ═══════════════════════════════════════════
   USER ROLE CARDS SECTION
═══════════════════════════════════════════ */
.section-roles {
  position: relative;
  z-index: 2;
  padding: 6rem 2rem;
  max-width: 1100px;
  margin: 0 auto;
}

.section-label {
  text-align: center;
  margin-bottom: 4rem;
}

.section-label h2 {
  font-family: 'Cinzel', serif;
  font-size: clamp(1.8rem, 3.5vw, 2.5rem);
  font-weight: 700;
  color: var(--white);
  margin-bottom: 0.8rem;
}

.section-label p {
  font-family: 'Crimson Pro', serif;
  font-size: 1.15rem;
  font-style: italic;
  color: var(--muted);
}

.roles-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.role-card {
  position: relative;
  background: rgba(11, 37, 64, 0.5);
  border: 1px solid var(--border);
  padding: 2.5rem 2rem;
  text-decoration: none;
  color: inherit;
  overflow: hidden;
  transition: transform 0.25s, border-color 0.25s;
  display: block;
}

.role-card::before {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: var(--gold);
  transform: scaleX(0);
  transition: transform 0.3s;
  transform-origin: left;
}

.role-card:hover { transform: translateY(-6px); border-color: rgba(255,215,0,0.4); }
.role-card:hover::before { transform: scaleX(1); }

.role-icon {
  width: 56px; height: 56px;
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
  background: var(--gold-pale);
  position: relative;
}

.role-icon::after {
  content: '';
  position: absolute;
  top: -1px; right: -1px;
  width: 10px; height: 10px;
  background: var(--gold);
}

.role-tag {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 0.5rem;
}

.role-card h3 {
  font-family: 'Cinzel', serif;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--white);
  margin-bottom: 0.8rem;
}

.role-card p {
  font-size: 0.88rem;
  color: var(--muted);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.role-link {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--gold);
  display: flex;
  align-items: center;
  gap: 6px;
}

.role-link svg {
  transition: transform 0.2s;
}

.role-card:hover .role-link svg { transform: translateX(4px); }

/* card bg number */
.role-bg-num {
  position: absolute;
  bottom: -20px; right: -10px;
  font-family: 'Cinzel', serif;
  font-size: 7rem;
  font-weight: 900;
  color: rgba(255,215,0,0.04);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}

/* ═══════════════════════════════════════════
   VMGO SECTION
═══════════════════════════════════════════ */
.section-vmgo {
  position: relative;
  z-index: 2;
  background: rgba(7, 24, 40, 0.8);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  padding: 5rem 2rem;
  backdrop-filter: blur(8px);
}

.vmgo-inner {
  max-width: 1100px;
  margin: 0 auto;
}

.vmgo-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5px;
  margin-top: 3.5rem;
  border: 1px solid var(--border);
}

.vmgo-item {
  padding: 2rem;
  border-right: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: background 0.3s;
}

.vmgo-item:last-child { border-right: none; }
.vmgo-item:hover { background: rgba(255,215,0,0.04); }

.vmgo-item::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 3px;
  background: var(--gold);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.3s;
}

.vmgo-item:hover::before { transform: scaleX(1); }

.vmgo-letter {
  font-family: 'Cinzel', serif;
  font-size: 3rem;
  font-weight: 900;
  color: rgba(255,215,0,0.12);
  line-height: 1;
  margin-bottom: 0.5rem;
}

.vmgo-title {
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 0.8rem;
}

.vmgo-text {
  font-family: 'Crimson Pro', serif;
  font-size: 0.95rem;
  color: var(--muted);
  line-height: 1.7;
}

/* ═══════════════════════════════════════════
   DEVELOPER CREDIT / FOOTER
═══════════════════════════════════════════ */
footer {
  position: relative;
  z-index: 2;
  background: var(--navy-deep);
  border-top: 1px solid var(--border);
  padding: 2.5rem 2rem;
  text-align: center;
}

.footer-logo {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--gold);
  letter-spacing: 2px;
  margin-bottom: 0.5rem;
}

.footer-credit {
  font-size: 0.8rem;
  color: var(--muted);
  letter-spacing: 1px;
}

.footer-credit strong { color: var(--gold); }

.footer-links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  margin-top: 1.2rem;
}

.footer-links a {
  font-size: 0.72rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--muted);
  text-decoration: none;
  transition: color 0.2s;
}

.footer-links a:hover { color: var(--gold); }

.footer-sep {
  color: var(--border);
  font-size: 0.8rem;
}

/* ═══════════════════════════════════════════
   LIVE TIME BAR
═══════════════════════════════════════════ */
.time-bar {
  position: relative;
  z-index: 2;
  background: rgba(255, 215, 0, 0.06);
  border-bottom: 1px solid var(--border);
  padding: 7px 2.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.72rem;
  letter-spacing: 1.5px;
  color: rgba(255,215,0,0.7);
  font-weight: 500;
}

/* ═══════════════════════════════════════════
   ANIMATIONS
═══════════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes pulse-gold {
  0%, 100% { box-shadow: 0 0 0 0 rgba(255,215,0,0.2); }
  50%       { box-shadow: 0 0 0 12px rgba(255,215,0,0); }
}

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 900px) {
  .roles-grid { grid-template-columns: 1fr; }
  .vmgo-grid  { grid-template-columns: 1fr; }
  .stats-inner { grid-template-columns: repeat(2, 1fr); }
  .stat-item:nth-child(2) { border-right: none; }
  .header-nav { display: none; }
}

@media (max-width: 600px) {
  .stats-inner { grid-template-columns: 1fr; }
  .stat-item   { border-right: none; border-bottom: 1px solid var(--border); }
  .time-bar    { flex-direction: column; gap: 2px; text-align: center; }
}

</style>
</head>
<body>

<!-- STARFIELD CANVAS -->
<canvas id="starfield"></canvas>

<!-- TIME BAR -->
<div class="time-bar" style="margin-top: 0; padding-top: 74px; padding-bottom: 7px; position: relative; z-index: 2;">
  <span>JOSE RIZAL MEMORIAL STATE UNIVERSITY — LEARNING MANAGEMENT SYSTEM</span>
  <span id="liveTime">Loading...</span>
</div>

<!-- HEADER -->
<header>
  <div class="header-brand">
    <div class="header-seal">JRMSU</div>
    <div class="header-title">
      JRMSU LMS
      <span>Jose Rizal Memorial State University</span>
    </div>
  </div>
  <nav class="header-nav">
    <a href="#about">About</a>
    <a href="#roles">Portal</a>
    <a href="#vmgo">VMGO</a>
  </nav>
  <a href="login.php" class="btn-login">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
    Login
  </a>
</header>

<!-- HERO SECTION -->
<section class="hero" id="about">
  <div class="hero-inner">
    <div class="hero-eyebrow">Academic Year 2025–2026</div>
    <h1 class="hero-headline">
      Learning Without
      <span class="gold-text">Boundaries</span>
    </h1>
    <p class="hero-sub">
      The official Learning Management System of Jose Rizal Memorial State University —<br>
      empowering students, instructors, and administrators in one unified platform.
    </p>
    <div class="hero-cta-group">
      <a href="login.php" class="btn-primary-hero">Access Portal</a>
      <a href="#roles" class="btn-ghost-hero">Learn More</a>
    </div>
    <div class="gold-rule">
      <div class="gold-rule-diamond"></div>
    </div>
  </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-number">3</div>
      <div class="stat-label">User Roles</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">RIZAL</div>
      <div class="stat-label">Core Values</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">S.M.A.R.T</div>
      <div class="stat-label">Strategic Goals</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">2025</div>
      <div class="stat-label">Thesis Project</div>
    </div>
  </div>
</div>

<!-- ROLE CARDS SECTION -->
<section class="section-roles" id="roles">
  <div class="section-label">
    <h2>Choose Your Portal</h2>
    <p>Select your role to access the system</p>
  </div>

  <div class="roles-grid">

    <!-- Student -->
    <a href="student_login.php" class="role-card">
      <div class="role-bg-num">01</div>
      <div class="role-icon">🎓</div>
      <div class="role-tag">Student Portal</div>
      <h3>Student</h3>
      <p>View your enrolled courses, track academic progress, submit feedback, and access all learning materials assigned to you.</p>
      <div class="role-link">
        Access Portal
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>

    <!-- Instructor -->
    <a href="instructor_login.php" class="role-card">
      <div class="role-bg-num">02</div>
      <div class="role-icon">📋</div>
      <div class="role-tag">Faculty Portal</div>
      <h3>Instructor</h3>
      <p>Manage your courses, monitor student performance, upload learning materials, and communicate with your class effectively.</p>
      <div class="role-link">
        Access Portal
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>

    <!-- Admin -->
    <a href="admin_login.php" class="role-card">
      <div class="role-bg-num">03</div>
      <div class="role-icon">⚙️</div>
      <div class="role-tag">Admin Control Panel</div>
      <h3>Administrator</h3>
      <p>Manage users, enroll students, assign instructors, reset passwords, and maintain the overall system configuration.</p>
      <div class="role-link">
        Access Portal
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>

  </div>
</section>

<!-- VMGO SECTION -->
<section class="section-vmgo" id="vmgo">
  <div class="vmgo-inner">
    <div class="section-label">
      <h2>University VMGO</h2>
      <p>Vision, Mission, Goals, and Objectives</p>
    </div>

    <div class="vmgo-grid">

      <div class="vmgo-item">
        <div class="vmgo-letter">V</div>
        <div class="vmgo-title">Vision</div>
        <div class="vmgo-text">A Smart UniverCity, locally and globally, that inspires excellence, fosters innovation, and promotes sustainable development for the betterment of society.</div>
      </div>

      <div class="vmgo-item">
        <div class="vmgo-letter">M</div>
        <div class="vmgo-title">Mission</div>
        <div class="vmgo-text">JRMSU pledges to deliver effective and efficient services along instruction, research, extension, and production — fostering adaptable learning through technology and innovation.</div>
      </div>

      <div class="vmgo-item">
        <div class="vmgo-letter">G</div>
        <div class="vmgo-title">Goals</div>
        <div class="vmgo-text"><strong style="color:var(--gold)">S.M.A.R.T:</strong> Strategic Modernization · Maximized Collaboration · Accountable Governance · Resilient Systems · Transformative Inclusivity.</div>
      </div>

      <div class="vmgo-item" style="grid-column: span 1;">
        <div class="vmgo-letter">R</div>
        <div class="vmgo-title">Core Values — RIZAL</div>
        <div class="vmgo-text">
          <strong style="color:var(--gold)">R</strong>esilience &nbsp;·&nbsp;
          <strong style="color:var(--gold)">I</strong>ntegrity &nbsp;·&nbsp;
          <strong style="color:var(--gold)">Z</strong>eal for Excellence &nbsp;·&nbsp;
          <strong style="color:var(--gold)">A</strong>ltruism &nbsp;·&nbsp;
          <strong style="color:var(--gold)">L</strong>eadership
        </div>
      </div>

      <div class="vmgo-item" style="grid-column: span 2;">
        <div class="vmgo-letter">Q</div>
        <div class="vmgo-title">Quality Policy Statement</div>
        <div class="vmgo-text">The Jose Rizal Memorial State University is committed to provide quality instruction, research, extension, and production programs that are relevant and responsive to the needs of the community — ensuring customer satisfaction through continual improvement, ethical standards, and accountability.</div>
      </div>

    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-logo">JRMSU — LMS</div>
  <div class="footer-credit">
    Developed by <strong>Limetares Group</strong> &nbsp;·&nbsp; Thesis Project S.Y. <strong>2025–2026</strong>
  </div>
  <div class="footer-links">
    <a href="login.php">Login</a>
    <span class="footer-sep">|</span>
    <a href="#about">Home</a>
    <span class="footer-sep">|</span>
    <a href="#vmgo">VMGO</a>
    <span class="footer-sep">|</span>
    <a href="#roles">Portal</a>
  </div>
</footer>

<script>
/* ── LIVE TIME ── */
function updateTime() {
  const now = new Date();
  document.getElementById('liveTime').textContent = now.toLocaleString('en-PH', {
    timeZone: 'Asia/Manila',
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
}
setInterval(updateTime, 1000);
updateTime();

/* ── STARFIELD CANVAS ── */
(function () {
  const canvas = document.getElementById('starfield');
  const ctx = canvas.getContext('2d');
  let stars = [];
  const NUM = 160;

  function resize() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
  }

  function init() {
    stars = [];
    for (let i = 0; i < NUM; i++) {
      stars.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        r: Math.random() * 1.2 + 0.2,
        a: Math.random(),
        speed: Math.random() * 0.003 + 0.001,
        drift: (Math.random() - 0.5) * 0.08
      });
    }
  }

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    stars.forEach(s => {
      ctx.beginPath();
      ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(255, 215, 0, ${s.a * 0.6})`;
      ctx.fill();
      s.a += s.speed;
      if (s.a > 1 || s.a < 0) s.speed *= -1;
      s.x += s.drift;
      if (s.x < 0) s.x = canvas.width;
      if (s.x > canvas.width) s.x = 0;
    });
    requestAnimationFrame(draw);
  }

  window.addEventListener('resize', () => { resize(); init(); });
  resize();
  init();
  draw();
})();

/* ── SCROLL REVEAL ── */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.style.opacity = '1';
      e.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.role-card, .vmgo-item, .stat-item').forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(20px)';
  el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
  observer.observe(el);
});
</script>

</body>
</html>
