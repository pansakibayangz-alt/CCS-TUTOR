<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

$username = $_SESSION['username'];

// Fetch admin info
$stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Admin not found.";
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
    $position = $_POST['position'];

    if (!empty($_POST['password'])) {
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE admin SET surname=?, firstname=?, middlename=?, degree_designation=?, username=?, email=?, password=?, position=? WHERE admin_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surname, $firstname, $middlename, $degree_designation, $new_username, $new_email, $new_pass, $position, $admin['admin_id']]);
    } else {
        $sql = "UPDATE admin SET surname=?, firstname=?, middlename=?, degree_designation=?, username=?, email=?, position=? WHERE admin_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$surname, $firstname, $middlename, $degree_designation, $new_username, $new_email, $position, $admin['admin_id']]);
    }

    $_SESSION['username'] = $new_username;

    header("Location: admin_about.php?updated=1");
    exit;
}

// Position options
$positions = ['Associate Dean', 'College Dean', 'Program chair'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin About Me</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{
    --gold: #FFD700;
    --bg-dark: #0A1228;
    --glass-bg: rgba(255, 255, 255, 0.08);
    --glass-border: rgba(255, 255, 255, 0.18);
    --text-muted: rgba(255,255,255,0.85);
}
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: white;
    min-height: 100vh;
    margin: 0;
}
.navbar-custom {
    background: linear-gradient(90deg,#071B2A,#08304F);
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
.navbar-brand { font-family: 'Merriweather', serif; font-size: 1.25rem; color: var(--gold) !important; font-weight:700;}
.navbar-custom .nav-link{ color: var(--text-muted); text-transform: uppercase; font-size: .85rem; font-weight:600;}
.navbar-custom .nav-link:hover, .navbar-custom .nav-link.active{ color: var(--gold); font-weight:700; text-decoration:underline; }
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
.card-custom {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(14px);
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.45);
}
label { font-weight: 600; color: var(--gold); }
input, select {
    background: rgba(255,255,255,0.85);
    border: none;
    border-radius: 10px;
    padding: 10px 12px;
    font-weight: 500;
    color: #000;
}
input[disabled], select[disabled]{ background: rgba(255,255,255,0.55); cursor: not-allowed; }
.btn-warning { background: #F6C72B; border: none; font-weight: 700; }
.btn-warning:hover { background: #ffdd57; }
.btn-success { font-weight: 700; }
.btn-secondary { font-weight: 600; }
.hidden { display: none; }
h2 { font-family: 'Merriweather', serif; color: var(--gold); letter-spacing:0.5px; font-size:1.8rem; }
footer { position: fixed; bottom: 0; width: 100%; background: rgba(0,0,0,0.55); backdrop-filter: blur(10px); color:white; text-align:center; font-weight:600; border-top:1px solid rgba(255,255,255,0.3); padding:10px;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
<div class="container-fluid" style="max-width:1200px; margin:0 auto;">
    <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px;">
        <img src="ccs.png" alt="CCS Logo" style="height:36px;">
        <span>CSTUTORHUB — ADMIN</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
        <svg width="28" height="28" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            <li class="nav-item"><a class="nav-link active" href="admin_about.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_manage_instructors.php">Instructors</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_manage_students.php">Students</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_feedback.php">Feedback</a></li>
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
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Surname</label>
                    <input type="text" name="surname" class="form-control editable-field" value="<?= $admin['surname']; ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label>First Name</label>
                    <input type="text" name="firstname" class="form-control editable-field" value="<?= $admin['firstname']; ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label>Middle Name</label>
                    <input type="text" name="middlename" class="form-control editable-field" value="<?= $admin['middlename']; ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label>Degree / Designation</label>
                    <input type="text" name="degree_designation" class="form-control editable-field" value="<?= $admin['degree_designation']; ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control editable-field" value="<?= $admin['email']; ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control editable-field" value="<?= $admin['username']; ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label>New Password (leave blank if no change)</label>
                    <input type="password" name="password" class="form-control editable-field" placeholder="Enter new password" disabled>
                </div>
                <div class="col-md-6">
                    <label>Position</label>
                    <select name="position" class="form-control editable-field" disabled>
                        <?php foreach($positions as $pos): ?>
                            <option value="<?= $pos ?>" <?= $admin['position'] === $pos ? 'selected' : '' ?>><?= $pos ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="button" id="btnEdit" class="btn btn-warning">Edit</button>
                <button type="button" id="btnCancel" class="btn btn-secondary hidden">Cancel</button>
                <button type="submit" name="save_changes" id="btnSave" class="btn btn-success hidden">Save</button>
            </div>
        </form>
    </div>
</div>

<footer>Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong></footer>

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