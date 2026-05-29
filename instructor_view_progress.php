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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Progress</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

:root{
    --navy: #0b2b4a;
    --navy-2: #08304f;

    --gold: #FFD700;
    --muted: rgba(255,255,255,0.9);

    --card-bg: rgba(255,255,255,0.04);
    --glass-border: rgba(255,230,0,0.14);
}

/* BODY */
body{
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
    margin:0;
    min-height:100vh;
    overflow-x:hidden;
}

/* TRUE FULL-CENTER WRAPPER */
.page-wrapper {
    min-height: calc(100vh - 160px); /* subtract navbar + footer */
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px 0;
}

/* NAVBAR */
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
h3{
    font-family:'Merriweather', serif;
    color: var(--gold);
    letter-spacing:0.4px;
}
.page-title-shift {
    margin-left: 25px;  /* adjust this number if you want more/less */
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
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
}

.link-gold { color: var(--gold); font-weight:700; }
.link-gold:hover{ text-decoration:underline; color:#ffea85; }

/* CARD STYLE — STRONG GOLD BORDER VERSION */
.card-progress {
    border: 2px solid rgba(255, 215, 0, 0.9); /* stronger gold */
    border-radius: 16px;
    color: #fff;
    font-weight: 600;
    transition: 0.3s ease;
    text-decoration: none;

    /* Navy glass background */
    background: rgba(12, 32, 54, 0.70);
    backdrop-filter: blur(6px);

    /* Stronger gold glow */
    box-shadow: 0 0 22px rgba(255, 215, 0, 0.45);
}

/* HOVER EFFECT — intense glow + smooth lift */
.card-progress:hover {
    transform: translateY(-6px) scale(1.03);

    /* stronger and wider glow */
    box-shadow: 0 0 32px rgba(255, 215, 0, 0.75);
    border-color: rgba(255, 215, 0, 1);
}

/* Card Titles */
.card-progress h5 {
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--gold);
    text-shadow: 0 0 6px rgba(255, 215, 0, 0.7);
}

.card-progress p {
    font-size: 0.95rem;
    opacity: 0.95;
}

/* LIVE TIME BAR */
#liveDateTimeBar{
    width:100%;
    background:rgba(0,0,0,0.35);
    backdrop-filter:blur(6px);
    padding:10px 0;
    text-align:center;
    color:var(--gold);
    font-weight:700;
    border-bottom:1px solid rgba(255,215,0,0.25);
}

/* Footer */
footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(10px);
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
    border-top: 1px solid rgba(255,255,255,0.3);
}


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

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">ABOUT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_students.php">STUDENTS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">LESSONS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">PRE-TEST</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_assessment.php">ASSESSMENT</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_view_progress.php">STUDENT PROGRESS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">FEEDBACK</a></li>
        <li class="nav-item"><a class="nav-link link-gold" href="logout.php">LOGOUT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_dashboard.php" style="font-size:25px;">🏠</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<!-- FULL CENTER WRAPPER -->
<div class="page-wrapper">
    <div class="container" style="max-width:1000px;">
        <div class="row g-4 justify-content-center">

            <div class="col-md-6">
                <a href="instructor_progress_logins.php" class="card card-progress text-center p-4">
                    <div class="card-body">
                        <h5>📅 Daily Student Log-Ins</h5>
                        <p>Check when students logged in for the day.</p>
                    </div>
                </a>
            </div>

            <div class="col-md-6">
                <a href="instructor_progress_lessons.php" class="card card-progress text-center p-4">
                    <div class="card-body">
                        <h5>📚 Learning Progress / Lessons Completed</h5>
                        <p>Track which lessons students have completed.</p>
                    </div>
                </a>
            </div>

            <div class="col-md-6">
                <a href="instructor_progress_overall.php" class="card card-progress text-center p-4">
                    <div class="card-body">
                        <h5>📊 Overall Performance / Average Score</h5>
                        <p>See student averages and overall performance.</p>
                    </div>
                </a>
            </div>

        </div>
    </div>
</div>

<!-- FOOTER -->
<<footer class="text-center py-3" style="
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// LIVE DATE & TIME
function updateDateTime(){
    document.getElementById("liveDateTimeBar").innerText =
        new Date().toLocaleString("en-PH", { 
            timeZone: "Asia/Manila",
            dateStyle: "full",
            timeStyle: "medium"
        });
}
setInterval(updateDateTime,1000);
updateDateTime();
</script>

</body>
</html>
