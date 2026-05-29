<?php
session_start();

// Redirect if already logged in as admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
    header("Location: admin_dashboard.php");
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
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['role']     = 'ADMIN';
            $_SESSION['username'] = $admin['username'];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CSTUTORHUB — Admin Login</title>

<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ── ROOT TOKENS ─────────────────────────────────────────── */
:root {
    --navy-deep:  #040E1A;
    --navy:       #071A2A;
    --navy-mid:   #0B2540;
    --navy-light: #08304F;
    --gold:       #FFD700;
    --gold-dim:   #C9A000;
    --gold-glow:  rgba(255,215,0,0.18);
    --muted:      rgba(255,255,255,0.88);
    --subtle:     rgba(255,255,255,0.45);
    --glass-bg:   rgba(255,255,255,0.035);
    --glass-bdr:  rgba(255,215,0,0.14);
    --danger:     #FF5A5A;
}

/* ── RESET / BASE ────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Poppins', sans-serif;
    background: var(--navy-deep);
    color: var(--muted);
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ── ANIMATED BACKGROUND ─────────────────────────────────── */
.bg-layer {
    position: fixed;
    inset: 0;
    z-index: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 10%,  rgba(11,61,145,0.30) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 80%,  rgba(139,0,0,0.18) 0%,  transparent 55%),
        radial-gradient(ellipse 50% 70% at 55% 50%,  rgba(7,26,42,0.95)  0%, transparent 100%),
        linear-gradient(180deg, #040E1A 0%, #071A2A 60%, #0B2540 100%);
}

/* star-field dots */
.bg-layer::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(1px 1px at  8%  12%, rgba(255,215,0,0.55) 0%, transparent 100%),
        radial-gradient(1px 1px at 18%  72%, rgba(255,255,255,0.20) 0%, transparent 100%),
        radial-gradient(1px 1px at 35%  38%, rgba(255,215,0,0.35) 0%, transparent 100%),
        radial-gradient(1px 1px at 52%  85%, rgba(255,255,255,0.15) 0%, transparent 100%),
        radial-gradient(1px 1px at 65%  20%, rgba(255,215,0,0.45) 0%, transparent 100%),
        radial-gradient(1px 1px at 78%  55%, rgba(255,255,255,0.22) 0%, transparent 100%),
        radial-gradient(1px 1px at 90%   8%, rgba(255,215,0,0.50) 0%, transparent 100%),
        radial-gradient(1px 1px at 44%  60%, rgba(255,255,255,0.18) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 25% 92%, rgba(255,215,0,0.30) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 70%  40%, rgba(255,215,0,0.40) 0%, transparent 100%);
}

/* floating glow orbs */
.orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(72px);
    opacity: 0.22;
    pointer-events: none;
    z-index: 0;
    animation: drift 14s ease-in-out infinite alternate;
}
.orb-1 { width:420px; height:420px; background:#FFD700; top:-120px; left:-100px; animation-delay: 0s;   }
.orb-2 { width:320px; height:320px; background:#0B3D91; bottom:-60px; right:-80px; animation-delay: -6s; }
.orb-3 { width:200px; height:200px; background:#8B0000; top:40%;  left:60%; animation-delay: -3s; }

@keyframes drift {
    from { transform: translate(0,0) scale(1);    }
    to   { transform: translate(30px,20px) scale(1.08); }
}

/* ── NAVBAR ──────────────────────────────────────────────── */
.topbar {
    position: relative;
    z-index: 10;
    background: linear-gradient(90deg, #071B2A, #08304F);
    border-bottom: 1px solid rgba(255,215,0,0.08);
    box-shadow: 0 8px 28px rgba(2,10,22,0.55);
    padding: 12px 32px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.topbar img { height: 36px; width: auto; }
.topbar-title {
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: var(--gold);
    text-shadow: 0 0 10px rgba(255,215,0,0.40);
}

/* ── LIVE TIME BAR ───────────────────────────────────────── */
#liveDateTimeBar {
    position: relative;
    z-index: 10;
    width: 100%;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(6px);
    padding: 9px 0;
    text-align: center;
    color: var(--gold);
    font-weight: 700;
    font-size: .82rem;
    letter-spacing: .5px;
    border-bottom: 1px solid rgba(255,215,0,0.20);
}

/* ── MAIN STAGE ──────────────────────────────────────────── */
.stage {
    position: relative;
    z-index: 5;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
}

/* ── LOGIN CARD ──────────────────────────────────────────── */
.login-card {
    width: 100%;
    max-width: 420px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-bdr);
    border-radius: 18px;
    padding: 40px 36px 36px;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.05),
        0 0 0 1px rgba(255,215,0,0.04),
        0 24px 64px rgba(2,8,23,0.65);
    backdrop-filter: blur(18px);
    animation: cardIn .6s cubic-bezier(.22,.68,0,1.2) both;
}

@keyframes cardIn {
    from { opacity:0; transform: translateY(28px) scale(.97); }
    to   { opacity:1; transform: translateY(0)    scale(1);   }
}

/* lock icon badge */
.lock-badge {
    width: 62px; height: 62px;
    border-radius: 14px;
    background: linear-gradient(135deg, #08273E, #0B3B66);
    border: 1px solid var(--glass-bdr);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.40), 0 0 0 6px rgba(255,215,0,0.06);
}
.lock-badge svg { width: 28px; height: 28px; }

.card-heading {
    text-align: center;
    margin-bottom: 28px;
}
.card-heading h2 {
    font-family: 'Merriweather', serif;
    font-size: 1.45rem;
    font-weight: 900;
    color: var(--gold);
    text-shadow: 0 0 12px rgba(255,215,0,0.30);
    letter-spacing: .5px;
    line-height: 1.2;
}
.card-heading p {
    margin-top: 6px;
    font-size: .80rem;
    color: var(--subtle);
    letter-spacing: .3px;
}

/* ── FORM FIELDS ─────────────────────────────────────────── */
.field-group { margin-bottom: 18px; }

.field-group label {
    display: block;
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--gold-dim);
    margin-bottom: 7px;
}

.input-wrap {
    position: relative;
}
.input-wrap .icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    opacity: .55;
    pointer-events: none;
    display: flex;
}
.input-wrap .icon svg { width: 17px; height: 17px; }

.input-wrap input {
    width: 100%;
    padding: 11px 14px 11px 40px;
    border-radius: 9px;
    border: 1px solid rgba(255,215,0,0.13);
    background: rgba(255,255,255,0.04);
    color: var(--muted);
    font-family: 'Poppins', sans-serif;
    font-size: .88rem;
    transition: border-color .22s, box-shadow .22s, background .22s;
    outline: none;
    -webkit-appearance: none;
}
.input-wrap input::placeholder { color: rgba(255,255,255,0.25); }

.input-wrap input:focus {
    border-color: var(--gold);
    background: rgba(255,215,0,0.04);
    box-shadow: 0 0 0 3px rgba(255,215,0,0.10);
}

/* toggle password button */
.toggle-pw {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    opacity: .45;
    padding: 4px;
    transition: opacity .2s;
    display: flex;
    color: var(--muted);
}
.toggle-pw:hover { opacity: .85; }
.toggle-pw svg { width: 17px; height: 17px; }

/* ── ERROR ALERT ─────────────────────────────────────────── */
.error-alert {
    display: flex;
    align-items: center;
    gap: 9px;
    background: rgba(255,90,90,0.10);
    border: 1px solid rgba(255,90,90,0.30);
    border-radius: 8px;
    padding: 10px 13px;
    margin-bottom: 18px;
    font-size: .82rem;
    color: #FF8A8A;
    animation: shake .38s cubic-bezier(.36,.07,.19,.97) both;
}
.error-alert svg { width: 15px; height: 15px; flex-shrink: 0; }

@keyframes shake {
    10%, 90%  { transform: translateX(-2px); }
    20%, 80%  { transform: translateX(3px);  }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60%  { transform: translateX(4px);  }
}

/* ── SUBMIT BUTTON ───────────────────────────────────────── */
.btn-login {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 9px;
    background: linear-gradient(90deg, var(--gold), var(--gold-dim));
    color: #071A2A;
    font-family: 'Poppins', sans-serif;
    font-size: .92rem;
    font-weight: 700;
    letter-spacing: .4px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: transform .18s, box-shadow .18s, filter .18s;
    box-shadow: 0 6px 20px rgba(255,215,0,0.22);
    margin-top: 4px;
}
.btn-login::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0.22), transparent 60%);
    opacity: 0;
    transition: opacity .22s;
}
.btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(255,215,0,0.32); }
.btn-login:hover::before { opacity: 1; }
.btn-login:active { transform: translateY(0); filter: brightness(.93); }

/* divider */
.divider {
    margin: 24px 0 0;
    text-align: center;
    font-size: .73rem;
    color: var(--subtle);
    letter-spacing: .3px;
}
.divider a {
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
}
.divider a:hover { text-decoration: underline; }

/* ── FOOTER ──────────────────────────────────────────────── */
.footer-fixed {
    position: fixed;
    bottom: 0; left: 0; width: 100%;
    background: linear-gradient(90deg, #071B2A, #08304F);
    border-top: 1px solid rgba(255,215,0,0.06);
    padding: 9px 18px;
    text-align: center;
    font-size: .76rem;
    color: rgba(255,255,255,0.55);
    z-index: 20;
}
</style>
</head>
<body>

<!-- BG LAYERS -->
<div class="bg-layer"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<!-- TOPBAR -->
<nav class="topbar">
    <img src="jrmsu.png" alt="JRMSU Logo">
    <img src="ccs.png"   alt="CCS Logo">
    <span class="topbar-title">CSTUTORHUB — ADMIN</span>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date &amp; time...</div>

<!-- MAIN -->
<main class="stage">
    <div class="login-card">

        <!-- LOCK ICON -->
        <div class="lock-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="#FFD700" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        <!-- HEADING -->
        <div class="card-heading">
            <h2>Administrator Login</h2>
            <p>Authorized personnel only. Please sign in to continue.</p>
        </div>

        <!-- ERROR -->
        <?php if ($error): ?>
        <div class="error-alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- FORM -->
        <form method="POST" action="" autocomplete="off" novalidate>

            <!-- USERNAME -->
            <div class="field-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter admin username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autofocus
                        required
                    >
                </div>
            </div>

            <!-- PASSWORD -->
            <div class="field-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="toggle-pw" id="togglePw" title="Show/hide password">
                        <!-- eye icon -->
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In as Administrator</button>

        </form>

        <p class="divider">Not an admin? <a href="login.php">Return to main login</a></p>

    </div>
</main>

<!-- FOOTER -->
<footer class="footer-fixed">
    Developed by <strong>&nbsp;Limetares's Group&nbsp;</strong> — Thesis S.Y. 2025–2026
</footer>

<script>
// Live date & time
function updateDateTime() {
    document.getElementById("liveDateTimeBar").textContent =
        new Date().toLocaleString("en-PH", {
            timeZone:  "Asia/Manila",
            dateStyle: "full",
            timeStyle: "medium"
        });
}
setInterval(updateDateTime, 1000);
updateDateTime();

// Toggle password visibility
const toggleBtn = document.getElementById('togglePw');
const pwInput   = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');

const eyeOpen = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
const eyeOff  = `<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a18.09 18.09 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.1 18.1 0 0 1-2.16 3.19M1 1l22 22"/>`;

toggleBtn.addEventListener('click', () => {
    const isPassword = pwInput.type === 'password';
    pwInput.type   = isPassword ? 'text' : 'password';
    eyeIcon.innerHTML = isPassword ? eyeOff : eyeOpen;
    toggleBtn.style.opacity = isPassword ? '.80' : '.45';
});
</script>

</body>
</html>
