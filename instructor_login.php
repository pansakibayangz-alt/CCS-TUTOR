<?php
session_start();

// Already logged in → redirect
if (isset($_SESSION['role']) && $_SESSION['role'] === 'INSTRUCTOR') {
    header("Location: instructor_dashboard.php");
    exit;
}

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
        $stmt->execute([$username]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$instructor) {
            // No account — must be added by admin
            $error = 'No account found for this username. Please contact the administrator.';
        } elseif (empty($instructor['password'])) {
            // Account exists but not yet activated
            $error = 'Your account has not been activated yet. Please contact the administrator.';
        } elseif (!password_verify($password, $instructor['password'])) {
            $error = 'Invalid username or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['role']          = 'INSTRUCTOR';
            $_SESSION['username']      = $instructor['username'];
            $_SESSION['instructor_id'] = $instructor['id'];
            $_SESSION['firstname']     = $instructor['firstname'];
            $_SESSION['surname']       = $instructor['surname'];

            header("Location: instructor_dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>CSTUTORHUB — Instructor Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,700;0,900;1,700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ════════════════════════════════════════════
   DESIGN TOKENS
════════════════════════════════════════════ */
:root {
    --navy-deep:   #040D18;
    --navy:        #071A2A;
    --navy-2:      #08304F;
    --navy-mid:    #0B2540;
    --gold:        #FFD700;
    --gold-dim:    #C9A000;
    --gold-soft:   rgba(255,215,0,0.14);
    --muted:       rgba(255,255,255,0.90);
    --subtle:      rgba(255,255,255,0.42);
    --card-bg:     rgba(255,255,255,0.04);
    --glass-bdr:   rgba(255,230,0,0.14);
    --accent:      #2E86C1;
    --accent-soft: rgba(46,134,193,0.18);
    --danger-bg:   rgba(255,87,87,0.10);
    --danger-bdr:  rgba(255,87,87,0.32);
    --radius-card:  20px;
    --radius-input: 10px;
    --ease:         cubic-bezier(0.4,0,0.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

/* BACKGROUND SCENE */
.bg-scene {
    position: fixed; inset: 0; z-index: 0; overflow: hidden;
    background:
        radial-gradient(ellipse 75% 55% at 15%  5%,  rgba(11,61,145,0.25)  0%, transparent 60%),
        radial-gradient(ellipse 55% 50% at 85% 85%,  rgba(46,134,193,0.15) 0%, transparent 55%),
        radial-gradient(ellipse 40% 60% at 50% 50%,  rgba(7,26,42,0.90)    0%, transparent 100%),
        linear-gradient(170deg, #040D18 0%, #071A2A 55%, #0B2540 100%);
}
.bg-scene::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        radial-gradient(1.2px 1.2px at  6%  14%, rgba(255,215,0,0.55)  0%, transparent 100%),
        radial-gradient(1px   1px   at 22%  70%, rgba(255,255,255,0.20) 0%, transparent 100%),
        radial-gradient(1.2px 1.2px at 40%  30%, rgba(255,215,0,0.38)  0%, transparent 100%),
        radial-gradient(1px   1px   at 55%  88%, rgba(46,134,193,0.40) 0%, transparent 100%),
        radial-gradient(1.2px 1.2px at 68%  16%, rgba(255,215,0,0.48)  0%, transparent 100%),
        radial-gradient(1px   1px   at 80%  55%, rgba(255,255,255,0.22) 0%, transparent 100%),
        radial-gradient(1.2px 1.2px at 93%   8%, rgba(46,134,193,0.45) 0%, transparent 100%),
        radial-gradient(1px   1px   at 47%  60%, rgba(255,255,255,0.16) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 14%  92%, rgba(255,215,0,0.32)  0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 74%  42%, rgba(255,215,0,0.40)  0%, transparent 100%),
        radial-gradient(1px   1px   at 29%  48%, rgba(46,134,193,0.28) 0%, transparent 100%),
        radial-gradient(1px   1px   at 61%  24%, rgba(255,215,0,0.26)  0%, transparent 100%);
    animation: twinkle 9s ease-in-out infinite alternate;
}
@keyframes twinkle { from { opacity:.65; } to { opacity:1; } }

.orb { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; animation: orbFloat ease-in-out infinite alternate; }
.orb-1 { width:clamp(240px,38vw,460px); height:clamp(240px,38vw,460px); background:#0B3D91; opacity:.18; top:-14%; left:-10%;  animation-duration:18s; }
.orb-2 { width:clamp(180px,28vw,340px); height:clamp(180px,28vw,340px); background:#2E86C1; opacity:.15; bottom:-8%;  right:-8%; animation-duration:22s; animation-delay:-8s; }
.orb-3 { width:clamp(110px,16vw,200px); height:clamp(110px,16vw,200px); background:#FFD700; opacity:.10; top:38%; left:56%;    animation-duration:14s; animation-delay:-4s; }
@keyframes orbFloat { from { transform:translate(0,0) scale(1); } to { transform:translate(24px,16px) scale(1.08); } }

.scan-line { position: absolute; width: 100%; height: 1px; background: linear-gradient(90deg, transparent, rgba(46,134,193,0.18), rgba(255,215,0,0.12), transparent); top: 0; animation: scan 12s linear infinite; }
@keyframes scan { from { top:-2px; opacity:0; } 5% { opacity:1; } 95% { opacity:1; } to { top:100vh; opacity:0; } }

/* TOPBAR */
.topbar {
    position: relative; z-index: 30;
    background: linear-gradient(90deg, rgba(7,27,42,0.97), rgba(8,48,79,0.97));
    border-bottom: 1px solid rgba(255,215,0,0.07);
    box-shadow: 0 6px 28px rgba(2,8,22,0.55);
    padding: 10px clamp(14px,4vw,40px);
    display: flex; align-items: center; gap: 10px;
}
.topbar-logos { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.topbar-logos img { height: clamp(26px,4vw,36px); width: auto; transition: transform .22s var(--ease); }
.topbar-logos img:hover { transform: scale(1.10); }
.topbar-title {
    font-family: 'Merriweather', serif;
    font-size: clamp(.72rem,1.5vw + .3rem,1.18rem);
    font-weight: 800; letter-spacing: .7px; color: var(--gold);
    text-shadow: 0 0 12px rgba(255,215,0,0.35);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* LIVE DATE BAR */
#liveDateTimeBar {
    position: relative; z-index: 30;
    background: rgba(0,0,0,0.38); backdrop-filter: blur(8px);
    padding: 7px 12px; text-align: center; color: var(--gold);
    font-size: clamp(.68rem,.85vw + .28rem,.82rem); font-weight: 700; letter-spacing: .4px;
    border-bottom: 1px solid rgba(255,215,0,0.20);
}

/* STAGE */
.stage { position: relative; z-index: 10; flex: 1; display: flex; align-items: center; justify-content: center; padding: clamp(20px,5vh,52px) clamp(12px,4vw,24px) clamp(54px,8vh,72px); }

/* LOGIN CARD */
.login-card {
    width: 100%; max-width: 440px; background: var(--card-bg);
    border: 1px solid var(--glass-bdr); border-radius: var(--radius-card);
    padding: clamp(26px,5vw,44px) clamp(20px,5vw,40px) clamp(22px,4vw,36px);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 0 0 1px rgba(255,215,0,0.04),
                0 28px 72px rgba(2,6,18,0.72), 0 6px 20px rgba(2,6,18,0.40);
    animation: cardIn .65s cubic-bezier(.22,.68,0,1.18) both;
}
@keyframes cardIn { from { opacity:0; transform:translateY(30px) scale(.96); } to { opacity:1; transform:translateY(0) scale(1); } }

/* Icon badge */
.role-badge {
    width: 66px; height: 66px; border-radius: 16px;
    background: linear-gradient(145deg, #06213A, #0C3A68);
    border: 1px solid var(--glass-bdr);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.45), 0 0 0 6px var(--accent-soft);
    animation: badgePulse 3.5s ease-in-out infinite;
}
@keyframes badgePulse { 0%,100% { box-shadow: 0 8px 28px rgba(0,0,0,.45), 0 0 0 6px var(--accent-soft); } 50% { box-shadow: 0 8px 28px rgba(0,0,0,.45), 0 0 0 10px rgba(46,134,193,.10); } }
.role-badge svg { width: 30px; height: 30px; }

/* Heading */
.card-heading { text-align: center; margin-bottom: 8px; }
.card-heading h2 {
    font-family: 'Merriweather', serif;
    font-size: clamp(1.12rem,2.5vw + .2rem,1.48rem);
    font-weight: 900; color: var(--gold);
    text-shadow: 0 0 16px rgba(255,215,0,0.25); letter-spacing: .4px; line-height: 1.25;
}
.card-heading p { margin-top: 7px; font-size: clamp(.70rem,.8vw + .28rem,.78rem); color: var(--subtle); letter-spacing: .2px; line-height: 1.5; }

/* Role chip */
.role-chip {
    display: inline-flex; align-items: center; gap: 6px; margin: 10px auto 0;
    padding: 4px 12px; border-radius: 20px; background: var(--accent-soft);
    border: 1px solid rgba(46,134,193,0.28); font-size: .70rem; font-weight: 600;
    letter-spacing: .6px; text-transform: uppercase; color: #74C6EF;
}
.role-chip::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #2E86C1; box-shadow: 0 0 6px rgba(46,134,193,0.70); }

/* Thin divider */
.divider-line { height: 1px; background: linear-gradient(90deg, transparent, var(--glass-bdr), transparent); margin: 20px 0; }

/* FORM */
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: .70rem; font-weight: 600; text-transform: uppercase; letter-spacing: .9px; color: var(--gold-dim); margin-bottom: 6px; transition: color .2s var(--ease); }
.field-group:focus-within label { color: var(--gold); }

.input-wrap { position: relative; }
.input-wrap .ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); opacity: .45; pointer-events: none; display: flex; transition: opacity .2s; }
.field-group:focus-within .ico { opacity: .88; }
.input-wrap .ico svg { width: 16px; height: 16px; }

.input-wrap input {
    width: 100%; padding: 12px 42px 12px 40px; border-radius: var(--radius-input);
    border: 1px solid rgba(255,215,0,0.11); background: rgba(255,255,255,0.04);
    color: var(--muted); font-family: 'Poppins', sans-serif; font-size: max(.88rem,16px);
    outline: none; -webkit-appearance: none;
    transition: border-color .22s var(--ease), box-shadow .22s var(--ease), background .22s var(--ease);
}
.input-wrap input::placeholder { color: rgba(255,255,255,0.22); font-size: .84rem; }
.input-wrap input:focus {
    border-color: rgba(46,134,193,0.60);
    background: rgba(46,134,193,0.04);
    box-shadow: 0 0 0 3px rgba(46,134,193,0.12), 0 2px 10px rgba(0,0,0,0.20);
}
.input-wrap input:-webkit-autofill,
.input-wrap input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 50px #071A2A inset;
    -webkit-text-fill-color: rgba(255,255,255,0.88);
}

/* Toggle password */
.toggle-pw { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; opacity: .40; padding: 5px; display: flex; color: var(--muted); border-radius: 6px; transition: opacity .2s; -webkit-tap-highlight-color: transparent; }
.toggle-pw:hover { opacity: .88; }
.toggle-pw svg { width: 16px; height: 16px; }

/* ALERTS */
.alert { display: flex; align-items: flex-start; gap: 9px; border-radius: 9px; padding: 11px 13px; margin-bottom: 18px; font-size: clamp(.76rem,.8vw + .28rem,.82rem); line-height: 1.45; }
.alert svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 2px; }
.alert-error { background: var(--danger-bg); border: 1px solid var(--danger-bdr); color: #FF9090; animation: alertSlide .28s var(--ease) both, shake .40s cubic-bezier(.36,.07,.19,.97) both; }
@keyframes alertSlide { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
@keyframes shake { 10%,90%{transform:translateX(-3px)} 20%,80%{transform:translateX(3px)} 30%,50%,70%{transform:translateX(-4px)} 40%,60%{transform:translateX(4px)} }

/* SUBMIT BUTTON */
.btn-login {
    width: 100%; margin-top: 6px; padding: 13px 20px; border: none; border-radius: var(--radius-input);
    background: linear-gradient(100deg, #2E86C1 0%, #1A6FA3 55%, #155E8E 100%);
    color: #fff; font-family: 'Poppins', sans-serif;
    font-size: clamp(.86rem,.9vw + .4rem,.94rem); font-weight: 700; letter-spacing: .5px;
    cursor: pointer; position: relative; overflow: hidden;
    transition: transform .20s var(--ease), box-shadow .20s var(--ease);
    box-shadow: 0 6px 22px rgba(46,134,193,0.30), 0 2px 6px rgba(0,0,0,0.25);
    -webkit-tap-highlight-color: transparent;
}
.btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(46,134,193,0.38), 0 3px 10px rgba(0,0,0,0.25); }
.btn-login:active { transform: translateY(1px); }

/* Spinner */
.spinner { display: none; width: 17px; height: 17px; border: 2px solid rgba(255,255,255,0.30); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; margin: 0 auto; vertical-align: middle; }
.btn-login.loading .btn-text { display: none; }
.btn-login.loading .spinner  { display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }

/* LINKS */
.card-links { margin-top: 22px; display: flex; justify-content: center; gap: 18px; flex-wrap: wrap; font-size: clamp(.68rem,.75vw + .28rem,.75rem); color: var(--subtle); }
.card-links a { color: var(--gold); text-decoration: none; font-weight: 600; transition: color .2s; }
.card-links a:hover { color: #fff; }

/* NOTICE BOX */
.notice-box {
    background: rgba(46,134,193,0.08);
    border: 1px solid rgba(46,134,193,0.25);
    border-radius: 9px;
    padding: 10px 14px;
    font-size: .76rem;
    color: rgba(255,255,255,0.65);
    margin-top: 14px;
    text-align: center;
    line-height: 1.5;
}

/* FOOTER */
.footer { position: fixed; bottom: 0; left: 0; width: 100%; z-index: 30; background: linear-gradient(90deg, rgba(7,27,42,0.97), rgba(8,48,79,0.97)); border-top: 1px solid rgba(255,215,0,0.07); padding: 8px 16px; text-align: center; font-size: clamp(.66rem,.72vw + .28rem,.75rem); color: rgba(255,255,255,0.48); }

/* RESPONSIVE */
@media (max-width: 600px) {
    .stage { align-items: flex-end; padding: 0; }
    .login-card { max-width:100%; border-radius:22px 22px 0 0; border-left:none; border-right:none; border-bottom:none; box-shadow:0 -6px 40px rgba(2,6,18,0.60); padding:26px 20px 28px; }
    .role-badge { width:56px; height:56px; border-radius:13px; margin-bottom:14px; }
    .role-badge svg { width:25px; height:25px; }
}
@media (max-width: 360px) { .topbar-title { display:none; } }
@media (max-height: 500px) and (orientation: landscape) {
    .role-badge { width:46px; height:46px; margin-bottom:10px; }
    .card-heading { margin-bottom:8px; }
    .divider-line { margin:12px 0; }
    .field-group { margin-bottom:10px; }
    .stage { align-items:center; padding:8px 16px 52px; }
    .login-card { border-radius:14px; max-width:400px; padding:16px 22px 14px; }
}
</style>
</head>
<body>

<!-- BACKGROUND -->
<div class="bg-scene">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="scan-line"></div>
</div>

<!-- TOPBAR -->
<nav class="topbar" role="banner">
    <div class="topbar-logos">
        <img src="jrmsu.png" alt="JRMSU Logo">
        <img src="ccs.png"   alt="CCS Logo">
    </div>
    <span class="topbar-title">CSTUTORHUB — INSTRUCTOR PORTAL</span>
</nav>

<!-- DATE BAR -->
<div id="liveDateTimeBar" aria-live="polite">Loading…</div>

<!-- MAIN -->
<main class="stage" role="main">
    <div class="login-card" role="dialog" aria-labelledby="loginTitle">

        <!-- Icon -->
        <div class="role-badge" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#2E86C1" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>

        <!-- Heading -->
        <div class="card-heading">
            <h2 id="loginTitle">Instructor Login</h2>
            <p>Welcome back! Sign in to manage your lessons,<br>assessments and student progress.</p>
            <span class="role-chip">Instructor Portal</span>
        </div>

        <div class="divider-line"></div>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8"  x2="12"   y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form id="loginForm" method="POST" action="" autocomplete="off" novalidate>

            <!-- Username -->
            <div class="field-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username"
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autofocus autocomplete="username" required aria-required="true">
                </div>
            </div>

            <!-- Password -->
            <div class="field-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password"
                           autocomplete="current-password" required aria-required="true">
                    <button type="button" class="toggle-pw" id="togglePw"
                            aria-label="Show password" title="Toggle password visibility">
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-login" id="submitBtn">
                <span class="btn-text">Sign In</span>
                <span class="spinner" aria-hidden="true"></span>
            </button>

        </form>

        <!-- Notice: no self-registration -->
        <div class="notice-box">
            🔒 Instructor accounts are managed by the administrator.<br>
            Contact your admin if you do not have access.
        </div>

        <!-- Links -->
        <div class="card-links">
            <a href="login.php">← Main Login</a>
            <span style="color:rgba(255,255,255,0.18)">|</span>
            <a href="student_login.php">Student Portal</a>
        </div>

    </div>
</main>

<!-- FOOTER -->
<footer class="footer">
    Developed by <strong>&nbsp;Limetares's Group&nbsp;</strong> — Thesis S.Y.&nbsp;2025–2026
</footer>

<!-- SCRIPTS -->
<script>
/* Live clock — Manila time */
(function tick() {
    document.getElementById('liveDateTimeBar').textContent =
        new Date().toLocaleString('en-PH', {
            timeZone:  'Asia/Manila',
            dateStyle: 'full',
            timeStyle: 'medium'
        });
    setTimeout(tick, 1000);
})();

/* Toggle password visibility */
const toggleBtn = document.getElementById('togglePw');
const pwInput   = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');
const SVG_EYE_OPEN = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
const SVG_EYE_OFF  = `<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a18.09 18.09 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.1 18.1 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

toggleBtn.addEventListener('click', () => {
    const isHidden = pwInput.type === 'password';
    pwInput.type            = isHidden ? 'text' : 'password';
    eyeIcon.innerHTML       = isHidden ? SVG_EYE_OFF : SVG_EYE_OPEN;
    toggleBtn.style.opacity = isHidden ? '.80' : '.40';
    toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
});

/* Loading state on submit */
document.getElementById('loginForm').addEventListener('submit', () => {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.disabled = true;
    setTimeout(() => { btn.classList.remove('loading'); btn.disabled = false; }, 8000);
});
</script>

</body>
</html>