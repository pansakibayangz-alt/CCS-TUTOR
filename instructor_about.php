<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

$username = $_SESSION['username'];

// Fetch instructor info
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instructor) {
    echo "Instructor not found.";
    exit;
}

// UPDATE INFO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {

    $surname = $_POST['surname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $degree_designation = $_POST['degree_designation'];
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];

    if (!empty($_POST['password'])) {
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE instructor SET surname=?, firstname=?, middlename=?, degree_designation=?, username=?, email=?, password=? WHERE instructor_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surname, $firstname, $middlename, $degree_designation, $new_username, $new_email, $new_pass, $instructor['instructor_id']]);
    } else {
        $sql = "UPDATE instructor SET surname=?, firstname=?, middlename=?, degree_designation=?, username=?, email=? WHERE instructor_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surname, $firstname, $middlename, $degree_designation, $new_username, $new_email, $instructor['instructor_id']]);
    }

    $_SESSION['username'] = $new_username;

    header("Location: instructor_about.php?updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instructor About Me</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root{
    --gold: #FFD700;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #0A1228; /* Deep JRMSU Navy */
    min-height: 100vh;
    color: #ffffff;
    margin: 0;
}

/* Navbar Dark Blue with Glow */
.navbar-custom{
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
}

.navbar-brand{
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: #FFD700 !important;
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
    color: #FFD700;
    font-weight: 700;
    text-decoration:underline;
}
.link-gold { color: #FFD700; font-weight:700; }
.link-gold:hover { text-decoration:underline; color:#ffea85;
}


/* Card - Glassmorphism */
.card-custom {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(14px);
    border-radius: 18px;
    padding: 28px;
    color: white;
    box-shadow: 0 0 25px rgba(0,0,0,0.45);
}

/* Labels */
label {
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Inputs */
input {
    background: rgba(255,255,255,0.85) !important;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    padding: 10px 12px;
}

input[disabled] {
    background: rgba(255,255,255,0.55) !important;
    color: #000;
    cursor: not-allowed;
}

/* Buttons */
.btn-warning {
    background: #F6C72B;
    border: none;
    color: #000;
    font-weight: 700;
}
.btn-warning:hover {
    background: #ffdd57;
}

.btn-success {
    font-weight: 700;
}

.btn-secondary {
    font-weight: 600;
}

.hidden { display: none; }

/* TITLE */
h2{
    font-family:'Merriweather', serif;
    color: var(--gold);
    letter-spacing:0.4px;
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

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false"
            aria-label="Toggle navigation">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18"
              stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link active" href="instructor_about.php">About</a></li>
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

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container mt-5 pb-5">

    <div class="card card-custom">

        <form method="POST">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Surname</label>
                    <input type="text" name="surname" class="form-control editable-field"
                        value="<?= $instructor['surname']; ?>" disabled>
                </div>

                <div class="col-md-4 mb-3">
                    <label>First Name</label>
                    <input type="text" name="firstname" class="form-control editable-field"
                        value="<?= $instructor['firstname']; ?>" disabled>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Middle Name</label>
                    <input type="text" name="middlename" class="form-control editable-field"
                        value="<?= $instructor['middlename']; ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Degree / Designation</label>
                    <input type="text" name="degree_designation" class="form-control editable-field"
                        value="<?= $instructor['degree_designation']; ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control editable-field"
                        value="<?= $instructor['email']; ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control editable-field"
                        value="<?= $instructor['username']; ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>New Password (leave blank if no change)</label>
                    <input type="password" name="password" class="form-control editable-field"
                        placeholder="Enter new password" disabled>
                </div>
            </div>

            <!-- BUTTONS -->
            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="button" id="btnEdit" class="btn btn-warning px-4 fw-bold">Edit</button>
                <button type="button" id="btnCancel" class="btn btn-secondary px-4 hidden">Cancel</button>
                <button type="submit" name="save_changes" id="btnSave" class="btn btn-success px-4 fw-bold hidden">Save</button>
            </div>

        </form>
    </div>
</div>

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

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const editBtn = document.getElementById("btnEdit");
    const saveBtn = document.getElementById("btnSave");
    const cancelBtn = document.getElementById("btnCancel");
    const fields = document.querySelectorAll(".editable-field");

    let originalValues = {};

    editBtn.addEventListener("click", () => {
        fields.forEach(f => originalValues[f.name] = f.value);
        fields.forEach(f => f.disabled = false);

        editBtn.classList.add("hidden");
        saveBtn.classList.remove("hidden");
        cancelBtn.classList.remove("hidden");
    });

    cancelBtn.addEventListener("click", () => {
        fields.forEach(f => f.value = originalValues[f.name]);
        fields.forEach(f => f.disabled = true);

        saveBtn.classList.add("hidden");
        cancelBtn.classList.add("hidden");
        editBtn.classList.remove("hidden");
    });
});
</script>
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
