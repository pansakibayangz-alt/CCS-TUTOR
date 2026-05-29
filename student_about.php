<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

$schoolId = $_SESSION['school_id'];

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmt->execute([$schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Student not found.";
    exit;
}

// UPDATE INFO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {

    $surname = $_POST['surname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $facebook_name = $_POST['facebook_name'];
    $new_username = $_POST['school_id'];
    $phone_number = $_POST['phone_number'];

    if (!empty($_POST['password'])) {
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE students SET surname=?, firstname=?, middlename=?, facebook_name=?, school_id=?, phone_number=?, password=? WHERE student_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surname, $firstname, $middlename, $facebook_name, $new_username, $phone_number, $new_pass, $student['student_id']]);
    } else {
        $sql = "UPDATE students SET surname=?, firstname=?, middlename=?, facebook_name=?, school_id=?, phone_number=? WHERE student_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surname, $firstname, $middlename, $facebook_name, $new_username, $phone_number, $student['student_id']]);
    }

    $_SESSION['school_id'] = $new_username;

    header("Location: student_about.php?updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student About Me</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* =====================================================
   GLOBAL THEME — Navy + Gold (JRMSU / CCS Professional)
====================================================== */
:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy) 0%, var(--navy2) 100%);
    min-height: 100vh;
    margin: 0;
    color: var(--white);
}

/* =====================================================
   NAVBAR — Navy, Gold, Glass, Premium University Style
====================================================== */
.navbar-custom {
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.08);
    box-shadow: 0 6px 20px rgba(0,0,0,0.45);
    padding-top: 6px;
    padding-bottom: 6px;
}

/* BRAND TEXT */
.navbar-brand {
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    letter-spacing: 0.6px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* NAV LINKS */
.navbar-custom .nav-link {
    color: rgba(255,255,255,0.9) !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.83rem;
    letter-spacing: 0.5px;
    padding: 10px 14px !important;
}

/* ACTIVE PAGE */
.navbar-custom .nav-link.text-warning {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 4px;
}

/* UNIVERSAL HOVER EFFECT */
.navbar-nav .nav-link:hover {
    color: var(--gold) !important;
    transform: translateY(-2px);
    transition: 0.22s ease-in-out;
}

/* LOGOUT GOLD SPECIAL */
.link-gold {
    color: var(--gold) !important;
    font-weight: 700;
}
.link-gold:hover {
    color: #ffea85 !important;
}

/* =====================================================
   CARDS — Glass Effect
====================================================== */
.card-custom {
    background: var(--glass);
    border: 1px solid rgba(255,215,0,0.15);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    padding: 25px;
    color: var(--white);
    box-shadow: 0 6px 28px rgba(0,0,0,0.45);
}

/* =====================================================
   INPUTS
====================================================== */
label {
    font-weight: 600;
}

input, select {
    background: rgba(255,255,255,0.9) !important;
    border: none;
}

input[disabled] {
    background: rgba(255,255,255,0.65) !important;
    cursor: not-allowed;
}

.hidden { display: none; }

/* =====================================================
   FOOTER
====================================================== */
.footer-fixed {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    padding: 10px;
    text-align: center;
    border-top: 1px solid rgba(255,215,0,0.12);
    font-weight: 600;
}

</style>


</style>
</head>

<body>

<!-- NAVBAR -->
<!-- STUDENT NAVBAR (FINAL VERSION) -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">

    <!-- BRAND -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="student_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px; width:auto;">
        <img src="ccs.png" alt="CCS Logo" style="height:36px; width:auto;">
        <span>CSTUTORHUB — STUDENT</span>
    </a>

    <!-- TOGGLER -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>

    <!-- LINKS -->
    <?php 
        $current = basename($_SERVER['PHP_SELF']);
    ?>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">

        <li class="nav-item">
          <a class="nav-link <?= $current=='student_about.php' ? 'text-warning fw-bold' : '' ?>" 
             href="student_about.php">About</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current=='student_view_courses.php' ? 'text-warning fw-bold' : '' ?>" 
             href="student_view_courses.php">Courses</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current=='student_progress.php' ? 'text-warning fw-bold' : '' ?>" 
             href="student_progress.php">My Progress</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current=='student_feedback.php' ? 'text-warning fw-bold' : '' ?>" 
             href="student_feedback.php">Feedback</a>
        </li>

        <li class="nav-item">
          <a class="nav-link link-gold" href="logout.php">Logout</a>
        </li>

      </ul>
    </div>

  </div>
</nav>



<!-- HOVER EFFECT STYLE -->
<style>
  .navbar-nav .nav-link:hover {
    color: #f8d05b !important;
    transform: translateY(-2px);
  }
</style>


<!-- MAIN CONTENT -->
<div class="container mt-5 mb-5">
    <div class="card card-custom">

        <h3 class="mb-3 text-warning">STUDENT INFORMATION</h3>

        <form method="POST" id="aboutForm">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <label>Surname</label>
                    <input type="text" name="surname" class="form-control editable-field"
                           value="<?= htmlspecialchars($student['surname']); ?>" disabled>
                </div>

                <div class="col-md-4 mb-3">
                    <label>First Name</label>
                    <input type="text" name="firstname" class="form-control editable-field"
                           value="<?= htmlspecialchars($student['firstname']); ?>" disabled>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Middle Name</label>
                    <input type="text" name="middlename" class="form-control editable-field"
                           value="<?= htmlspecialchars($student['middlename']); ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Facebook Name</label>
                    <input type="text" name="facebook_name" class="form-control editable-field"
                           value="<?= htmlspecialchars($student['facebook_name']); ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>School ID</label>
                    <input type="text" name="school_id" class="form-control editable-field"
                           value="<?= htmlspecialchars($student['school_id']); ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" class="form-control editable-field"
                           value="<?= htmlspecialchars($student['phone_number']); ?>" disabled>
                </div>

                <div class="col-md-6 mb-3">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control editable-field"
                           placeholder="Leave blank if unchanged" disabled>
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

</body>
</html>
