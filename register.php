<?php
require_once 'db.php';

// Fetch role options for Admin positions
$adminPositions = ['Associate Dean', 'College Dean', 'Program chair'];
$studentYears = ['1', '2', '3', '4'];
$studentBlocks = ['A', 'B', 'C', 'D', 'E', 'F'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register — BSCS Student Progress System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
/* (NO CHANGES — your styles remain exactly the same) */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #0a1228;
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
body::before {
    content: "";
    position: absolute;
    width: 850px;
    height: 550px;
    background: url("jrmsu.png") no-repeat center;
    background-size: contain;
    opacity: 0.06;
    left: 50%;
    top: 52%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}
.system-title {
    font-size: 30px;
    font-weight: 700;
    color: white;
    margin-bottom: 22px;
    letter-spacing: 1px;
    opacity: 0;
    transform: translateY(-20px);
    animation: titleFade 1s ease-out forwards;
}
@keyframes titleFade {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.glass-box {
    width: 420px;
    max-height: 80vh;
    overflow-y: auto;
    padding-right: 14px;
    background: rgba(255, 255, 255, 0.12);
    padding: 28px;
    border-radius: 12px;
    backdrop-filter: blur(12px);
    box-shadow: 0 0 20px rgba(0,0,0,0.35);
    opacity: 0;
    transform: translateY(40px);
    animation: formFade 0.9s ease-out forwards;
}
@keyframes formFade {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}
.glass-box::-webkit-scrollbar { width: 6px; }
.glass-box::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 20px;
}
.form-label {
    font-weight: 600;
    color: #ffffff;
    font-size: 0.86rem;
}
.form-control,
.form-select {
    background: rgba(255,255,255,0.55);
    border: none;
    border-radius: 8px;
    height: 36px;
    font-size: 0.85rem;
}
.btn-primary {
    width: 100%;
    background: #1a73e8;
    border: none;
    padding: 10px;
    font-weight: 600;
    border-radius: 8px;
}
.btn-primary:hover { background: #155fc0; }
.btn-secondary,
.btn-outline-success {
    border-radius: 8px;
    padding: 8px 14px;
}
.common-field,
.admin-fields,
.instructor-fields,
.student-fields {
    display: none;
}
</style>
</head>

<body>

<div class="system-title">
    CS TUTORING HUB
</div>

<div class="glass-box">

<h4 class="text-center text-white mb-3">Create an Account</h4>

<form action="process_register.php" method="post" id="registerForm" autocomplete="off">

    <!-- ROLE -->
    <div class="mb-3">
        <label class="form-label">Select Role</label>
        <select name="role" id="role" class="form-select" required>
            <option value="">-- Select Role --</option>
            <option value="ADMIN">Admin</option>
            <option value="INSTRUCTOR">Instructor</option>
            <option value="STUDENT">Student</option>
        </select>
    </div>

    <!-- COMMON FIELDS -->
    <div class="mb-3 common-field">
        <label class="form-label">Surname</label>
        <input type="text" name="surname" class="form-control">
    </div>

    <div class="mb-3 common-field">
        <label class="form-label">First Name</label>
        <input type="text" name="firstname" class="form-control">
    </div>

    <div class="mb-3 common-field">
        <label class="form-label">Middle Name</label>
        <input type="text" name="middlename" class="form-control">
    </div>

    <div class="mb-3 common-field">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control">
    </div>

    <div class="mb-3 common-field">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control">
    </div>

    <!-- ADMIN FIELDS -->
    <div class="admin-fields">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="admin_username" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Position</label>
            <select name="position" class="form-select">
                <option value="">-- Select Position --</option>
                <?php
                $stmt = $pdo->prepare("SELECT position FROM admin");
                $stmt->execute();
                $taken = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // ✅ YOUR LOGIC APPLIED HERE
                $hasAssociateDean = in_array('Associate Dean', $taken);
                $hasCollegeDean = in_array('College Dean', $taken);

                if ($hasAssociateDean || $hasCollegeDean) {
                    echo "<option value='Program chair'>Program chair</option>";
                } else {
                    foreach ($adminPositions as $p) {
                        if (!in_array($p, $taken)) {
                            echo "<option value='$p'>$p</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <!-- INSTRUCTOR FIELDS -->
    <div class="instructor-fields">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="instructor_username" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Degree / Designation</label>
            <input type="text" name="degree_designation_instructor" class="form-control" value="N/A">
        </div>
    </div>

    <!-- STUDENT FIELDS -->
    <div class="student-fields">
        <div class="mb-3">
            <label class="form-label">School ID</label>
            <input type="text" name="school_id" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Facebook Name</label>
            <input type="text" name="facebook_name" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone_number" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Year Level</label>
            <select name="year_level" class="form-select">
                <option value="">-- Select Year Level --</option>
                <?php foreach($studentYears as $y): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Block</label>
            <select name="block" class="form-select">
                <option value="">-- Select Block --</option>
                <?php foreach($studentBlocks as $b): ?>
                    <option value="<?= $b ?>"><?= $b ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mb-2">Register</button>

    <div class="d-flex gap-2">
        <button type="reset" class="btn btn-secondary w-50">Clear</button>
        <a href="login.php" class="btn btn-outline-success w-50">Back to Login</a>
    </div>

</form>
</div>

<script>
// (NO CHANGES — your JS remains)
const roleSelect = document.getElementById("role");

const fields = {
    common: document.querySelectorAll(".common-field"),
    admin: document.querySelector(".admin-fields"),
    instructor: document.querySelector(".instructor-fields"),
    student: document.querySelector(".student-fields")
};

function hideAll() {
    fields.common.forEach(el => el.style.display = "none");
    fields.admin.style.display = "none";
    fields.instructor.style.display = "none";
    fields.student.style.display = "none";
    document.querySelectorAll("input, select").forEach(el => el.required = false);
}

function showCommon() {
    fields.common.forEach(el => {
        el.style.display = "block";
        el.querySelectorAll("input, select").forEach(x => x.required = true);
    });
}

roleSelect.addEventListener("change", () => {
    hideAll();
    let role = roleSelect.value;

    if (!role) return;

    showCommon();

    if (role === "ADMIN") {
        fields.admin.style.display = "block";
        fields.admin.querySelectorAll("input, select").forEach(x => x.required = true);
    }
    else if (role === "INSTRUCTOR") {
        fields.instructor.style.display = "block";
        fields.instructor.querySelectorAll("input, select").forEach(x => x.required = true);
    }
    else if (role === "STUDENT") {
        fields.student.style.display = "block";
        fields.student.querySelectorAll("input, select").forEach(x => x.required = true);
    }
});

hideAll();
</script>

</body>
</html>