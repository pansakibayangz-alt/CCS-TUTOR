<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$message = '';
$msgType = '';

/* ══════════════════════════════════════════
   HANDLE APPROVE / REJECT ACTIONS
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $userType   = $_POST['user_type']  ?? '';
    $userId     = intval($_POST['user_id'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');

    if ($action === 'approve') {
        if ($userType === 'instructor') {
            $stmt = $pdo->prepare("UPDATE instructor SET status='approved', rejection_reason=NULL WHERE instructor_id=?");
            $stmt->execute([$userId]);
            $message = "Instructor approved successfully.";
        } elseif ($userType === 'student') {
            $stmt = $pdo->prepare("UPDATE students SET status='approved', rejection_reason=NULL WHERE student_id=?");
            $stmt->execute([$userId]);
            $message = "Student approved successfully.";
        }
        $msgType = 'success';

    } elseif ($action === 'reject') {
        if (empty($reason)) {
            $message = "Please provide a reason for rejection.";
            $msgType = 'danger';
        } else {
            if ($userType === 'instructor') {
                $stmt = $pdo->prepare("UPDATE instructor SET status='rejected', rejection_reason=? WHERE instructor_id=?");
                $stmt->execute([$reason, $userId]);
                $message = "Instructor rejected.";
            } elseif ($userType === 'student') {
                $stmt = $pdo->prepare("UPDATE students SET status='rejected', rejection_reason=? WHERE student_id=?");
                $stmt->execute([$reason, $userId]);
                $message = "Student rejected.";
            }
            $msgType = 'warning';
        }
    }
}

/* ══════════════════════════════════════════
   FETCH PENDING INSTRUCTORS
══════════════════════════════════════════ */
$stmtI = $pdo->query("
    SELECT instructor_id, firstname, middlename, surname, degree_designation,
           username, registered_at
    FROM instructor
    WHERE status = 'pending'
    ORDER BY registered_at ASC
");
$pendingInstructors = $stmtI->fetchAll(PDO::FETCH_ASSOC);

/* ══════════════════════════════════════════
   FETCH PENDING STUDENTS
══════════════════════════════════════════ */
$stmtS = $pdo->query("
    SELECT student_id, firstname, middlename, surname, school_id,
           year_level, block, phone_number, facebook_name, registered_at
    FROM students
    WHERE status = 'pending'
    ORDER BY registered_at ASC
");
$pendingStudents = $stmtS->fetchAll(PDO::FETCH_ASSOC);

$totalPending = count($pendingInstructors) + count($pendingStudents);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Approvals — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --gold: #FFD700;
    --glass-bg: rgba(255,255,255,0.08);
    --glass-border: rgba(255,255,255,0.18);
    --text-muted-w: rgba(255,255,255,0.8);
}
body { font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#071A2A,#0B2540); color:white; margin:0; min-height:100vh; }

/* NAVBAR */
.navbar-custom { background:linear-gradient(90deg,#071B2A,#08304F); border-bottom:1px solid rgba(255,215,0,0.06); box-shadow:0 8px 24px rgba(2,12,27,.45); }
.navbar-brand   { font-family:'Merriweather',serif; font-size:1.2rem; color:var(--gold)!important; font-weight:700; }
.navbar-custom .nav-link { color:var(--text-muted-w); text-transform:uppercase; font-size:.84rem; font-weight:600; }
.navbar-custom .nav-link:hover,
.navbar-custom .nav-link.active { color:var(--gold); text-decoration:underline; }

/* DATE BAR */
#liveDateTimeBar { width:100%; background:rgba(0,0,0,.35); backdrop-filter:blur(6px); padding:9px 0; text-align:center; color:var(--gold); font-weight:700; border-bottom:1px solid rgba(255,215,0,.22); }

/* PAGE HEADER */
.page-header { padding:36px 0 8px; }
.page-header h2 { font-size:1.9rem; font-weight:700; color:var(--gold); }

/* BADGE PILL */
.badge-pending { background:#f59e0b; color:#000; font-weight:700; font-size:.8rem; padding:3px 10px; border-radius:20px; }
.badge-none    { background:rgba(255,255,255,.15); color:rgba(255,255,255,.6); font-size:.8rem; padding:3px 10px; border-radius:20px; }

/* CARD */
.card-custom { background:var(--glass-bg); border:1px solid var(--glass-border); backdrop-filter:blur(14px); border-radius:18px; padding:28px; box-shadow:0 8px 24px rgba(0,0,0,.45); margin-bottom:28px; }
.card-custom h5 { color:var(--gold); font-weight:700; margin-bottom:18px; font-size:1.1rem; }

/* TABLE */
.tbl { width:100%; border-collapse:collapse; }
.tbl thead tr { background:rgba(0,0,0,.4); }
.tbl th { color:var(--gold); font-size:.82rem; padding:10px 12px; text-align:center; white-space:nowrap; border-bottom:1px solid rgba(255,215,0,.2); }
.tbl td { color:white; font-size:.85rem; padding:10px 12px; text-align:center; border-bottom:1px solid rgba(255,255,255,.06); vertical-align:middle; }
.tbl tbody tr:hover { background:rgba(255,255,255,.04); }

/* BUTTONS */
.btn-approve { background:#16a34a; color:#fff; border:none; border-radius:8px; padding:5px 14px; font-size:.82rem; font-weight:600; cursor:pointer; transition:.15s; }
.btn-approve:hover { background:#15803d; }
.btn-reject  { background:#dc2626; color:#fff; border:none; border-radius:8px; padding:5px 14px; font-size:.82rem; font-weight:600; cursor:pointer; transition:.15s; }
.btn-reject:hover  { background:#b91c1c; }

/* EMPTY STATE */
.empty-state { text-align:center; color:rgba(255,255,255,.4); padding:40px 0; font-size:.95rem; }
.empty-state i { font-size:2.5rem; display:block; margin-bottom:10px; color:rgba(255,255,255,.2); }

/* MODAL */
.modal-content { background:#0f2139; border:1px solid rgba(255,215,0,.2); color:white; border-radius:16px; }
.modal-header  { border-bottom:1px solid rgba(255,255,255,.1); }
.modal-footer  { border-top:1px solid rgba(255,255,255,.1); }
.modal-title   { color:var(--gold); font-weight:700; }
.form-label    { color:rgba(255,255,255,.8); }
.form-control  { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:white; border-radius:8px; }
.form-control:focus { background:rgba(255,255,255,.15); border-color:var(--gold); box-shadow:none; color:white; }
.form-control::placeholder { color:rgba(255,255,255,.35); }

footer { position:fixed; bottom:0; width:100%; background:rgba(0,0,0,.55); backdrop-filter:blur(10px); color:white; text-align:center; font-weight:600; border-top:1px solid rgba(255,255,255,.3); padding:10px; z-index:100; }

@media(max-width:768px){
    .tbl { display:block; overflow-x:auto; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
<div class="container-fluid" style="max-width:1300px;margin:0 auto;">
    <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU" style="height:34px;">
        <img src="ccs.png"   alt="CCS"   style="height:34px;">
        <span>CSTUTORHUB — ADMIN</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
        <svg width="28" height="28" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-1">
            <li class="nav-item"><a class="nav-link" href="admin_about.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_manage_instructors.php">Instructors</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_manage_students.php">Students</a></li>
            <li class="nav-item"><a class="nav-link active" href="#">
                Approvals
                <?php if($totalPending > 0): ?>
                    <span class="badge-pending ms-1"><?= $totalPending ?></span>
                <?php endif; ?>
            </a></li>
            <li class="nav-item"><a class="nav-link" href="admin_feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link" style="color:var(--gold);font-weight:700;" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- LIVE DATE BAR -->
<div id="liveDateTimeBar">Loading...</div>

<div class="container pb-5" style="max-width:1200px;">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h2><i class="bi bi-person-check me-2"></i>Pending Approvals
            <?php if($totalPending > 0): ?>
                <span class="badge-pending ms-2"><?= $totalPending ?> pending</span>
            <?php else: ?>
                <span class="badge-none ms-2">All clear</span>
            <?php endif; ?>
        </h2>
        <p style="color:rgba(255,255,255,.5); font-size:.88rem; margin:0;">
            Review and approve or reject new registrations before they can log in.
        </p>
    </div>

    <!-- ALERT -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ══ PENDING INSTRUCTORS ══ -->
    <div class="card-custom">
        <h5><i class="bi bi-person-workspace me-2"></i>Pending Instructors
            <span class="badge-pending ms-2"><?= count($pendingInstructors) ?></span>
        </h5>

        <?php if (empty($pendingInstructors)): ?>
            <div class="empty-state">
                <i class="bi bi-check-circle"></i>
                No pending instructor registrations.
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Degree / Designation</th>
                    <th>Username</th>
                    <th>Registered At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($pendingInstructors as $i => $ins): 
                $fullName = trim($ins['firstname'].' '.($ins['middlename']??'').' '.$ins['surname']);
                if (!empty($ins['degree_designation']) && strtoupper($ins['degree_designation']) !== 'N/A')
                    $fullName .= ', '.$ins['degree_designation'];
            ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($fullName) ?></td>
                    <td><?= htmlspecialchars($ins['degree_designation'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($ins['username']) ?></td>
                    <td><?= $ins['registered_at'] ? date('M d, Y g:i A', strtotime($ins['registered_at'])) : '—' ?></td>
                    <td>
                        <!-- APPROVE -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action"    value="approve">
                            <input type="hidden" name="user_type" value="instructor">
                            <input type="hidden" name="user_id"   value="<?= $ins['instructor_id'] ?>">
                            <button type="submit" class="btn-approve"
                                    onclick="return confirm('Approve <?= addslashes($fullName) ?>?')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        &nbsp;
                        <!-- REJECT (opens modal) -->
                        <button class="btn-reject"
                                onclick="openRejectModal('instructor','<?= $ins['instructor_id'] ?>','<?= addslashes($fullName) ?>')">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ PENDING STUDENTS ══ -->
    <div class="card-custom">
        <h5><i class="bi bi-mortarboard me-2"></i>Pending Students
            <span class="badge-pending ms-2"><?= count($pendingStudents) ?></span>
        </h5>

        <?php if (empty($pendingStudents)): ?>
            <div class="empty-state">
                <i class="bi bi-check-circle"></i>
                No pending student registrations.
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>School ID</th>
                    <th>Year Level</th>
                    <th>Block</th>
                    <th>Contact</th>
                    <th>Facebook</th>
                    <th>Registered At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($pendingStudents as $i => $s): 
                $fullName = trim($s['firstname'].' '.($s['middlename']??'').' '.$s['surname']);
            ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($fullName) ?></td>
                    <td><?= htmlspecialchars($s['school_id']) ?></td>
                    <td><?= htmlspecialchars($s['year_level']) ?></td>
                    <td><?= htmlspecialchars($s['block']) ?></td>
                    <td><?= htmlspecialchars($s['phone_number'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['facebook_name'] ?? '—') ?></td>
                    <td><?= $s['registered_at'] ? date('M d, Y g:i A', strtotime($s['registered_at'])) : '—' ?></td>
                    <td>
                        <!-- APPROVE -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action"    value="approve">
                            <input type="hidden" name="user_type" value="student">
                            <input type="hidden" name="user_id"   value="<?= $s['student_id'] ?>">
                            <button type="submit" class="btn-approve"
                                    onclick="return confirm('Approve <?= addslashes($fullName) ?>?')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        &nbsp;
                        <!-- REJECT -->
                        <button class="btn-reject"
                                onclick="openRejectModal('student','<?= $s['student_id'] ?>','<?= addslashes($fullName) ?>')">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->

<!-- ══ REJECT MODAL ══ -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Registration</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <input type="hidden" name="action"    value="reject">
            <input type="hidden" name="user_type" id="modal_user_type">
            <input type="hidden" name="user_id"   id="modal_user_id">
            <p style="color:rgba(255,255,255,.7); font-size:.9rem; margin-bottom:14px;">
                Rejecting: <strong id="modal_name" style="color:white;"></strong>
            </p>
            <div class="mb-3">
                <label class="form-label">Reason for Rejection <span style="color:#f87171">*</span></label>
                <textarea name="reason" id="modal_reason" class="form-control" rows="3"
                          placeholder="e.g. Incomplete information, duplicate account, etc."
                          required></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger fw-bold">Confirm Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer>Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openRejectModal(userType, userId, fullName) {
    document.getElementById('modal_user_type').value = userType;
    document.getElementById('modal_user_id').value   = userId;
    document.getElementById('modal_name').textContent = fullName;
    document.getElementById('modal_reason').value    = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function updateDateTime() {
    document.getElementById('liveDateTimeBar').innerText =
        new Date().toLocaleString('en-PH', {
            timeZone:'Asia/Manila', dateStyle:'full', timeStyle:'medium'
        });
}
setInterval(updateDateTime, 1000);
updateDateTime();
</script>
</body>
</html>
