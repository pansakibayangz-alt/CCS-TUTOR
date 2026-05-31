<?php
session_start(); // FIX: session_start() sa simula ng file
require_once 'functions.php';
require_once 'db.php';

$error = '';
$role  = '';

// FIX: POST-Redirect-GET pattern — ipakita ang success via GET param
if (isset($_GET['success'])) {
    $successRole = htmlspecialchars($_GET['success']);
} else {
    $successRole = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? ''); // FIX: Tanggal ang redundant na line 7, ito na lang ang ginagamit

    /* ── INSTRUCTOR REGISTRATION ── */
    if ($role === 'INSTRUCTOR') {
        $firstname   = trim($_POST['firstname']          ?? '');
        $middlename  = trim($_POST['middlename']         ?? '');
        $surname     = trim($_POST['surname']            ?? '');
        $degree      = trim($_POST['degree_designation'] ?? '');
        $username    = trim($_POST['username']           ?? '');
        $password    = $_POST['password']                ?? '';
        $confirm     = $_POST['confirm_password']        ?? '';

        // FIX: Username format validation
        if (!$firstname || !$surname || !$username || !$password) {
            $error = "Please fill in all required fields.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = "Username must be 3–30 characters (letters, numbers, underscore only).";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $chk = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->rowCount() > 0) {
                // FIX: htmlspecialchars() sa $username sa loob ng error message (XSS fix)
                $error = "Username '" . htmlspecialchars($username) . "' is already taken.";
            } else {
                $hash       = password_hash($password, PASSWORD_DEFAULT);
                $unique_key = bin2hex(random_bytes(16));

                $stmt = $pdo->prepare("
                    INSERT INTO instructor
                        (firstname, middlename, surname, degree_designation, username, password, status, registered_at, unique_key)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
                ");
                $stmt->execute([$firstname, $middlename, $surname, $degree, $username, $hash, $unique_key]);

                // FIX: Post-Redirect-GET — redirect after success para maiwasan ang double submit on refresh
                header("Location: register.php?success=INSTRUCTOR");
                exit;
            }
        }

    /* ── STUDENT REGISTRATION ── */
    } elseif ($role === 'STUDENT') {
        $firstname    = trim($_POST['s_firstname']    ?? '');
        $middlename   = trim($_POST['s_middlename']   ?? '');
        $surname      = trim($_POST['s_surname']      ?? '');
        $school_id    = trim($_POST['school_id']      ?? '');
        $phone_number = trim($_POST['phone_number']   ?? '');
        $facebook     = trim($_POST['facebook_name']  ?? '');
        $year_level   = trim($_POST['s_year_level']   ?? '');
        $block        = trim($_POST['s_block']        ?? '');
        $password     = $_POST['password']            ?? '';
        $confirm      = $_POST['confirm_password']    ?? '';

        if (!$firstname || !$surname || !$school_id || !$year_level || !$block || !$password) {
            $error = "Please fill in all required fields.";
        // FIX: School ID format validation
        } elseif (!preg_match('/^\d{4}-\d{5}$/', $school_id)) {
            $error = "School ID must follow the format: YYYY-NNNNN (e.g. 2024-00001).";
        // FIX: Phone number format validation (optional field pero kung may laman, dapat valid)
        } elseif ($phone_number && !preg_match('/^09\d{9}$/', $phone_number)) {
            $error = "Contact number must be in the format: 09XXXXXXXXX (11 digits).";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $chk = $pdo->prepare("SELECT student_id FROM students WHERE school_id = ?");
            $chk->execute([$school_id]);
            if ($chk->rowCount() > 0) {
                // FIX: htmlspecialchars() sa $school_id sa loob ng error message (XSS fix)
                $error = "School ID '" . htmlspecialchars($school_id) . "' is already registered.";
            } else {
                $hash       = password_hash($password, PASSWORD_DEFAULT);
                $unique_key = bin2hex(random_bytes(16));

                $stmt = $pdo->prepare("
                    INSERT INTO students
                        (firstname, middlename, surname, school_id, phone_number, facebook_name,
                         year_level, block, password, status, registered_at, unique_key)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
                ");
                $stmt->execute([
                    $firstname, $middlename, $surname, $school_id,
                    $phone_number, $facebook, $year_level, $block, $hash, $unique_key
                ]);

                // FIX: Post-Redirect-GET — redirect after success
                header("Location: register.php?success=STUDENT");
                exit;
            }
        }

    } else {
        $error = "Please select a role.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register — CS Tutoring Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background-color: #ffffff;
        margin: 0;
        overflow-x: hidden;
    }

    /* Left Panel: Fixed & Branded */
    .brand-panel {
        background: linear-gradient(145deg, #0a1228 0%, #1a34ff 100%);
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 41.666667%;
        z-index: 10;
    }
    .brand-panel::before {
        content: "";
        position: absolute;
        inset: 0;
        background: url('jrmsu.png') center/55% no-repeat;
        opacity: 0.12;
        mix-blend-mode: screen;
    }
    .brand-title {
        font-family: 'Montserrat', sans-serif;
        letter-spacing: -0.5px;
    }

    /* Right Panel: Scrollable Form */
    .form-scroll-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        background-color: #ffffff;
    }

    /* Inputs & UI Elements */
    .form-control, .form-select {
        height: 54px;
        padding-left: 46px;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        color: #1e293b;
    }
    .form-control:focus, .form-select:focus {
        border-color: #1a34ff;
        box-shadow: 0 0 0 4px rgba(26,52,255,0.15);
        background-color: #ffffff;
    }
    .input-icon {
        position: absolute;
        top: 50%;
        left: 16px;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.15rem;
        z-index: 4;
        transition: color 0.2s ease;
    }
    .form-control:focus ~ .input-icon, .form-select:focus ~ .input-icon {
        color: #1a34ff;
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Divider */
    .section-divider {
        display: flex;
        align-items: center;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 2rem 0 1.5rem;
    }
    .section-divider::before, .section-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1.5px solid #f1f5f9;
    }
    .section-divider:not(:empty)::before { margin-right: 1em; }
    .section-divider:not(:empty)::after { margin-left: 1em; }

    /* Button */
    .btn-register {
        height: 56px;
        border-radius: 12px;
        background: #1a34ff;
        color: #fff;
        font-weight: 700;
        font-size: 1.05rem;
        letter-spacing: 0.5px;
        border: none;
        box-shadow: 0 4px 14px rgba(26,52,255,0.25);
        transition: all 0.3s ease;
    }
    .btn-register:hover:not(:disabled) {
        background: #0d24c1;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(26,52,255,0.35);
    }
    .btn-register:disabled {
        background: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        opacity: 0.8;
    }

    .success-box {
        background: #f0fdf4;
        border: 2px solid #bbf7d0;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
    }
    .success-box .bi { font-size: 72px; color: #22c55e; margin-bottom: 15px; display:block; }

    /* FIX: Password match indicator styles */
    .pw-match-msg {
        font-size: 0.78rem;
        margin-top: 4px;
        min-height: 18px;
    }

    /* Animations */
    .dynamic-fields { display: none; }
    .dynamic-fields.active {
        display: block;
        animation: slideUpFade 0.5s ease-out forwards;
    }

    .fade-in-up { animation: slideUpFade 0.6s ease-out forwards; }
    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        
        <div class="col-lg-5 d-none d-lg-flex brand-panel flex-column justify-content-center align-items-center text-center p-5">
            <div class="position-relative z-1 text-white fade-in-up">
                <div class="bg-white p-3 rounded-circle d-inline-block mb-4 shadow-lg">
                    <img src="ccs.png" alt="Logo" style="width: 90px; height: 90px; object-fit: contain;">
                </div>
                <h2 class="brand-title fw-bold mb-3 display-5">Join the Hub</h2>
                <p class="fs-5 opacity-75 fw-light px-4">Create an account today to streamline your academic journey and connect with peers.</p>
            </div>
        </div>

        <div class="col-12 col-lg-7 offset-lg-5 form-scroll-wrapper py-5">
            <div class="w-100 px-4 px-md-5 mx-auto fade-in-up" style="max-width: 680px;">
                
                <div class="d-lg-none text-center mb-5">
                    <div class="bg-light p-2 rounded-circle d-inline-block mb-3 shadow-sm">
                        <img src="ccs.png" alt="Logo" style="width: 70px;">
                    </div>
                    <h2 class="brand-title fw-bold text-dark mb-0">CS Tutoring Hub</h2>
                </div>

                <?php if ($successRole): ?>
                    <div class="success-box">
                        <i class="bi bi-check-circle-fill shadow-sm rounded-circle d-inline-block bg-white text-success"></i>
                        <h2 class="fw-bold text-dark mb-3">Registration Successful!</h2>
                        <p class="text-muted fs-5 mb-4">
                            Your <strong><?= $successRole === 'INSTRUCTOR' ? 'Instructor' : 'Student' ?></strong> account has been created and is currently <strong>pending approval</strong>.
                        </p>
                        <p class="text-muted mb-5">An administrator will review your details shortly. You will be able to log in once approved.</p>
                        <a href="login.php" class="btn btn-register w-100 d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Return to Login
                        </a>
                    </div>
                <?php else: ?>

                    <div class="mb-4">
                        <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1px;">Create Account</h1>
                        <p class="text-secondary fs-6 mb-0">Please fill in your information to get started.</p>
                        <!-- FIX: Required fields legend -->
                        <p class="text-secondary" style="font-size: 0.8rem;"><span class="text-danger">*</span> Required fields</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3 border-0 shadow-sm d-flex align-items-center p-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                            <!-- FIX: Error message na naka-htmlspecialchars na sa PHP side na, safe na ito -->
                            <div><?= $error ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="regForm">
                        
                        <div class="mb-4">
                            <label class="form-label">I am registering as a <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <select name="role" id="roleSelect" class="form-select border-primary bg-white shadow-sm" required style="border-width: 2px;">
                                    <option value="">-- Choose your account type --</option>
                                    <option value="INSTRUCTOR" <?= $role==='INSTRUCTOR'?'selected':'' ?>>Instructor</option>
                                    <option value="STUDENT"    <?= $role==='STUDENT'   ?'selected':'' ?>>Student</option>
                                </select>
                                <i class="bi bi-person-badge input-icon text-primary"></i>
                            </div>
                        </div>

                        <!-- INSTRUCTOR FIELDS -->
                        <div id="instructorFields" class="dynamic-fields">
                            <div class="section-divider">Personal Details</div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" placeholder="Juan">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Surname <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="surname" class="form-control" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" placeholder="Dela Cruz">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Middle Name <span class="text-muted fw-normal">(Optional)</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="middlename" class="form-control" value="<?= htmlspecialchars($_POST['middlename'] ?? '') ?>" placeholder="(Optional)">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Degree / Designation <span class="text-muted fw-normal">(Optional)</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="degree_designation" class="form-control" value="<?= htmlspecialchars($_POST['degree_designation'] ?? '') ?>" placeholder="e.g. MIT, Ph.D">
                                        <i class="bi bi-award input-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="section-divider">Account Security</div>

                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="text" name="username" id="username_i" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="3–30 characters, letters/numbers/_">
                                    <i class="bi bi-at input-icon"></i>
                                </div>
                                <!-- FIX: Live username hint -->
                                <div id="username_hint" class="pw-match-msg text-muted"></div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="password" id="pw_i" class="form-control" placeholder="Min. 6 characters">
                                        <i class="bi bi-shield-lock input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="confirm_password" id="cpw_i" class="form-control" placeholder="Re-enter password">
                                        <i class="bi bi-shield-check input-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <!-- FIX: Live password match indicator -->
                            <div id="pw_match_i" class="pw-match-msg mb-4"></div>
                        </div>

                        <!-- STUDENT FIELDS -->
                        <div id="studentFields" class="dynamic-fields">
                            <div class="section-divider">Personal Details</div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="s_firstname" class="form-control" value="<?
