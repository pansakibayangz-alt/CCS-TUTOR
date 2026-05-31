<?php
require_once 'functions.php';
require_once 'db.php';

$success = '';
$error   = '';
$role    = $_POST['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? '');

    /* ── INSTRUCTOR REGISTRATION ── */
    if ($role === 'INSTRUCTOR') {
        $firstname   = trim($_POST['firstname']          ?? '');
        $middlename  = trim($_POST['middlename']         ?? '');
        $surname     = trim($_POST['surname']            ?? '');
        $degree      = trim($_POST['degree_designation'] ?? '');
        $username    = trim($_POST['username']           ?? '');
        $password    = $_POST['password']                ?? '';
        $confirm     = $_POST['confirm_password']        ?? '';

        if (!$firstname || !$surname || !$username || !$password) {
            $error = "Please fill in all required fields.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            /* duplicate check */
            $chk = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->rowCount() > 0) {
                $error = "Username '$username' is already taken.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // ADDED: Generate a secure unique key for the database column
                $unique_key = bin2hex(random_bytes(16));

                $stmt = $pdo->prepare("
                    INSERT INTO instructor
                        (firstname, middlename, surname, degree_designation, username, password, status, registered_at, unique_key)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
                ");
                $stmt->execute([$firstname, $middlename, $surname, $degree, $username, $hash, $unique_key]);
                $success = "INSTRUCTOR";
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
        $block        = trim($_POST['s_block']         ?? '');
        $password     = $_POST['password']             ?? '';
        $confirm      = $_POST['confirm_password']     ?? '';

        if (!$firstname || !$surname || !$school_id || !$year_level || !$block || !$password) {
            $error = "Please fill in all required fields.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $chk = $pdo->prepare("SELECT student_id FROM students WHERE school_id = ?");
            $chk->execute([$school_id]);
            if ($chk->rowCount() > 0) {
                $error = "School ID '$school_id' is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // ADDED: Generate a secure unique key for the database column
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
                $success = "STUDENT";
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
        background-color: #f8f9fa;
        margin: 0;
        overflow-x: hidden;
    }

    /* Split Screen Branding Panel */
    .brand-panel {
        background: linear-gradient(135deg, #0a1228 0%, #1a34ff 100%);
        position: fixed;
        height: 100vh;
        width: 41.666667%; /* col-lg-5 equivalent */
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

    /* Registration Form Container */
    .form-container {
        margin-left: auto;
        min-height: 100vh;
    }
    @media (min-width: 992px) {
        .form-container {
            width: 58.333333%; /* col-lg-7 equivalent */
        }
    }

    /* Inputs */
    .form-control, .form-select {
        height: 50px;
        padding-left: 42px;
        border-radius: 8px;
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
        left: 14px;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.1rem;
        z-index: 4;
    }

    .btn-register {
        height: 52px;
        border-radius: 10px;
        background: #1a34ff;
        color: #fff;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }
    .btn-register:hover:not(:disabled) {
        background: #0022cc;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(26,52,255,0.2);
    }
    .btn-register:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    .success-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .success-box .bi { font-size: 64px; color: #22c55e; margin-bottom: 20px; display:block; }
    
    .dynamic-fields { display:none; }
    
    .fade-in-up { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        
        <div class="col-lg-5 d-none d-lg-flex brand-panel flex-column justify-content-center align-items-center text-center p-5">
            <div class="position-relative z-1 text-white fade-in-up">
                <img src="ccs.png" alt="Logo" class="mb-4 shadow-sm rounded-circle" style="width: 110px;">
                <h2 class="brand-title fw-bold mb-3">Join the Hub</h2>
                <p class="fs-6 opacity-75 fw-light px-4">Create your account to start managing your academic journey and tutoring sessions.</p>
            </div>
        </div>

        <div class="col-12 form-container d-flex align-items-center justify-content-center bg-white py-5">
            <div class="w-100 px-4 px-md-5 fade-in-up" style="max-width: 650px;">
                
                <div class="d-lg-none text-center mb-4 mt-2">
                    <img src="ccs.png" alt="Logo" class="mb-3" style="width: 70px;">
                    <h2 class="brand-title fw-bold text-dark mb-0">CS Tutoring Hub</h2>
                </div>

                <?php if ($success): ?>
                    <div class="success-box">
                        <i class="bi bi-check-circle-fill"></i>
                        <h3 class="fw-bold text-dark mb-3">Registration Submitted!</h3>
                        <p class="text-muted mb-4">
                            Your <strong><?= $success === 'INSTRUCTOR' ? 'Instructor' : 'Student' ?></strong>
                            account is now pending approval.<br><br>
                            Please wait for the administrator to review and approve your account before logging in.
                        </p>
                        <a href="login.php" class="btn btn-register w-100 mt-2 d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Return to Login
                        </a>
                    </div>
                <?php else: ?>

                    <div class="mb-4">
                        <h2 class="fw-bold text-dark mb-1">Create an Account</h2>
                        <p class="text-muted small">Fill out the details below to register.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="regForm">
                        
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-semibold text-dark small">I am registering as a <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-person-badge input-icon"></i>
                                <select name="role" id="roleSelect" class="form-select border-primary" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="INSTRUCTOR" <?= $role==='INSTRUCTOR'?'selected':'' ?>>Instructor</option>
                                    <option value="STUDENT"    <?= $role==='STUDENT'   ?'selected':'' ?>>Student</option>
                                </select>
                            </div>
                        </div>

                        <hr class="mb-4 text-muted opacity-25">

                        <div id="instructorFields" class="dynamic-fields">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">First Name <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" placeholder="Juan">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Surname <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" name="surname" class="form-control" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" placeholder="dela Cruz">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Middle Name</label>
                                    <div class="position-relative">
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" name="middlename" class="form-control" value="<?= htmlspecialchars($_POST['middlename'] ?? '') ?>" placeholder="(Optional)">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Degree / Designation</label>
                                    <div class="position-relative">
                                        <i class="bi bi-award input-icon"></i>
                                        <input type="text" name="degree_designation" class="form-control" value="<?= htmlspecialchars($_POST['degree_designation'] ?? '') ?>" placeholder="e.g. MIT">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 position-relative">
                                <label class="form-label fw-semibold text-dark small">Username <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <i class="bi bi-at input-icon"></i>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Choose a username">
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-lock input-icon"></i>
                                        <input type="password" name="password" id="pw_i" class="form-control" placeholder="Min. 6 chars">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-lock-fill input-icon"></i>
                                        <input type="password" name="confirm_password" id="cpw_i" class="form-control" placeholder="Re-enter password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="studentFields" class="dynamic-fields">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">First Name <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" name="s_firstname" class="form-control" value="<?= htmlspecialchars($_POST['s_firstname'] ?? '') ?>" placeholder="Juan">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Surname <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" name="s_surname" class="form-control" value="<?= htmlspecialchars($_POST['s_surname'] ?? '') ?>" placeholder="dela Cruz">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Middle Name</label>
                                    <div class="position-relative">
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" name="s_middlename" class="form-control" value="<?= htmlspecialchars($_POST['s_middlename'] ?? '') ?>" placeholder="(Optional)">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">School ID <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-credit-card-2-front input-icon"></i>
                                        <input type="text" name="school_id" class="form-control" value="<?= htmlspecialchars($_POST['school_id'] ?? '') ?>" placeholder="2024-00001">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Year Level <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-mortarboard input-icon"></i>
                                        <select name="s_year_level" class="form-select">
                                            <option value="">-- Select --</option>
                                            <?php 
                                            $yearOptions = ['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year'];
                                            foreach($yearOptions as $val => $label): ?>
                                                <option value="<?= $val ?>" <?= ($_POST['s_year_level']??'')===$val?'selected':'' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Block <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-collection input-icon"></i>
                                        <select name="s_block" class="form-select">
                                            <option value="">-- Select --</option>
                                            <?php foreach(['A','B','C','D','E','F'] as $b): ?>
                                                <option value="<?= $b ?>" <?= ($_POST['s_block']??'')===$b?'selected':'' ?>><?= $b ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Contact No.</label>
                                    <div class="position-relative">
                                        <i class="bi bi-telephone input-icon"></i>
                                        <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" placeholder="09xxxxxxxxx">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Facebook Name</label>
                                    <div class="position-relative">
                                        <i class="bi bi-facebook input-icon"></i>
                                        <input type="text" name="facebook_name" class="form-control" value="<?= htmlspecialchars($_POST['facebook_name'] ?? '') ?>" placeholder="(Optional)">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-lock input-icon"></i>
                                        <input type="password" name="password" id="pw_s" class="form-control" placeholder="Min. 6 chars">
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label fw-semibold text-dark small">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <i class="bi bi-lock-fill input-icon"></i>
                                        <input type="password" name="confirm_password" id="cpw_s" class="form-control" placeholder="Re-enter password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register w-100" id="submitBtn" disabled>Create Account</button>

                        <div class="mt-4 text-center">
                            <p class="text-muted small mb-0">
                                Already have an account? <a href="login.php" class="text-decoration-none fw-semibold text-primary">Sign in here</a>
                            </p>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
const roleSelect       = document.getElementById('roleSelect');
const instructorFields = document.getElementById('instructorFields');
const studentFields    = document.getElementById('studentFields');
const submitBtn        = document.getElementById('submitBtn');
const form             = document.getElementById('regForm');

function toggleFields() {
    const v = roleSelect.value;
    instructorFields.style.display = v === 'INSTRUCTOR' ? 'block' : 'none';
    studentFields.style.display    = v === 'STUDENT'    ? 'block' : 'none';
    validate();
}

function validate() {
    const v = roleSelect.value;
    let ok = false;

    if (v === 'INSTRUCTOR') {
        const fn = form.querySelector('#instructorFields input[name="firstname"]').value.trim();
        const sn = form.querySelector('#instructorFields input[name="surname"]').value.trim();
        const un = form.querySelector('input[name="username"]').value.trim();
        const pw = document.getElementById('pw_i').value;
        const cp = document.getElementById('cpw_i').value;
        ok = fn && sn && un && pw.length >= 6 && pw === cp;
    } else if (v === 'STUDENT') {
        const fn  = form.querySelector('input[name="s_firstname"]').value.trim();
        const sn  = form.querySelector('input[name="s_surname"]').value.trim();
        const sid = form.querySelector('input[name="school_id"]').value.trim();
        const yl  = form.querySelector('select[name="s_year_level"]').value;
        const bl  = form.querySelector('select[name="s_block"]').value;
        const pw  = document.getElementById('pw_s').value;
        const cp  = document.getElementById('cpw_s').value;
        ok = fn && sn && sid && yl && bl && pw.length >= 6 && pw === cp;
    }

    submitBtn.disabled = !ok;
}

roleSelect.addEventListener('change', toggleFields);
form && form.addEventListener('input', validate);
toggleFields();

// Pre-select role if PHP sent back a value
roleSelect.dispatchEvent(new Event('change'));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
