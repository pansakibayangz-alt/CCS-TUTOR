<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

// Fetch admin info
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Count pending approvals for badge
$pendingCount = 0;
try {
    $pi = $pdo->query("SELECT COUNT(*) FROM instructor WHERE status='pending'")->fetchColumn();
    $ps = $pdo->query("SELECT COUNT(*) FROM students WHERE status='pending'")->fetchColumn();
    $pendingCount = (int)$pi + (int)$ps;
} catch(Exception $e) { $pendingCount = 0; }

// VMGO gradient headers
$gradients = [
    'CORE VALUES'              => 'linear-gradient(135deg, #8B0000, #FFD700)',
    'VISION'                   => 'linear-gradient(135deg, #0B3D91, #2E86C1)',
    'MISSION'                  => 'linear-gradient(135deg, #6A1B9A, #D4A0F7)',
    'GOALS'                    => 'linear-gradient(135deg, #145A32, #7BF1A8)',
    'QUALITY POLICY STATEMENT' => 'linear-gradient(135deg, #3A0CA3, #FFD700)',
];

$message = '';
$msgType = '';
$activeTab = 'password'; // default tab

/* ═══════════════════════════════════════════
   POST HANDLER
═══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── 1. CHANGE PASSWORD ── */
    if ($action === 'change_password') {
        $activeTab   = 'password';
        $userType    = $_POST['user_type'] ?? '';
        $userId      = trim($_POST['user_id'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($userType) || empty($userId) || empty($newPassword)) {
            $message = "Please fill in all fields.";
            $msgType = "danger";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                if ($userType === 'student') {
                   $stmt = $pdo->prepare("SELECT student_id FROM students WHERE school_id = ?");
                    $stmt->execute([$userId]);
                    if ($stmt->rowCount() > 0) {
                        $pdo->prepare("UPDATE students SET password = ? WHERE school_id = ?")
                            ->execute([$hashedPassword, $userId]);
                        $message = "Student password updated successfully.";
                        $msgType = "success";
                    } else {
                        $message = "Student with School ID '$userId' not found.";
                        $msgType = "danger";
                    }
                } elseif ($userType === 'instructor') {
                   $stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
                    $stmt->execute([$userId]);
                    if ($stmt->rowCount() > 0) {
                        $pdo->prepare("UPDATE instructor SET password = ? WHERE username = ?")
                            ->execute([$hashedPassword, $userId]);
                        $message = "Instructor password updated successfully.";
                        $msgType = "success";
                    } else {
                        $message = "Instructor with username '$userId' not found.";
                        $msgType = "danger";
                    }
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $msgType = "danger";
            }
        }

    /* ── 2. ADD STUDENT ── */
    } elseif ($action === 'add_student') {
        $activeTab  = 'add_student';
        $firstname  = trim($_POST['s_firstname'] ?? '');
        $surname    = trim($_POST['s_surname']   ?? '');
        $school_id  = trim($_POST['s_school_id'] ?? '');

        if (empty($firstname) || empty($surname) || empty($school_id)) {
            $message = "Please fill in all student fields.";
            $msgType = "danger";
        } else {
            try {
                // Check duplicate school_id
              $chk = $pdo->prepare("SELECT student_id FROM students WHERE school_id = ?");
                $chk->execute([$school_id]);
                if ($chk->rowCount() > 0) {
                    $message = "A student with School ID '$school_id' already exists.";
                    $msgType = "danger";
                } else {
                    // Default password = school_id (student must change on first login)
                    $defaultPw = password_hash($school_id, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare(
                        "INSERT INTO students (firstname, surname, school_id, password) VALUES (?, ?, ?, ?)"
                    );
                    $ins->execute([$firstname, $surname, $school_id, $defaultPw]);
                    $message = "Student '$firstname $surname' added. Default password is their School ID: <strong>$school_id</strong>";
                    $msgType = "success";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $msgType = "danger";
            }
        }

    /* ── 3. ADD INSTRUCTOR ── */
    } elseif ($action === 'add_instructor') {
        $activeTab  = 'add_instructor';
        $firstname  = trim($_POST['i_firstname']  ?? '');
        $surname    = trim($_POST['i_surname']    ?? '');
        $iusername  = trim($_POST['i_username']   ?? '');
        $department = trim($_POST['i_department'] ?? '');

        if (empty($firstname) || empty($surname) || empty($iusername)) {
            $message = "Please fill in all instructor fields.";
            $msgType = "danger";
        } else {
            try {
                // Check duplicate username
                $chk = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
                $chk->execute([$iusername]);
                if ($chk->rowCount() > 0) {
                    $message = "An instructor with username '$iusername' already exists.";
                    $msgType = "danger";
                } else {
                    // Default password = username
                    $defaultPw = password_hash($iusername, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare(
                        "INSERT INTO instructor (firstname, surname, username, department, password) VALUES (?, ?, ?, ?, ?)"
                    );
                    $ins->execute([$firstname, $surname, $iusername, $department, $defaultPw]);
                    $message = "Instructor '$firstname $surname' added. Default password is their username: <strong>$iusername</strong>";
                    $msgType = "success";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $msgType = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
/* ── GLOBAL THEME ── */
:root {
    --navy: #0b2b4a;
    --gold: #FFD700;
    --muted: rgba(255,255,255,0.9);
    --card-bg: rgba(255,255,255,0.04);
    --glass-border: rgba(255,230,0,0.12);
}

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
}

/* NAVBAR */
.navbar-custom {
    background: linear-gradient(90deg,#071B2A,#08304F);
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
.navbar-brand {
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    font-weight: 700;
}
.navbar-custom .nav-link {
    color: rgba(255,255,255,0.9);
    text-transform: uppercase;
    font-size: .83rem;
    font-weight: 600;
}
.navbar-custom .nav-link:hover { color: var(--gold); text-decoration: underline; }

/* LIVE TIME BAR */
#liveDateTimeBar {
    width: 100%;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(6px);
    padding: 10px 0;
    text-align: center;
    color: var(--gold);
    font-weight: 700;
    border-bottom: 1px solid rgba(255,215,0,0.25);
}

/* PAGE WRAPPER */
.container-main {
    max-width: 1200px;
    margin: 36px auto 90px;
    padding: 0 18px;
    display: flex;
    gap: 25px;
}

/* LEFT COLUMN */
.left-column { flex: 3; }

/* RIGHT COLUMN */
.right-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 25px;
    min-width: 280px;
}

/* QUICK ACTIONS */
.quick-actions {
    padding: 18px;
    border-radius: 12px;
    background: var(--card-bg);
    border: 1px solid var(--glass-border);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 6px 24px rgba(2,8,23,0.45);
}
.quick-actions h3 {
    font-family: 'Merriweather', serif;
    color: var(--gold);
    margin-bottom: 14px;
    font-size: 1rem;
}
.quick-actions button {
    width: 100%;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    background: linear-gradient(90deg,#FFD700,#C9A000);
    color: #071A2A;
    cursor: pointer;
}
.quick-actions button:hover { background: linear-gradient(90deg,#C9A000,#FFD700); }

/* ADMIN PANEL CARD */
.admin-card {
    background: var(--card-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 18px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 6px 24px rgba(2,8,23,0.45);
}
.admin-card h3 {
    font-family: 'Merriweather', serif;
    color: var(--gold);
    margin-bottom: 6px;
    font-size: 1rem;
}

/* TAB BUTTONS */
.tab-row {
    display: flex;
    gap: 6px;
    margin-bottom: 14px;
    border-bottom: 1px solid rgba(255,215,0,0.12);
    padding-bottom: 10px;
    flex-wrap: wrap;
}
.tab-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid rgba(255,215,0,0.20);
    background: transparent;
    color: rgba(255,255,255,0.7);
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
}
.tab-btn.active,
.tab-btn:hover {
    background: linear-gradient(90deg,#FFD700,#C9A000);
    color: #071A2A;
    border-color: transparent;
}

/* PANEL SECTIONS */
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* FORM CONTROLS */
.form-control, .form-select {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,215,0,0.15);
    color: #fff;
    font-size: .87rem;
}
.form-control:focus, .form-select:focus {
    background: rgba(255,255,255,0.08);
    border-color: var(--gold);
    box-shadow: 0 0 0 0.2rem rgba(255,215,0,0.20);
    color: #fff;
}
.form-control::placeholder { color: rgba(255,255,255,0.4); }
.form-select option { background: #0B2540; color: #fff; }

.form-label { font-size: .78rem; color: rgba(255,215,0,0.85); margin-bottom: 4px; font-weight: 600; }

.btn-gold {
    background: linear-gradient(90deg,#FFD700,#C9A000);
    color: #071A2A;
    font-weight: 600;
    border: none;
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all .2s;
}
.btn-gold:hover { background: linear-gradient(90deg,#C9A000,#FFD700); }

/* PROFILE CARD */
.profile-card {
    display: flex;
    gap: 14px;
    padding: 18px;
    border-radius: 12px;
    background: var(--card-bg);
    border: 1px solid var(--glass-border);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 6px 24px rgba(2,8,23,0.45);
}
.profile-avatar {
    width: 78px; height: 78px;
    border-radius: 10px;
    background: linear-gradient(135deg,#08273E,#0B3B66);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold);
    font-family: 'Merriweather', serif;
    font-size: 22px; font-weight: 700;
    flex-shrink: 0;
}

/* SECTION TITLES */
.section-title {
    margin: 28px 0 12px;
    font-family: 'Merriweather', serif;
    color: white;
    font-size: 1.1rem;
}

/* VMGO COLLAPSE STYLE */
.vmgo-header {
    padding: 14px;
    border-radius: 10px;
    color: #071A2A;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 8px;
    box-shadow: 0 6px 18px rgba(2,8,23,0.15);
}
.vmgo-body {
    background: rgba(255,255,255,0.04);
    padding: 18px;
    border-radius: 10px;
    border: 1px solid rgba(255,215,0,0.06);
    margin-bottom: 12px;
}

/* ALERT OVERRIDE for dark theme */
.alert-success { background: rgba(40,167,69,0.15); border-color: rgba(40,167,69,0.4); color: #7aff9e; }
.alert-danger  { background: rgba(220,53,69,0.15); border-color: rgba(220,53,69,0.40); color: #ff9090; }

/* FOOTER */
.footer-fixed {
    position: fixed;
    bottom: 0; left: 0; width: 100%;
    background: linear-gradient(90deg,#071B2A,#08304F);
    border-top: 1px solid rgba(255,215,0,0.06);
    padding: 10px 18px;
    color: rgba(255,255,255,0.8);
    display: flex; justify-content: center;
    z-index: 1000;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .container-main { flex-direction: column; }
    .right-column { min-width: unset; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">

    <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px; width:auto;">
        <img src="ccs.png"   alt="CCS Logo"   style="height:36px; width:auto;">
        <span style="font-family:'Merriweather',serif;font-size:1.35rem;font-weight:800;
                     letter-spacing:1px;color:var(--gold);text-shadow:0 0 6px rgba(255,215,0,0.45);">
            CSTUTORHUB — ADMIN
        </span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
        <svg width="28" height="28" fill="none">
          <path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
    </button>

    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="admin_about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_manage_instructors.php">Instructors</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_manage_students.php">Students</a></li>
        <li class="nav-item">
            <a class="nav-link" href="admin_pending_approvals.php">
                Approvals
                <?php if($pendingCount > 0): ?>
                    <span style="background:#f59e0b;color:#000;font-weight:700;font-size:.72rem;padding:2px 8px;border-radius:20px;margin-left:4px;"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="admin_feedback.php">Feedback</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>

  </div>
</nav>

<div id="liveDateTimeBar">Loading date &amp; time...</div>

<div class="container-main">

    <!-- ═══ LEFT COLUMN ═══ -->
    <div class="left-column">

        <!-- Profile -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?php
                    echo strtoupper(
                        substr($admin['firstname'] ?? 'A', 0, 1) .
                        substr($admin['surname']   ?? 'D', 0, 1)
                    );
                ?>
            </div>
            <div>
                <h5 style="font-family:'Merriweather',serif;color:var(--gold);margin:0;">
                    <?= htmlspecialchars(($admin['firstname'] ?? '') . ' ' . ($admin['surname'] ?? '')) ?>
                </h5>
                <p class="mb-0">Username: <b><?= htmlspecialchars($admin['username']) ?></b></p>
                <p class="mb-0">Role: <b>Administrator</b></p>
            </div>
        </div>

        <h4 class="section-title">JRMSU VMGO</h4>

        <div class="vmgo-header" style="background:<?= $gradients['CORE VALUES'] ?>"
             data-bs-toggle="collapse" data-bs-target="#vmgo0">🔹 CORE VALUES</div>
        <div id="vmgo0" class="collapse">
            <div class="vmgo-body">
                <p><strong>R</strong> – Resilience</p>
                <p><strong>I</strong> – Integrity</p>
                <p><strong>Z</strong> – Zeal for Excellence</p>
                <p><strong>A</strong> – Altruism</p>
                <p><strong>L</strong> – Leadership</p>
            </div>
        </div>

        <div class="vmgo-header" style="background:<?= $gradients['VISION'] ?>"
             data-bs-toggle="collapse" data-bs-target="#vmgo1">🔹 VISION</div>
        <div id="vmgo1" class="collapse">
            <div class="vmgo-body">
                A Smart UniverCity, locally and globally, that inspires excellence, fosters innovation, and promotes sustainable development for the betterment of society.
            </div>
        </div>

        <div class="vmgo-header" style="background:<?= $gradients['MISSION'] ?>"
             data-bs-toggle="collapse" data-bs-target="#vmgo2">🔹 MISSION</div>
        <div id="vmgo2" class="collapse">
            <div class="vmgo-body">
                Jose Rizal Memorial State University pledges to deliver effective and efficient services along instruction, research, extension, and production. It commits to provide a Smart UniverCity to foster adaptable learning with technology and innovation for student success, forge partnerships to tackle challenges and drive sustainable progress, and ensure academic excellence, ethics, and accountability in all operations.
            </div>
        </div>

        <div class="vmgo-header" style="background:<?= $gradients['GOALS'] ?>"
             data-bs-toggle="collapse" data-bs-target="#vmgo3">🔹 GOALS</div>
        <div id="vmgo3" class="collapse">
            <div class="vmgo-body">
                <p><strong>S</strong> – Strategic Modernization and Responsive Technologies</p>
                <p><strong>M</strong> – Maximized Stakeholders Glocal Collaboration and Connectivity</p>
                <p><strong>A</strong> – Accountable Participatory Governance and Sound Fiscal Management</p>
                <p><strong>R</strong> – Resilient to internal and external risks and hazards</p>
                <p><strong>T</strong> – Transformative Livability and Inclusive Environment</p>
            </div>
        </div>

        <div class="vmgo-header" style="background:<?= $gradients['QUALITY POLICY STATEMENT'] ?>"
             data-bs-toggle="collapse" data-bs-target="#vmgo4">🔹 QUALITY POLICY STATEMENT</div>
        <div id="vmgo4" class="collapse">
            <div class="vmgo-body">
                The Jose Rizal Memorial State University is committed to provide quality instruction, research, extension, and production programs that are relevant and responsive to the needs of the community. It ensures customer satisfaction through continual improvement of services, adherence to ethical standards, and compliance with statutory and regulatory requirements while upholding excellence, integrity, and accountability.
            </div>
        </div>

    </div><!-- /left-column -->

    <!-- ═══ RIGHT COLUMN ═══ -->
    <div class="right-column">

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <button onclick="location.href='admin_manage_instructors.php'">Manage Instructors</button>
            <button onclick="location.href='admin_manage_students.php'">Manage Students</button>
            <button onclick="location.href='admin_manage_lessons.php'">Manage Lessons</button>
            <button onclick="location.href='admin_manage_assessment.php'">Manage Assessments</button>
        </div>

        <!-- Admin Control Panel (tabbed) -->
        <div class="admin-card">
            <h3>Admin Control Panel</h3>

            <!-- TAB BUTTONS -->
            <div class="tab-row">
                <button class="tab-btn <?= $activeTab === 'add_student'   ? 'active' : '' ?>"
                        onclick="switchTab('add_student')">+ Add Student</button>
                <button class="tab-btn <?= $activeTab === 'add_instructor' ? 'active' : '' ?>"
                        onclick="switchTab('add_instructor')">+ Add Instructor</button>
                <button class="tab-btn <?= $activeTab === 'password'       ? 'active' : '' ?>"
                        onclick="switchTab('password')">🔑 Passwords</button>
            </div>

            <!-- ALERT -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $msgType ?> alert-dismissible fade show mb-3"
                     role="alert" style="font-size:.85rem; padding:10px 14px;">
                    <?= $message ?>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- ── PANEL 1: ADD STUDENT ── -->
            <div id="panel_add_student" class="tab-panel <?= $activeTab === 'add_student' ? 'active' : '' ?>">
                <p style="font-size:.78rem; color:rgba(255,255,255,0.55); margin-bottom:12px;">
                    The student's default password will be set to their <strong style="color:var(--gold)">School ID</strong>.
                    They can only log in after being added here.
                </p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_student">
                    <div class="mb-2">
                        <label class="form-label">First Name</label>
                        <input type="text" name="s_firstname" class="form-control"
                               placeholder="e.g. Juan"
                               value="<?= htmlspecialchars($_POST['s_firstname'] ?? '') ?>"
                               required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Surname</label>
                        <input type="text" name="s_surname" class="form-control"
                               placeholder="e.g. dela Cruz"
                               value="<?= htmlspecialchars($_POST['s_surname'] ?? '') ?>"
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">School ID</label>
                        <input type="text" name="s_school_id" class="form-control"
                               placeholder="e.g. 2024-00001"
                               value="<?= htmlspecialchars($_POST['s_school_id'] ?? '') ?>"
                               required>
                        <small style="color:rgba(255,255,255,0.4); font-size:.72rem;">
                            This will also be the student's initial password.
                        </small>
                    </div>
                    <button type="submit" class="btn-gold">Add Student</button>
                </form>
            </div>

            <!-- ── PANEL 2: ADD INSTRUCTOR ── -->
            <div id="panel_add_instructor" class="tab-panel <?= $activeTab === 'add_instructor' ? 'active' : '' ?>">
                <p style="font-size:.78rem; color:rgba(255,255,255,0.55); margin-bottom:12px;">
                    The instructor's default password will be set to their <strong style="color:var(--gold)">Username</strong>.
                    They can only log in after being added here.
                </p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_instructor">
                    <div class="mb-2">
                        <label class="form-label">First Name</label>
                        <input type="text" name="i_firstname" class="form-control"
                               placeholder="e.g. Maria"
                               value="<?= htmlspecialchars($_POST['i_firstname'] ?? '') ?>"
                               required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Surname</label>
                        <input type="text" name="i_surname" class="form-control"
                               placeholder="e.g. Santos"
                               value="<?= htmlspecialchars($_POST['i_surname'] ?? '') ?>"
                               required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="i_username" class="form-control"
                               placeholder="e.g. msantos"
                               value="<?= htmlspecialchars($_POST['i_username'] ?? '') ?>"
                               required>
                        <small style="color:rgba(255,255,255,0.4); font-size:.72rem;">
                            This will also be the instructor's initial password.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department <span style="color:rgba(255,255,255,0.4)">(optional)</span></label>
                        <input type="text" name="i_department" class="form-control"
                               placeholder="e.g. Computer Science"
                               value="<?= htmlspecialchars($_POST['i_department'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-gold">Add Instructor</button>
                </form>
            </div>

            <!-- ── PANEL 3: MANAGE PASSWORDS ── -->
            <div id="panel_password" class="tab-panel <?= $activeTab === 'password' ? 'active' : '' ?>">
                <p style="font-size:.78rem; color:rgba(255,255,255,0.55); margin-bottom:12px;">
                    Reset or update any user's password.
                </p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-2">
                        <label class="form-label">Account Type</label>
                        <select name="user_type" class="form-select" required>
                            <option value="" disabled selected>Select user type...</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">User ID</label>
                        <input type="text" name="user_id" class="form-control"
                               placeholder="School ID / Username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control"
                               placeholder="Enter new password" required>
                    </div>
                    <button type="submit" class="btn-gold">Update Password</button>
                </form>
            </div>

        </div><!-- /admin-card -->

    </div><!-- /right-column -->

</div><!-- /container-main -->

<footer class="footer-fixed">
    Developed by <strong>&nbsp;Limetares's Group&nbsp;</strong> — Thesis S.Y. 2025–2026
</footer>

<script>
/* Tab switching */
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('panel_' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

/* Live date/time */
function updateDateTime() {
    document.getElementById('liveDateTimeBar').innerText =
        new Date().toLocaleString('en-PH', {
            timeZone: 'Asia/Manila',
            dateStyle: 'full',
            timeStyle: 'medium'
        });
}
setInterval(updateDateTime, 1000);
updateDateTime();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
