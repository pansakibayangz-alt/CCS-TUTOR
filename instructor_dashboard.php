<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

// Fetch instructor info
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

// Gradient colors for each category
$gradients = [
    'CORE VALUES' => 'linear-gradient(135deg, #8B0000, #FFD700)',       // deep red -> gold accent
    'VISION' => 'linear-gradient(135deg, #0B3D91, #2E86C1)',           // navy -> blue
    'MISSION' => 'linear-gradient(135deg, #6A1B9A, #D4A0F7)',          // purple -> soft
    'GOALS' => 'linear-gradient(135deg, #145A32, #7BF1A8)',            // dark green -> mint
    'QUALITY POLICY STATEMENT' => 'linear-gradient(135deg, #3A0CA3, #FFD700)' // purple -> gold
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Instructor Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,700;1,700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>

/* ---------------------------
   UNIVERSITY STYLE (NAVY + YELLOW)
   --------------------------- */

:root{
    --navy: #0b2b4a;
    --navy-2: #08304f;

    /* UPDATED: GOLD → JRMSU YELLOW */
    --gold: #FFD700;
    --muted: rgba(255,255,255,0.9);

    --card-bg: rgba(255,255,255,0.04);
    --glass-border: rgba(255,230,0,0.14); /* updated */
}

/* Reset & body */
*{box-sizing:border-box}
html,body{height:100%}
body{
    margin:0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
}

/* Top navbar */
.navbar-custom{
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
.navbar-brand{
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    letter-spacing: 0.6px;
    font-weight:700;
}
.navbar-custom .nav-link{
    color: rgba(255,255,255,0.9);
    font-weight:600;
    text-transform:uppercase;
    font-size:0.83rem;
}
.navbar-custom .nav-link:hover{
    color: var(--gold);
    text-decoration:underline;
}

/* container & layout */
.container-main{
    max-width:1100px;
    margin: 36px auto 96px;
    padding: 0 18px;
}

/* Top info */
.top-panel{
    display:flex;
    gap:18px;
    align-items:center;
    margin-bottom:18px;
}

/* instructor card */
.profile-card{
    display:flex;
    gap:14px;
    align-items:center;
    background: var(--card-bg);
    border: 1px solid var(--glass-border);
    padding:18px;
    border-radius:12px;
    width:100%;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 6px 24px rgba(2,8,23,0.45);
    backdrop-filter: blur(6px) saturate(120%);
}
.profile-avatar{
    width:78px;
    height:78px;
    border-radius:10px;
    background: linear-gradient(135deg,#08273e,#0b3b66);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    color:var(--gold);
    font-family: 'Merriweather', serif;
    font-size:22px;
    border:1px solid rgba(255,215,0,0.12);
    box-shadow: 0 6px 18px rgba(2,8,23,0.6);
}
.profile-meta h5{
    margin:0;
    font-family:'Merriweather', serif;
    font-size:1.06rem;
    color:var(--gold);
    letter-spacing:0.3px;
}
.profile-meta p{
    margin:0;
    font-size:0.9rem;
    color:rgba(255,255,255,0.85);
}

/* VMGO heading */
.section-title{
    margin: 28px 0 12px;
    font-family: 'Merriweather', serif;
    font-size: 1.1rem;
    color: #fff;
    letter-spacing:0.4px;
}

/* Accordion tweaks */
.accordion .accordion-item{
    background: transparent;
    border: none;
    margin-bottom: 12px;
}
.accordion-button{
    border-radius:10px;
    padding:16px 18px;
    color:#071A2A;
    font-weight:700;
    background: linear-gradient(180deg,#fef3c7, #fff8e1);
    box-shadow: 0 6px 18px rgba(2,8,23,0.12);
    border: 1px solid rgba(8,48,79,0.05);
    transition: transform .15s ease, box-shadow .15s ease;
}
.accordion-button:after{
    display:none;
}
.accordion-button.collapsed{
    background: linear-gradient(90deg,#fff8e1,#fffef8);
}
.accordion-button:hover{
    transform: translateY(-3px);
    box-shadow: 0 14px 36px rgba(2,12,27,0.25);
}

/* content */
.accordion-body{
    margin-top:12px;
    background: rgba(255,255,255,0.04);
    border-radius:10px;
    padding:18px;
    color:rgba(255,255,255,0.95);
    border: 1px solid rgba(255,215,0,0.06);
    font-size:0.96rem;
    line-height:1.6;
}

/* Accent bullet */
.vmgo-bullet{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    border-radius:8px;
    background: rgba(255,215,0,0.12);
    color: var(--gold);
    font-weight:800;
    margin-right:10px;
    box-shadow: 0 6px 18px rgba(2,8,23,0.12);
}

/* Two-column layout for VMGO */
.vmgo-grid{
    display:grid;
    grid-template-columns: 1fr;
    gap:14px;
}
@media(min-width:980px){
    .vmgo-grid{ grid-template-columns: 1fr 360px; align-items:start; gap:22px; }
}

/* Right column box */
.side-box{
    background: rgba(255,255,255,0.03);
    border-radius:10px;
    padding:16px;
    border:1px solid rgba(255,215,0,0.06);
    box-shadow: 0 10px 30px rgba(2,8,23,0.18);
}
.side-box h6{
    margin:0 0 12px;
    font-family:'Merriweather', serif;
    color:var(--gold);
    font-weight:700;
}

/* footer */
.footer-fixed{
    position:fixed;
    left:0;
    bottom:0;
    width:100%;
    background: linear-gradient(90deg, rgba(7,27,42,0.9), rgba(8,48,79,0.9));
    border-top: 1px solid rgba(255,215,0,0.06);
    color: rgba(255,255,255,0.8);
    padding:10px 18px;
    font-size:0.95rem;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    z-index:9999;
}

/* subtle entrance animation */
.fade-in {
    animation: fadeUp .55s ease both;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px) }
    to   { opacity: 1; transform: translateY(0) }
}

/* utility */
.kv { font-weight:600; color:rgba(255,255,255,0.9) }
.link-gold { color: var(--gold); font-weight:700; text-decoration:none; }
.link-gold:hover { text-decoration:underline; color:#ffea85; }

</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">
   <a class="navbar-brand d-flex align-items-center gap-2" href="instructor_dashboard.php">
    <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px; width:auto;">
    <img src="ccs.png" alt="CCS Logo" style="height:36px; width:auto;">
    <span>CSTUTORHUB — INSTRUCTOR</span>
</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_students.php">Students</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">Lessons</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">Pre-test</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_assessment.php">Assessment</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">Student Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">Feedback</a></li>
        <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<!-- LIVE DATE & TIME BAR -->
<div id="liveDateTimeBar" style="
    width:100%;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(6px);
    padding:10px 0;
    text-align:center;
    font-size:1rem;
    color:var(--gold);
    font-weight:700;
    border-bottom:1px solid rgba(255,215,0,0.25);
">
    Loading date & time...
</div>

<!-- MAIN -->
<div class="container-main fade-in">

    <!-- TOP: Profile -->
    <div class="top-panel">
        <div class="profile-card">
            <div class="profile-avatar">
                <?php
                    $initials = 'IN';
                    if (!empty($instructor['firstname'])) {
                        $initials = strtoupper(substr($instructor['firstname'],0,1) . (isset($instructor['surname'][0]) ? $instructor['surname'][0] : '') );
                    }
                    echo htmlspecialchars($initials);
                ?>
            </div>
            <div class="profile-meta">
                <h5><?= htmlspecialchars( ($instructor['firstname'] ?? 'Instructor') . ' ' . ($instructor['surname'] ?? '') ) ?></h5>
                <p class="mb-0">Username: <span class="kv"><?= htmlspecialchars($instructor['username'] ?? $username) ?></span></p>
                <p class="mb-0">Role: <span class="kv">Instructor</span> • Department: <span class="kv"><?= htmlspecialchars($instructor['department'] ?? 'Computer Science') ?></span></p>
            </div>
        </div>
    </div>

    <!-- VMGO -->
    <h4 class="section-title">JRMSU VMGO</h4>

    <div class="vmgo-grid">

        <!-- left accordion -->
        <div>
            <div class="accordion" id="vmgoAccordion">

                <!-- CORE VALUES -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading0">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse0" aria-expanded="false" aria-controls="collapse0">
                            <span class="vmgo-bullet">⭐</span> CORE VALUES
                        </button>
                    </h2>
                    <div id="collapse0" class="accordion-collapse collapse" aria-labelledby="heading0" data-bs-parent="#vmgoAccordion">
                        <div class="accordion-body">
                            <p><strong>R</strong> – Resilience</p>
                            <p><strong>I</strong> – Integrity</p>
                            <p><strong>Z</strong> – Zeal for Excellence</p>
                            <p><strong>A</strong> – Altruism</p>
                            <p><strong>L</strong> – Leadership</p>
                        </div>
                    </div>
                </div>

                <!-- VISION -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading1">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                            <span class="vmgo-bullet">🎓</span> VISION
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#vmgoAccordion">
                        <div class="accordion-body">
                            A Smart UniverCity, locally and globally, that inspires excellence, fosters innovation, and promotes sustainable development for the betterment of society.
                        </div>
                    </div>
                </div>

                <!-- MISSION -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                            <span class="vmgo-bullet">📜</span> MISSION
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#vmgoAccordion">
                        <div class="accordion-body">
                            Jose Rizal Memorial State University pledges to deliver effective and efficient services along instruction, research, extension, and production. It commits to provide a Smart UniverCity to foster adaptable learning with technology and innovation for student success, forge partnerships to tackle challenges and drive sustainable progress, and ensure academic excellence, ethics, and accountability in all operations.
                        </div>
                    </div>
                </div>

                <!-- GOALS -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                            <span class="vmgo-bullet">🎯</span> GOALS
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#vmgoAccordion">
                        <div class="accordion-body">
                            Jose Rizal Memorial State University aims to transform into a dynamic institution that prioritizes innovation, collaboration, sustainability, and inclusivity through a S.M.A.R.T. approach:
                            <br><br>
                            <strong>S</strong> – Strategic Modernization and Responsive Technologies<br>
                            <strong>M</strong> – Maximized Stakeholders Glocal Collaboration and Connectivity<br>
                            <strong>A</strong> – Accountable Participatory Governance and Sound Fiscal Management<br>
                            <strong>R</strong> – Resilient to internal and external risks and hazards<br>
                            <strong>T</strong> – Transformative Livability and Inclusive Environment
                        </div>
                    </div>
                </div>

                <!-- QUALITY POLICY -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                            <span class="vmgo-bullet">✅</span> QUALITY POLICY STATEMENT
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#vmgoAccordion">
                        <div class="accordion-body">
                            The Jose Rizal Memorial State University is committed to provide quality instruction, research, extension, and production programs that are relevant and responsive to the needs of the community. It ensures customer satisfaction through continual improvement of services, adherence to ethical standards, and compliance with statutory and regulatory requirements while upholding excellence, integrity, and accountability.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- right side -->
        <aside class="side-box">
            <h6>Quick Actions</h6>
            <ul class="list-unstyled mb-3" style="font-size:0.95rem;">
                <li class="mb-2"><a class="link-gold" href="instructor_manage_lessons.php">+ Create Lesson</a></li>
                <li class="mb-2"><a class="link-gold" href="instructor_manage_pretest.php">+ Create Pre-test</a></li>
                <li class="mb-2"><a class="link-gold" href="instructor_manage_assessment.php">+ Create Assessment</a></li>
                <li class="mb-2"><a class="link-gold" href="instructor_manage_students.php">View Students</a></li>
            </ul>

            <h6>Notes</h6>
            <p style="font-size:0.9rem; color:rgba(255,255,255,0.85)">
                Welcome back. Use the side links to manage content. The VMGO is shown for reference — keep it visible when orienting new students.
            </p>

            <div style="height:12px"></div>
            <small style="color:rgba(255,255,255,0.6)">
                Last login: <strong><?= htmlspecialchars(date('F j, Y \a\t g:ia')) ?></strong>
            </small>
        </aside>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateDateTime() {
    const now = new Date();

    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };

    const dateStr = now.toLocaleDateString('en-US', options);
    const timeStr = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    document.getElementById('liveDateTimeBar').innerHTML =
        dateStr + " — " + timeStr;
}

setInterval(updateDateTime, 1000);
updateDateTime();
</script>

<footer class="text-center py-3" style="
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(8px);
    color:#fff;
    border-top:1px solid rgba(255,255,255,0.3);
    font-weight:600;
">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

</body>
</html>