<?php
require_once 'functions.php';
require_once 'db.php';

/* ------------------------------
   FETCH ADMINS & INSTRUCTORS
--------------------------------*/
$adminQuery = $pdo->prepare("SELECT username, password FROM admin ORDER BY username");
$adminQuery->execute();
$admins = $adminQuery->fetchAll(PDO::FETCH_ASSOC);

$instructorQuery = $pdo->prepare("SELECT username, password FROM instructor ORDER BY username");
$instructorQuery->execute();
$instructors = $instructorQuery->fetchAll(PDO::FETCH_ASSOC);

$studentQueryAll = $pdo->query("SELECT school_id FROM students");
$students = $studentQueryAll->fetchAll(PDO::FETCH_ASSOC);

/* Role availability */
$roleExists = [
    'ADMIN'      => !empty($admins),
    'INSTRUCTOR' => !empty($instructors),
    'STUDENT'    => !empty($students)
];

/* Helper: check if status column already exists (migration may not be run yet) */
function columnExists($pdo, $table, $col) {
    try { $pdo->query("SELECT $col FROM $table LIMIT 0"); return true; }
    catch (Exception $e) { return false; }
}
$hasStatus = columnExists($pdo, 'instructor', 'status');

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

        $cols = $hasStatus ? "username, password, status, rejection_reason" : "username, password";
        $stmtAny = $pdo->prepare("SELECT $cols FROM instructor WHERE username = ?");
        $stmtAny->execute([$username]);
        $instructorUser = $stmtAny->fetch(PDO::FETCH_ASSOC);

        if (!$instructorUser) {
            $loginError = "Instructor username is incorrect.";
        } elseif (!password_verify($password, $instructorUser['password'])) {
            $loginError = "Instructor password is incorrect.";
        } elseif ($hasStatus && ($instructorUser['status'] ?? 'approved') === 'pending') {
            $loginError = "Your account is still <strong>pending approval</strong>. Please wait for the Admin to approve your registration.";
        } elseif ($hasStatus && ($instructorUser['status'] ?? 'approved') === 'rejected') {
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

        $scols = $hasStatus ? "password, status, rejection_reason" : "password";
        $studentQuery = $pdo->prepare("SELECT $scols FROM students WHERE school_id = ?");
        $studentQuery->execute([$schoolId]);
        $student = $studentQuery->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $loginError = "School ID is incorrect.";
        } elseif (!password_verify($password, $student['password'])) {
            $loginError = "Password is incorrect.";
        } elseif ($hasStatus && ($student['status'] ?? 'approved') === 'pending') {
            $loginError = "Your account is still <strong>pending approval</strong>. Please wait for the Admin to approve your registration.";
        } elseif ($hasStatus && ($student['status'] ?? 'approved') === 'rejected') {
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
<title>CS Tutoring Hub — Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        overflow-x: hidden;
    }

    /* Split Screen Branding Panel */
    .brand-panel {
        background: linear-gradient(135deg, #0a1228 0%, #1a34ff 100%);
        position: relative;
    }
    .brand-panel::before {
        content: "";
        position: absolute;
        inset: 0;
        background: url('jrmsu.png') center/50% no-repeat;
        opacity: 0.15;
        mix-blend-mode: overlay;
    }
    .brand-title {
        font-family: 'Montserrat', sans-serif;
        letter-spacing: -0.5px;
    }

    /* Form Panel */
    .form-control, .form-select {
        height: 52px;
        padding-left: 45px;
        border-radius: 10px;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #1a34ff;
        box-shadow: 0 0 0 4px rgba(26,52,255,0.1);
        background-color: #ffffff;
    }
    .input-icon {
        position: absolute;
        top: 50%;
        left: 16px;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.1rem;
        z-index: 4;
    }

    /* Custom Button */
    .btn-login {
        height: 52px;
        border-radius: 10px;
        background: #1a34ff;
        color: #fff;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }
    .btn-login:hover:not(:disabled) {
        background: #0022cc;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(26,52,255,0.2);
    }
    .btn-login:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    /* Animations */
    .fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animated-field {
        animation: fadeIn 0.4s ease forwards;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
</head>
<body>

<div class="container-fluid p-0 vh-100">
    <div class="row g-0 h-100">
        
        <div class="col-lg-5 col-xl-6 d-none d-lg-flex brand-panel flex-column justify-content-center align-items-center text-center p-5">
            <div class="position-relative z-1 text-white fade-in-up">
                <img src="ccs.png" alt="Logo" class="mb-4 shadow-sm rounded-circle" style="width: 130px;">
                <h1 class="brand-title fw-bold display-5 mb-3">CS Tutoring Hub</h1>
                <p class="fs-5 opacity-75 fw-light">Empowering students to excel through peer-led learning.</p>
            </div>
        </div>

        <div class="col-12 col-lg-7 col-xl-6 d-flex align-items-center justify-content-center">
            <div class="w-100 px-4 px-md-5 py-5 fade-in-up" style="max-width: 550px;">
                
                <div class="d-lg-none text-center mb-5">
                    <img src="ccs.png" alt="Logo" class="mb-3" style="width: 80px;">
                    <h2 class="brand-title fw-bold text-dark mb-0">CS Tutoring Hub</h2>
                </div>

                <div class="mb-5">
                    <h2 class="fw-bold text-dark mb-2">Welcome Back</h2>
                    <p class="text-muted">Please enter your details to sign in to your account.</p>
                </div>

                <?php if($loginError): ?>
                    <div class="alert alert-danger rounded-3 border-0 shadow-sm d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= $loginError ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="loginForm">

                    <div class="mb-4 position-relative">
                        <label class="form-label fw-semibold text-dark small">Select Role</label>
                        <div class="position-relative">
                            <i class="bi bi-person-badge input-icon"></i>
                            <select name="role" id="role" class="form-select">
                                <option value="">-- Choose your role --</option>
                                <option value="ADMIN" <?= $oldValues['role']=='ADMIN'?'selected':'' ?>>Administrator</option>
                                <option value="INSTRUCTOR" <?= $oldValues['role']=='INSTRUCTOR'?'selected':'' ?>>Instructor</option>
                                <option value="STUDENT" <?= $oldValues['role']=='STUDENT'?'selected':'' ?>>Student</option>
                            </select>
                        </div>
                    </div>

                    <div id="adminFields" style="display:none">
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-semibold text-dark small">Admin Username</label>
                            <div class="position-relative">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" name="admin_username" class="form-control" placeholder="Enter username" value="<?= htmlspecialchars($oldValues['admin_username']) ?>">
                            </div>
                        </div>
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-semibold text-dark small">Password</label>
                            <div class="position-relative">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" name="admin_password" class="form-control" placeholder="••••••••" value="<?= htmlspecialchars($oldValues['admin_password']) ?>">
                            </div>
                        </div>
                    </div>

                    <div id="instructorFields" style="display:none">
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-semibold text-dark small">Instructor Username</label>
                            <div class="position-relative">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" name="instructor_username" class="form-control" placeholder="Enter username" value="<?= htmlspecialchars($oldValues['instructor_username']) ?>">
                            </div>
                        </div>
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-semibold text-dark small">Password</label>
                            <div class="position-relative">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" name="instructor_password" class="form-control" placeholder="••••••••" value="<?= htmlspecialchars($oldValues['instructor_password']) ?>">
                            </div>
                        </div>
                    </div>

                    <div id="studentFields" style="display:none">
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-semibold text-dark small">School ID</label>
                            <div class="position-relative">
                                <i class="bi bi-credit-card-2-front input-icon"></i>
                                <input type="text" name="school_id" class="form-control" placeholder="e.g. 2024-00001" value="<?= htmlspecialchars($oldValues['school_id']) ?>">
                            </div>
                        </div>
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-semibold text-dark small">Password</label>
                            <div class="position-relative">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" name="student_password" class="form-control" placeholder="••••••••" value="<?= htmlspecialchars($oldValues['student_password']) ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100 mt-2" id="loginBtn" disabled>Sign In</button>

                    <div class="mt-4 text-center">
                        <p class="text-muted small mb-1">
                            Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold text-primary">Create an account</a>
                        </p>
                        <a href="forgot_password.php" class="text-decoration-none text-muted small fw-semibold hover-primary">Forgot Password?</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
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

    adminFields.classList.add("animated-field");
    instructorFields.classList.add("animated-field");
    studentFields.classList.add("animated-field");

    validateForm();
}

function validateForm() {
    let valid = false;
    if (role.value === "ADMIN") {
        valid = (document.querySelector('input[name="admin_username"]').value.trim() !== "" && 
                 document.querySelector('input[name="admin_password"]').value.trim() !== "");
    }
    if (role.value === "INSTRUCTOR") {
        valid = (document.querySelector('input[name="instructor_username"]').value.trim() !== "" && 
                 document.querySelector('input[name="instructor_password"]').value.trim() !== "");
    }
    if (role.value === "STUDENT") {
        valid = (document.querySelector('input[name="school_id"]').value.trim() !== "" && 
                 document.querySelector('input[name="student_password"]').value.trim() !== "");
    }
    loginBtn.disabled = !valid;
}

role.addEventListener("change", updateFields);
document.addEventListener("input", validateForm);
document.addEventListener("DOMContentLoaded", updateFields);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
