<?php
require_once 'functions.php';
require_once 'db.php';

/* ------------------------------
   FETCH ADMINS & INSTRUCTORS
--------------------------------*/
$adminQuery = $pdo->prepare("SELECT username, password FROM admin ORDER BY username");
$adminQuery->execute();
$admins = $adminQuery->fetchAll(PDO::FETCH_ASSOC);

/* Only fetch APPROVED instructors for login check */
$instructorQuery = $pdo->prepare("SELECT username, password, status, rejection_reason FROM instructor WHERE status = 'approved' ORDER BY username");
$instructorQuery->execute();
$instructors = $instructorQuery->fetchAll(PDO::FETCH_ASSOC);

/* Only fetch APPROVED students for role availability check */
$studentQueryAll = $pdo->query("SELECT school_id FROM students WHERE status = 'approved'");
$students = $studentQueryAll->fetchAll(PDO::FETCH_ASSOC);

/* Role availability */
$roleExists = [
    'ADMIN' => !empty($admins),
    'INSTRUCTOR' => !empty($instructors),
    'STUDENT' => !empty($students)
];

/* ------------------------------
   HANDLE LOGIN
--------------------------------*/
$loginError = '';
$oldValues = [
    'role'=>'', 
    'admin_username'=>'', 
    'instructor_username'=>'', 
    'school_id'=>'', 
    'admin_password'=>'', 
    'instructor_password'=>'', 
    'student_password'=>''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $oldValues['role'] = $role;

    /* ADMIN */
    if ($role === 'ADMIN' && $roleExists['ADMIN']) {
        $username = trim($_POST['admin_username'] ?? '');
        $password = trim($_POST['admin_password'] ?? '');
        $oldValues['admin_username'] = $username;
        $oldValues['admin_password'] = $password;

        $adminUser = null;
        foreach ($admins as $a) {
            if ($a['username'] === $username) {
                $adminUser = $a;
                break;
            }
        }

        if (!$adminUser) {
            $loginError = "Admin username is incorrect.";
        } elseif (!password_verify($password, $adminUser['password'])) {
            $loginError = "Admin password is incorrect.";
        } else {
            session_start();
            $_SESSION['role'] = 'ADMIN';
            $_SESSION['username'] = $username;
            header("Location: admin_dashboard.php");
            exit;
        }
    }

    /* INSTRUCTOR */
    elseif ($role === 'INSTRUCTOR') {
        $username = trim($_POST['instructor_username'] ?? '');
        $password = trim($_POST['instructor_password'] ?? '');
        $oldValues['instructor_username'] = $username;
        $oldValues['instructor_password'] = $password;

        /* Check ALL instructors (any status) for proper error messages */
        $stmtAny = $pdo->prepare("SELECT username, password, status, rejection_reason FROM instructor WHERE username = ?");
        $stmtAny->execute([$username]);
        $instructorUser = $stmtAny->fetch(PDO::FETCH_ASSOC);

        if (!$instructorUser) {
            $loginError = "Instructor username is incorrect.";
        } elseif (!password_verify($password, $instructorUser['password'])) {
            $loginError = "Instructor password is incorrect.";
        } elseif ($instructorUser['status'] === 'pending') {
            $loginError = "Your account is still <strong>pending approval</strong>. Please wait for the Admin to approve your registration.";
        } elseif ($instructorUser['status'] === 'rejected') {
            $reason = htmlspecialchars($instructorUser['rejection_reason'] ?? 'No reason provided.');
            $loginError = "Your account has been <strong>rejected</strong>. Reason: <em>$reason</em>";
        } else {
            session_start();
            $_SESSION['role'] = 'INSTRUCTOR';
            $_SESSION['username'] = $username;
            header("Location: instructor_dashboard.php");
            exit;
        }
    }

    /* STUDENT */
    elseif ($role === 'STUDENT') {
        $schoolId = trim($_POST['school_id'] ?? '');
        $password = trim($_POST['student_password'] ?? '');
        $oldValues['school_id'] = $schoolId;
        $oldValues['student_password'] = $password;

        /* Check ALL students (any status) for proper error messages */
        $studentQuery = $pdo->prepare("SELECT password, status, rejection_reason FROM students WHERE school_id = ?");
        $studentQuery->execute([$schoolId]);
        $student = $studentQuery->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $loginError = "School ID is incorrect.";
        } elseif (!password_verify($password, $student['password'])) {
            $loginError = "Password is incorrect.";
        } elseif ($student['status'] === 'pending') {
            $loginError = "Your account is still <strong>pending approval</strong>. Please wait for the Admin to approve your registration.";
        } elseif ($student['status'] === 'rejected') {
            $reason = htmlspecialchars($student['rejection_reason'] ?? 'No reason provided.');
            $loginError = "Your account has been <strong>rejected</strong>. Reason: <em>$reason</em>";
        } else {
            session_start();
            $_SESSION['role'] = 'STUDENT';
            $_SESSION['school_id'] = $schoolId;

            $stmtLogin = $pdo->prepare("INSERT INTO student_logins (student_id) VALUES (?)");
            $stmtLogin->execute([$schoolId]);

            $_SESSION['login_id'] = $pdo->lastInsertId();

            header("Location: student_dashboard.php");
            exit;
        }
    }

    else {
        $loginError = "Please select a role.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BSCS Student Progress — Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap');

/* DISABLE BUTTON */
.btn-login:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* FULL SCREEN */
body {
    margin: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    background: #0a1228;
    font-family: "Poppins", sans-serif;
    position: relative;
    overflow: hidden;
}

/* WATERMARK */
body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: url('jrmsu.png') center/45% no-repeat;
    opacity: 0.27;
    z-index: 1;
}

/* TITLE */
.system-title {
    position: absolute;
    top: 40px;
    font-family: "Montserrat", sans-serif;
    font-weight: 800;
    font-size: 42px;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 14px;
    z-index: 3;

    /* ✨ SYSTEM TITLE ANIMATION */
    opacity: 0;
    transform: translateY(-25px);
    animation: titleFade 1s ease-out forwards;
}

@keyframes titleFade {
    from {
        opacity: 0;
        transform: translateY(-25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.system-title img {
    width: 85px;
    height: 85px;
}

/* FORM BOX */
.container-box {
    position: relative;
    z-index: 2;
    width: 380px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(14px);
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 0 35px rgba(0,0,0,0.45);
    text-align: center;
    margin-top: 120px;

    /* ✨ FORM SLIDE + FADE ANIMATION */
    opacity: 0;
    transform: translateY(40px);
    animation: formFade 0.7s ease-out forwards;
}

@keyframes formFade {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FIELD ANIMATION */
.animated-field {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeField 0.5s ease forwards;
}

@keyframes fadeField {
    to { opacity: 1; transform: translateY(0); }
}

.position-relative span.input-icon {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #164bff;
    font-size: 18px;
}

.form-control, .form-select {
    height: 48px;
    padding-left: 40px;
    border-radius: 12px;
    border: 1px solid #d6ddff;
}

/* BUTTON */
.btn-login {
    width: 100%;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1a34ff, #0066ff);
    color: #fcfcfe;
    font-weight: 600;
    transition: 0.2s;
}

.btn-login:hover:not(:disabled) {
    background: linear-gradient(135deg, #0d24c1, #0044cc);
}
</style>
</head>

<body>

<div class="system-title">
    CS TUTORING HUB
    <img src="ccs.png">
</div>

<div class="container-box">
	<h3 class="text-white fw-bold" style="font-size: 32px; letter-spacing: 1px;">
		LOGIN
	</h3>
	<p class="text-light mb-3">Access your account</p>

    <?php if($loginError): ?>
        <div class="alert alert-danger text-center"><?= $loginError ?></div>
    <?php endif; ?>

    <form action="" method="POST">

        <label class="form-label text-white">Select Role</label>
        <div class="mb-3 position-relative">
            <span class="input-icon"><i class="bi bi-person-badge"></i></span>
            <select name="role" id="role" class="form-select">
                <option value="">-- Select Role --</option>
                <option value="ADMIN" <?= $oldValues['role']=='ADMIN'?'selected':'' ?>>Admin</option>
                <option value="INSTRUCTOR" <?= $oldValues['role']=='INSTRUCTOR'?'selected':'' ?>>Instructor</option>
                <option value="STUDENT" <?= $oldValues['role']=='STUDENT'?'selected':'' ?>>Student</option>
            </select>
        </div>

        <!-- ADMIN -->
        <div id="adminFields" style="display:none">
            <label class="form-label text-white">Username</label>
            <div class="mb-3 position-relative">
                <span class="input-icon"><i class="bi bi-person"></i></span>
                <input type="text" name="admin_username" class="form-control"
                value="<?= htmlspecialchars($oldValues['admin_username']) ?>">
            </div>

            <label class="form-label text-white">Password</label>
            <div class="mb-3 position-relative">
                <span class="input-icon"><i class="bi bi-lock"></i></span>
                <input type="password" name="admin_password" class="form-control"
                value="<?= htmlspecialchars($oldValues['admin_password']) ?>">
            </div>
        </div>

        <!-- INSTRUCTOR -->
        <div id="instructorFields" style="display:none">
            <label class="form-label text-white">Username</label>
            <div class="mb-3 position-relative">
                <span class="input-icon"><i class="bi bi-person"></i></span>
                <input type="text" name="instructor_username" class="form-control"
                value="<?= htmlspecialchars($oldValues['instructor_username']) ?>">
            </div>

            <label class="form-label text-white">Password</label>
            <div class="mb-3 position-relative">
                <span class="input-icon"><i class="bi bi-lock"></i></span>
                <input type="password" name="instructor_password" class="form-control"
                value="<?= htmlspecialchars($oldValues['instructor_password']) ?>">
            </div>
        </div>

        <!-- STUDENT -->
        <div id="studentFields" style="display:none">
            <label class="form-label text-white">School ID</label>
            <div class="mb-3 position-relative">
                <span class="input-icon"><i class="bi bi-credit-card-2-front"></i></span>
                <input type="text" name="school_id" class="form-control"
                value="<?= htmlspecialchars($oldValues['school_id']) ?>">
            </div>

            <label class="form-label text-white">Password</label>
            <div class="mb-3 position-relative">
                <span class="input-icon"><i class="bi bi-lock"></i></span>
                <input type="password" name="student_password" class="form-control"
                value="<?= htmlspecialchars($oldValues['student_password']) ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-login mt-2" id="loginBtn" disabled>LOGIN</button>

        <p class="mt-3">
            <a href="register.php" class="text-info">Register Here</a><br>
            <a href="forgot_password.php" class="text-warning">Forgot Password?</a>
        </p>

    </form>

</div>

<script>
const role = document.getElementById('role');
const adminFields = document.getElementById('adminFields');
const instructorFields = document.getElementById('instructorFields');
const studentFields = document.getElementById('studentFields');
const loginBtn = document.getElementById('loginBtn');

function updateFields() {
    adminFields.style.display = "none";
    instructorFields.style.display = "none";
    studentFields.style.display = "none";

    if (role.value === "ADMIN") adminFields.style.display = "block";
    if (role.value === "INSTRUCTOR") instructorFields.style.display = "block";
    if (role.value === "STUDENT") studentFields.style.display = "block";

    /* ✨ ADD ANIMATION CLASS */
    adminFields.classList.add("animated-field");
    instructorFields.classList.add("animated-field");
    studentFields.classList.add("animated-field");

    validateForm();
}

function validateForm() {
    let valid = false;

    if (role.value === "ADMIN") {
        let u = document.querySelector('input[name="admin_username"]').value.trim();
        let p = document.querySelector('input[name="admin_password"]').value.trim();
        valid = (u !== "" && p !== "");
    }

    if (role.value === "INSTRUCTOR") {
        let u = document.querySelector('input[name="instructor_username"]').value.trim();
        let p = document.querySelector('input[name="instructor_password"]').value.trim();
        valid = (u !== "" && p !== "");
    }

    if (role.value === "STUDENT") {
        let id = document.querySelector('input[name="school_id"]').value.trim();
        let p = document.querySelector('input[name="student_password"]').value.trim();
        valid = (id !== "" && p !== "");
    }

    loginBtn.disabled = !valid;
}

role.addEventListener("change", updateFields);
document.addEventListener("input", validateForm);
document.addEventListener("DOMContentLoaded", updateFields);
</script>

</body>
</html>
