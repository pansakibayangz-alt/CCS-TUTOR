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
        width: 41.666667%; /* matches col-lg-5 */
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

                <?php if ($success): ?>
                    <div class="success-box">
                        <i class="bi bi-check-circle-fill shadow-sm rounded-circle d-inline-block bg-white text-success"></i>
                        <h2 class="fw-bold text-dark mb-3">Registration Successful!</h2>
                        <p class="text-muted fs-5 mb-4">
                            Your <strong><?= $success === 'INSTRUCTOR' ? 'Instructor' : 'Student' ?></strong> account has been created and is currently <strong>pending approval</strong>.
                        </p>
                        <p class="text-muted mb-5">An administrator will review your details shortly. You will be able to log in once approved.</p>
                        <a href="login.php" class="btn btn-register w-100 d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Return to Login
                        </a>
                    </div>
                <?php else: ?>

                    <div class="mb-5">
                        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -1px;">Create Account</h1>
                        <p class="text-secondary fs-6">Please fill in your information to get started.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3 border-0 shadow-sm d-flex align-items-center p-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                            <div><?= htmlspecialchars($error) ?></div>
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
                                    <label class="form-label">Middle Name</label>
                                    <div class="position-relative">
                                        <input type="text" name="middlename" class="form-control" value="<?= htmlspecialchars($_POST['middlename'] ?? '') ?>" placeholder="(Optional)">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Degree / Designation</label>
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
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Choose a unique username">
                                    <i class="bi bi-at input-icon"></i>
                                </div>
                            </div>
                            <div class="row g-3 mb-5">
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
                        </div>

                        <div id="studentFields" class="dynamic-fields">
                            <div class="section-divider">Personal Details</div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="s_firstname" class="form-control" value="<?= htmlspecialchars($_POST['s_firstname'] ?? '') ?>" placeholder="Juan">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Surname <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="s_surname" class="form-control" value="<?= htmlspecialchars($_POST['s_surname'] ?? '') ?>" placeholder="Dela Cruz">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Middle Name</label>
                                    <div class="position-relative">
                                        <input type="text" name="s_middlename" class="form-control" value="<?= htmlspecialchars($_POST['s_middlename'] ?? '') ?>" placeholder="(Optional)">
                                        <i class="bi bi-person input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact No.</label>
                                    <div class="position-relative">
                                        <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" placeholder="09xxxxxxxxx">
                                        <i class="bi bi-telephone input-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="section-divider">Academic Info</div>

                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="form-label">School ID <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="text" name="school_id" class="form-control" value="<?= htmlspecialchars($_POST['school_id'] ?? '') ?>" placeholder="e.g. 2024-00001">
                                        <i class="bi bi-credit-card-2-front input-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Year Level <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <select name="s_year_level" class="form-select">
                                            <option value="">-- Select --</option>
                                            <?php 
                                            $yearOptions = ['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year'];
                                            foreach($yearOptions as $val => $label): ?>
                                                <option value="<?= $val ?>" <?= ($_POST['s_year_level']??'')===$val?'selected':'' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="bi bi-mortarboard input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Block <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <select name="s_block" class="form-select">
                                            <option value="">-- Select --</option>
                                            <?php foreach(['A','B','C','D','E','F'] as $b): ?>
                                                <option value="<?= $b ?>" <?= ($_POST['s_block']??'')===$b?'selected':'' ?>><?= $b ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="bi bi-collection input-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="section-divider">Account Security</div>

                            <div class="mb-3">
                                <label class="form-label">Facebook Profile Name</label>
                                <div class="position-relative">
                                    <input type="text" name="facebook_name" class="form-control" value="<?= htmlspecialchars($_POST['facebook_name'] ?? '') ?>" placeholder="For communication (Optional)">
                                    <i class="bi bi-facebook input-icon"></i>
                                </div>
                            </div>
                            <div class="row g-3 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="password" id="pw_s" class="form-control" placeholder="Min. 6 characters">
                                        <i class="bi bi-shield-lock input-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="confirm_password" id="cpw_s" class="form-control" placeholder="Re-enter password">
                                        <i class="bi bi-shield-check input-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register w-100 mb-4" id="submitBtn" disabled>
                            Complete Registration <i class="bi bi-arrow-right ms-2"></i>
                        </button>

                        <div class="text-center pb-4">
                            <p class="text-secondary mb-0">
                                Already have an account? 
                                <a href="login.php" class="text-decoration-none fw-bold text-primary" style="letter-spacing: 0.5px;">Sign in here</a>
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
    
    // Remove active class for animation resets
    instructorFields.classList.remove('active');
    studentFields.classList.remove('active');

    if (v === 'INSTRUCTOR') {
        instructorFields.classList.add('active');
    } else if (v === 'STUDENT') {
        studentFields.classList.add('active');
    }
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
if (roleSelect.value !== '') {
    roleSelect.dispatchEvent(new Event('change'));
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
