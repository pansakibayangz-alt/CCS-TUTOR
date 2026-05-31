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
                $stmt = $pdo->prepare("
                    INSERT INTO instructor
                        (firstname, middlename, surname, degree_designation, username, password, status, registered_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$firstname, $middlename, $surname, $degree, $username, $hash]);
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
                $stmt = $pdo->prepare("
                    INSERT INTO students
                        (firstname, middlename, surname, school_id, phone_number, facebook_name,
                         year_level, block, password, status, registered_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $firstname, $middlename, $surname, $school_id,
                    $phone_number, $facebook, $year_level, $block, $hash
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
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap');

body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #0a1228;
    font-family: "Poppins", sans-serif;
    position: relative;
    overflow-x: hidden;
    padding-bottom: 40px;
}

body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: url('jrmsu.png') center/45% no-repeat;
    opacity: 0.15;
    z-index: 0;
}

.system-title {
    position: relative;
    z-index: 2;
    margin-top: 36px;
    font-family: "Montserrat", sans-serif;
    font-weight: 800;
    font-size: 32px;
    color: white;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: titleFade .8s ease-out forwards;
}
.system-title img { width:65px; height:65px; }

@keyframes titleFade {
    from { opacity:0; transform:translateY(-20px); }
    to   { opacity:1; transform:translateY(0); }
}

.container-box {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 480px;
    background: rgba(255,255,255,0.10);
    backdrop-filter: blur(16px);
    border-radius: 20px;
    padding: 36px 32px;
    box-shadow: 0 0 40px rgba(0,0,0,0.5);
    margin-top: 28px;
    animation: formFade .7s ease-out forwards;
}
@keyframes formFade {
    from { opacity:0; transform:translateY(30px); }
    to   { opacity:1; transform:translateY(0); }
}

.form-label { color: rgba(255,255,255,0.85); font-size:.87rem; margin-bottom:4px; }
.form-control, .form-select {
    height: 46px;
    padding-left: 40px;
    border-radius: 10px;
    border: 1px solid #d6ddff;
    background: rgba(255,255,255,0.92);
}
.form-control:focus, .form-select:focus {
    border-color: #1a34ff;
    box-shadow: 0 0 0 3px rgba(26,52,255,.18);
}
.icon-wrap {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #164bff;
    font-size: 17px;
}
.pos-rel { position: relative; }

.btn-register {
    width: 100%;
    height: 48px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #1a34ff, #0066ff);
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    transition: .2s;
    cursor: pointer;
}
.btn-register:hover { background: linear-gradient(135deg, #0d24c1, #0044cc); }
.btn-register:disabled { opacity:.5; cursor:not-allowed; }

.success-box {
    background: rgba(34,197,94,.18);
    border: 1px solid rgba(34,197,94,.5);
    border-radius: 16px;
    padding: 36px 28px;
    text-align: center;
    color: white;
}
.success-box .bi { font-size: 56px; color: #4ade80; margin-bottom: 14px; display:block; }
.success-box h4 { color: #4ade80; font-weight: 700; margin-bottom: 10px; }
.success-box p  { color: rgba(255,255,255,.8); font-size:.92rem; margin:0; }

.dynamic-fields { display:none; }
</style>
</head>
<body>

<div class="system-title">
    CS TUTORING HUB
    <img src="ccs.png" alt="">
</div>

<div class="container-box">

<?php if ($success): ?>
    <!-- ── SUCCESS STATE ── -->
    <div class="success-box">
        <i class="bi bi-hourglass-split"></i>
        <h4>Registration Submitted!</h4>
        <p>
            Your <strong><?= $success === 'INSTRUCTOR' ? 'Instructor' : 'Student' ?></strong>
            account is now <strong>pending approval</strong>.<br><br>
            Please wait for the <strong>Admin</strong> to review and approve your account
            before you can log in.
        </p>
        <a href="login.php" class="btn btn-outline-light mt-4" style="border-radius:10px; font-weight:600;">
            <i class="bi bi-arrow-left-circle me-1"></i> Back to Login
        </a>
    </div>

<?php else: ?>
    <!-- ── FORM ── -->
    <h3 class="text-white fw-bold text-center mb-1" style="font-size:28px;">REGISTER</h3>
    <p class="text-light text-center mb-3" style="font-size:.85rem;">Create your account</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="regForm">

        <!-- ROLE SELECT -->
        <div class="mb-3">
            <label class="form-label">Select Role <span style="color:#f87171">*</span></label>
            <div class="pos-rel">
                <span class="icon-wrap"><i class="bi bi-person-badge"></i></span>
                <select name="role" id="roleSelect" class="form-select" required>
                    <option value="">-- Select Role --</option>
                    <option value="INSTRUCTOR" <?= $role==='INSTRUCTOR'?'selected':'' ?>>Instructor</option>
                    <option value="STUDENT"    <?= $role==='STUDENT'   ?'selected':'' ?>>Student</option>
                </select>
            </div>
        </div>

        <!-- ── INSTRUCTOR FIELDS ── -->
        <div id="instructorFields" class="dynamic-fields">

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">First Name <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-person"></i></span>
                        <input type="text" name="firstname" class="form-control"
                               value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" placeholder="Juan">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Surname <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-person"></i></span>
                        <input type="text" name="surname" class="form-control"
                               value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" placeholder="dela Cruz">
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">Middle Name</label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-person"></i></span>
                        <input type="text" name="middlename" class="form-control"
                               value="<?= htmlspecialchars($_POST['middlename'] ?? '') ?>" placeholder="(optional)">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Degree / Designation</label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-award"></i></span>
                        <input type="text" name="degree_designation" class="form-control"
                               value="<?= htmlspecialchars($_POST['degree_designation'] ?? '') ?>" placeholder="e.g. MIT">
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label">Username <span style="color:#f87171">*</span></label>
                <div class="pos-rel">
                    <span class="icon-wrap"><i class="bi bi-at"></i></span>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="e.g. jdelacruz">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">Password <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pw_i" class="form-control" placeholder="Min. 6 chars">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Confirm Password <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" id="cpw_i" class="form-control" placeholder="Re-enter">
                    </div>
                </div>
            </div>
        </div>

        <!-- ── STUDENT FIELDS ── -->
        <div id="studentFields" class="dynamic-fields">

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">First Name <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-person"></i></span>
                        <input type="text" name="s_firstname" class="form-control"
                               value="<?= htmlspecialchars($_POST['s_firstname'] ?? '') ?>" placeholder="Juan">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Surname <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-person"></i></span>
                        <input type="text" name="s_surname" class="form-control"
                               value="<?= htmlspecialchars($_POST['s_surname'] ?? '') ?>" placeholder="dela Cruz">
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">Middle Name</label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-person"></i></span>
                        <input type="text" name="s_middlename" class="form-control"
                               value="<?= htmlspecialchars($_POST['s_middlename'] ?? '') ?>" placeholder="(optional)">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">School ID <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-credit-card-2-front"></i></span>
                        <input type="text" name="school_id" class="form-control"
                               value="<?= htmlspecialchars($_POST['school_id'] ?? '') ?>" placeholder="2024-00001">
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">Year Level <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-mortarboard"></i></span>
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
                <div class="col-6">
                    <label class="form-label">Block <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-collection"></i></span>
                        <select name="s_block" class="form-select">
                            <option value="">-- Select --</option>
                            <?php foreach(['A','B','C','D','E','F'] as $b): ?>
                                <option value="<?= $b ?>" <?= ($_POST['s_block']??'')===$b?'selected':'' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">Contact No.</label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone_number" class="form-control"
                               value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" placeholder="09xxxxxxxxx">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Facebook Name</label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-facebook"></i></span>
                        <input type="text" name="facebook_name" class="form-control"
                               value="<?= htmlspecialchars($_POST['facebook_name'] ?? '') ?>" placeholder="(optional)">
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">Password <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pw_s" class="form-control" placeholder="Min. 6 chars">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Confirm Password <span style="color:#f87171">*</span></label>
                    <div class="pos-rel">
                        <span class="icon-wrap"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" id="cpw_s" class="form-control" placeholder="Re-enter">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-register" id="submitBtn" disabled>REGISTER</button>

        <p class="text-center mt-3 mb-0">
            <a href="login.php" class="text-info" style="font-size:.88rem;">Already have an account? Login</a>
        </p>

    </form>
<?php endif; ?>

</div><!-- /container-box -->

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
