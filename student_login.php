<?php
session_start();

// Already logged in → redirect
if (isset($_SESSION['role']) && $_SESSION['role'] === 'STUDENT') {
    header("Location: student_dashboard.php");
    exit;
}

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = trim($_POST['school_id'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($school_id === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            // No account found at all — may not have been added by admin yet
            $error = 'No account found for this School ID. Please contact your administrator.';
        } elseif (empty($student['password'])) {
            // Account exists but has no password — admin hasn't activated it
            $error = 'Your account has not been activated yet. Please contact your administrator.';
        } elseif (!password_verify($password, $student['password'])) {
            $error = 'Invalid School ID or password.';
        } else {
            // Successful login
            session_regenerate_id(true);
            $_SESSION['role']       = 'STUDENT';
            $_SESSION['school_id']  = $student['school_id'];
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['firstname']  = $student['firstname'];
            $_SESSION['surname']    = $student['surname'];

            header("Location: student_dashboard.php");
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
<title>CSTUTORHUB — Student Login</title>
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
    --accent:      #F5B041;
    --accent-soft: rgba(245,176,65,0.18);
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
        radial-gradient(ellipse 55% 50% at 85% 85%,  rgba(245,176,65,0.15) 0%, transparent 55%),
        radial-gradient(ellipse 40% 60% at 50% 50%,  rgba(7,26,42,0.90)    0%, transparent 100%),
        linear-gradient(170deg, #040D18 0%, #071A2A 55%, #0B2540 100%);
}
.bg-scene::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        radial-gradient(1.2px 1.2px at  6%  14%, rgba(255,215,0,0.55)  0%, transparent 100%),
        radial-gradient(1px   1px   at 22%  70%, rgba(255,255,255,0.20) 0%, transparent 100%),
        radial-gradient(1.2px 1.2px at 40%  30%, rgba(255,215,0,0.38)  0%, transparent 100%),
        radial-gradient(1px   1px   at 55%  88%, rgba(245,176,65,0.40) 0%, transparent 100%);
    animation: twinkle 9s ease-in-out infinite alternate;
}
@keyframes twinkle { from { opacity:.65; } to { opacity:1; } }

.orb { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; animation: orbFloat ease-in-out infinite alternate; }
.orb-1 { width:clamp(240px,38vw,460px); height:clamp(240px,38vw,460px); background:#0B3D91; opacity:.18; top:-14%; left:-10%;  animation-duration:18s; }
.orb-2 { width:clamp(180px,28vw,340px); height:clamp(180px,28vw,340px); background:#F5B041; opacity:.12; bottom:-8%; right:-8%; animation-duration:22s; animation-delay:-8s; }
@keyframes orbFloat { from { transform:translate(0,0) scale(1); } to { transform:translate(24px,16px) scale(1.08); } }

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
    font-weight: 800;
    color: var(--gold);
    text-shadow: 0 0 12px rgba(255,215,0,0.35);
}

/* LIVE DATE BAR */
#liveDateTimeBar {
    position: relative; z-index: 30;
    background: rgba(0,0,0,0.38); backdrop-filter: blur(8px);
    padding: 7px 12px; text-align: center; color: var(--gold);
    font-size: clamp(.68rem,.85vw + .28rem,.82rem); font-weight: 700;
    border-bottom: 1px solid rgba(255,215,0,0.20);
}
.stage { position: relative; z-index: 10; flex: 1; display: flex; align-items: center; justify-content: center; padding: clamp(20px,5vh,52px) clamp(12px,4vw,24px) clamp(54px,8vh,72px); }

/* LOGIN CARD */
.login-card {
    width: 100%; max-width: 440px; background: var(--card-bg);
    border: 1px solid var(--glass-bdr); border-radius: var(--radius-card);
    padding: clamp(26px,5vw,44px) clamp(20px,5vw,40px) clamp(22px,4vw,36px);
    backdrop-filter: blur(20px);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 28px 72px rgba(2,6,18,0.72);
    animation: cardIn .65s cubic-bezier(.22,.68,0,1.18) both;
}
@keyframes cardIn { from { opacity:0; transform: translateY(30px) scale(.96); } to { opacity:1; transform: translateY(0) scale(1); } }

.role-badge {
    width: 66px; height: 66px; border-radius: 16px;
    background: linear-gradient(145deg, #06213A, #0C3A68); border: 1px solid var(--glass-bdr);
    display: flex; align-items: center; justify-content: center; margin: 0 auto 18px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.45), 0 0 0 6px var(--accent-soft);
}
.role-badge svg { width: 30px; height: 30px; stroke: #F5B041; }

.card-heading { text-align: center; margin-bottom: 8px; }
.card-heading h2 { font-family: 'Merriweather', serif; font-size: clamp(1.12rem,2.5vw + .2rem,1.48rem); font-weight: 900; color: var(--gold); }
.card-heading p { margin-top: 7px; font-size: clamp(.70rem,.8vw + .28rem,.78rem); color: var(--subtle); }

.role-chip {
    display: inline-flex; align-items: center; gap: 6px; margin: 10px auto 0;
    padding: 4px 12px; border-radius: 20px; background: var(--accent-soft);
    border: 1px solid rgba(245,176,65,0.28); font-size: .70rem; font-weight: 600; color: #F8C471;
}
.role-chip::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #F5B041; }

.divider-line { height: 1px; background: linear-gradient(90deg, transparent, var(--glass-bdr), transparent); margin: 20px 0; }

/* FORM ELEMENTS */
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: .70rem; font-weight: 600; color: var(--gold-dim); margin-bottom: 6px; }
.input-wrap { position: relative; }
.input-wrap .ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); opacity: .45; display: flex; }
.input-wrap .ico svg { width: 16px; height: 16px; }

.input-wrap input {
    width: 100%; padding: 12px 42px 12px 40px; border-radius: var(--radius-input);
    border: 1px solid rgba(255,215,0,0.11); background: rgba(255,255,255,0.04);
    color: var(--muted); font-family: 'Poppins', sans-serif; font-size: max(.88rem,16px); outline: none;
    -webkit-appearance: none; transition: border-color .22s var(--ease), box-shadow .22s var(--ease);
}
.input-wrap input::placeholder { color: rgba(255,255,255,0.22); font-size: .84rem; }
.input-wrap input:focus {
    border-color: rgba(245,176,65,0.60);
    box-shadow: 0 0 0 3px rgba(245,176,65,0.12);
}

/* Autofill override */
.input-wrap input:-webkit-autofill,
.input-wrap input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 50px #071A2A inset;
    -webkit-text-fill-color: rgba(255,255,255,0.88);
}

.toggle-pw { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); opacity: .40; padding: 5px; display: flex; border-radius: 6px; transition: opacity .2s; }
.toggle-pw:hover { opacity: .88; }
.toggle-pw svg { width: 16px; height: 16px; }

/* ALERTS */
.alert { display: flex; gap: 9px; border-radius: 9px; padding: 11px 13px; margin-bottom: 18px; font-size: .82rem; line-height: 1.45; align-items: flex-start; }
.alert svg { width: 15px; height: 15px; margin-top: 2px; flex-shrink: 0; }
.alert-error { background: var(--danger-bg); border: 1px solid var(--danger-bdr); color: #FF9090; animation: alertSlide .28s var(--ease) both, shake .40s cubic-bezier(.36,.07,.19,.97) both; }
@keyframes alertSlide { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
@keyframes shake { 10%,90%{transform:translateX(-3px)} 20%,80%{transform:translateX(3px)} 30%,50%,70%{transform:translateX(-4px)} 40%,60%{transform:translateX(4px)} }

/* SUBMIT BUTTON */
.btn-login {
    width: 100%; margin-top: 6px; padding: 13px 20px; border: none; border-radius: var(--radius-input);
    background: linear-gradient(100deg, #D4AC0D 0%, #B9770E 100%); color: #fff; font-weight: 700;
    cursor: pointer; transition: transform .20s, box-shadow .20s; font-family:'Poppins',sans-serif;
    font-size: clamp(.86rem,.9vw + .4rem,.94rem); -webkit-tap-highlight-color: transparent;
}
.btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(212,172,13,0.38); }
.btn-login:active { transform: translateY(1px); }

/* CARD LINKS */
.card-links { margin-top: 22px; display: flex; justify-content: center; gap: 18px; font-size: .75rem; color: var(--subtle); flex-wrap: wrap; }
.card-links a { color: var(--gold); text-decoration: none; font-weight: 600; }
.card-links a:hover { color: #fff; }

/* FOOTER */
.footer { position: fixed; bottom: 0; width: 100%; background: rgba(7,27,42,0.97); padding: 8px 16px; text-align: center; font-size: .75rem; color: rgba(255,255,255,0.48); z-index: 30; border-top: 1px solid rgba(255,215,0,0.07); }

/* Mobile */
@media (max-width: 600px) {
    .stage { align-items: flex-end; padding: 0; }
    .login-card { max-width:100%; border-radius:22px 22px 0 0; border-left:none; border-right:none; border-bottom:none; padding:26px 20px 28px; }
}
</style>
</head>
<body>

<div class="bg-scene">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
</div>

<nav class="topbar">
    <div class="topbar-logos">
        <img src="jrmsu.png" alt="JRMSU Logo">
        <img src="ccs.png"   alt="CCS Logo">
    </div>
    <span class="topbar-title">CSTUTORHUB — STUDENT PORTAL</span>
</nav>

<div id="liveDateTimeBar">Loading…</div>

<main class="stage">
    <div class="login-card">

        <div class="role-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/>
                <path d="M12 14c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5z"/>
            </svg>
        </div>

        <div class="card-heading">
            <h2>Student Login</h2>
            <p>Welcome back! Sign in to view your courses,<br>lessons and track your progress.</p>
            <span class="role-chip">Student Portal</span>
        </div>

        <div class="divider-line"></div>

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

        <form method="POST" action="" autocomplete="off">

            <div class="field-group">
                <label for="school_id">School ID</label>
                <div class="input-wrap">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </span>
                    <input type="text" id="school_id" name="school_id"
                           placeholder="Enter your School ID"
                           value="<?= htmlspecialchars($_POST['school_id'] ?? '') ?>"
                           required autofocus autocomplete="username">
                </div>
            </div>

            <div class="field-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password"
                           required autocomplete="current-password">
                    <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="card-links">
            <a href="login.php">← Main Login</a>
            <span style="color:rgba(255,255,255,0.18)">|</span>
            <a href="instructor_login.php">Instructor Portal</a>
        </div>

    </div>
</main>

<footer class="footer">
    Developed by <strong>&nbsp;Limetares's Group&nbsp;</strong> — Thesis S.Y.&nbsp;2025–2026
</footer>

<script>
(function tick() {
    document.getElementById('liveDateTimeBar').textContent =
        new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'full', timeStyle: 'medium' });
    setTimeout(tick, 1000);
})();

const toggleBtn = document.getElementById('togglePw');
const pwInput   = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');
const EYE_OPEN  = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
const EYE_OFF   = `<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a18.09 18.09 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.1 18.1 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

toggleBtn.addEventListener('click', () => {
    const hidden = pwInput.type === 'password';
    pwInput.type         = hidden ? 'text' : 'password';
    eyeIcon.innerHTML    = hidden ? EYE_OFF : EYE_OPEN;
    toggleBtn.style.opacity = hidden ? '.80' : '.40';
    toggleBtn.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
});
</script>

</body>
</html>