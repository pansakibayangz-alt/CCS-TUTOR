<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php'; 

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instructor) {
    die("Instructor not found in database.");
}

$instructor_id = $instructor['instructor_id'];

// HANDLE POST REQUESTS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $student_id     = $_POST['student_id'];
    $surname        = $_POST['surname'];
    $firstname      = $_POST['firstname'];
    $middlename     = $_POST['middlename'];
    $facebook_name  = $_POST['facebook_name'];
    $phone_number   = $_POST['phone_number'];

    $stmt = $pdo->prepare("
        UPDATE students 
        SET surname=?, firstname=?, middlename=?, facebook_name=?, phone_number=? 
        WHERE student_id=? AND instructor_id=?
    ");
    $stmt->execute([$surname, $firstname, $middlename, $facebook_name, $phone_number, $student_id, $instructor_id]);

    header("Location: instructor_manage_students.php?action=saved");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id=? AND instructor_id=?");
    $stmt->execute([$student_id, $instructor_id]);
    header("Location: instructor_manage_students.php?action=deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_to_me'])) {
    $student_id = $_POST['student_id'];
    $stmt = $pdo->prepare("UPDATE students SET instructor_id=? WHERE student_id=? AND (instructor_id IS NULL OR instructor_id=0)");
    $stmt->execute([$instructor_id, $student_id]);
    header("Location: instructor_manage_students.php?action=assigned");
    exit;
}

// ---------------- FILTERS & SEARCH ----------------
$filter = '';
$params = [];

if ((isset($_GET['year_level']) && $_GET['year_level'] !== '') || (isset($_GET['block']) && $_GET['block'] !== '')) {
    $filter .= " AND 1=1"; // placeholder for easy AND
    if (!empty($_GET['year_level'])) {
        $filter .= " AND year_level=?";
        $params[] = $_GET['year_level'];
    }
    if (!empty($_GET['block'])) {
        $filter .= " AND block=?";
        $params[] = $_GET['block'];
    }
}

// Text search
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $filter .= " AND (surname LIKE ? OR firstname LIKE ? OR middlename LIKE ?)";
    $search = '%' . trim($_GET['q']) . '%';
    $params = array_merge($params, [$search, $search, $search]);
}

// Fetch students assigned to this instructor
$stmt = $pdo->prepare("SELECT * FROM students WHERE instructor_id=? $filter ORDER BY year_level ASC, block ASC, surname ASC");
$stmt->execute(array_merge([$instructor_id], $params));
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group students by year_level + block
$groupedStudents = [];
foreach ($students as $student) {
    $key = $student['year_level'] . $student['block'];
    if (!isset($groupedStudents[$key])) {
        $groupedStudents[$key] = [
            'year_level' => $student['year_level'],
            'block' => $student['block'],
            'students' => []
        ];
    }
    $groupedStudents[$key]['students'][] = $student;
}

// Fetch unassigned students
$unassignedStmt = $pdo->query("SELECT * FROM students WHERE instructor_id IS NULL OR instructor_id=0 ORDER BY year_level ASC, block ASC, surname ASC");
$unassignedStudents = $unassignedStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique year_level & block for dropdown
$dropdownStmt = $pdo->prepare("SELECT DISTINCT year_level, block FROM students WHERE instructor_id=? ORDER BY year_level, block");
$dropdownStmt->execute([$instructor_id]);
$yearBlockOptions = $dropdownStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instructor - Student List</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

:root{
    --navy: #0b2b4a;
    --navy-2: #08304f;

    --gold: #FFD700;
    --muted: rgba(255,255,255,0.9);

    --card-bg: rgba(255,255,255,0.04);
    --glass-border: rgba(255,230,0,0.14);
}

/* BODY */
body{
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
    margin:0;
    min-height:100vh;
}
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
}

/* NAVBAR (Copied from dashboard) */
.navbar-custom{
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
.navbar-brand{
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    letter-spacing: 0.6px;
    font-weight:700;
}
.navbar-custom .nav-link{
    color: rgba(255,255,255,0.9);
    font-weight:600;
    text-transform:uppercase;
    font-size:0.83rem;
}
.navbar-custom .nav-link:hover{
    color: var(--gold);
    text-decoration:underline;
}
.link-gold { color: var(--gold); font-weight:700; }
.link-gold:hover{ text-decoration:underline; color:#ffea85; }

/* MAIN CONTAINER */
.table-container{
    background: var(--card-bg);
    border:1px solid var(--glass-border);
    padding:32px;
    border-radius:14px;
    max-width:1200px;
    margin:40px auto;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 8px 32px rgba(2,8,23,0.55);
    backdrop-filter: blur(8px) saturate(120%);
}

/* TITLE */
h2{
    font-family:'Merriweather', serif;
    color: var(--gold);
    letter-spacing:0.4px;
}

/* TABLE (JRMSU Navy + Gold Theme) */
table{
    color:white;
}
table thead{
    background: rgba(7,27,42,0.85);
    color: var(--gold);
    font-weight:700;
    border-bottom: 2px solid var(--gold);
}
table tbody tr:hover{
    background: rgba(255,215,0,0.14) !important;
}
table td input{
    background: rgba(255,255,255,0.85);
    border:none;
    font-weight:500;
    border-radius:6px;
}
table td input:disabled{
    background: rgba(255,255,255,0.5);
}
.editable-field:focus{
    border:2px solid var(--gold) !important;
}

/* GROUP HEADER */
.group-header{
    font-size:1.4rem;
    color:var(--gold);
    font-family:'Merriweather', serif;
    text-shadow: 0px 0px 3px rgba(0,0,0,0.8);
    margin-top:40px;
}

/* BUTTONS */
.btn-warning{
    background-color: var(--gold);
    color:#000;
    font-weight:600;
}
.btn-warning:hover{
    background-color:#e4c200;
}
.btn-danger, .btn-success{
    font-weight:600;
}

/* UNASSIGNED BOX */
.unassigned-box{
    font-family:'Merriweather', serif;
    background: rgba(255,255,255,0.06);
    border:1px solid var(--gold);
    border-left:5px solid var(--gold);
    padding:18px;
    border-radius:12px;
    backdrop-filter: blur(6px);
}

/* Fade-in animation */
.fade-in{
    animation: fadeUp .55s ease both;
}
@keyframes fadeUp{
    from{ opacity:0; transform:translateY(10px); }
    to{ opacity:1; transform:translateY(0); }
}

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

/* Footer */
footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(10px);
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
    border-top: 1px solid rgba(255,255,255,0.3);
}


</style>

</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">
   <a class="navbar-brand d-flex align-items-center gap-2" href="instructor_dashboard.php">
    <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px; width:auto;">
    <img src="ccs.png" alt="CCS Logo" style="height:36px; width:auto;">
    <span>CSTUTORHUB — INSTRUCTOR</span>
   </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false"
            aria-label="Toggle navigation">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18"
              stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">About</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_students.php">Students</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">Lessons</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">Pre-test</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_assessment.php">Assessment</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">Student Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">Feedback</a></li>
        <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_dashboard.php" style="font-size:25px;">🏠</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container mt-5 mb-5 table-container">

<?php if (!empty($unassignedStudents)): ?>
<div class="mb-4 text-center">
    <button type="button" class="btn btn-warning fw-bold w-50" data-bs-toggle="modal" data-bs-target="#unassignedModal">
        View Unassigned Students
    </button>
</div>
<?php endif; ?>

<!-- FILTER + LIVE SEARCH -->
<form method="GET" class="mb-3" id="filterForm">
    <div class="row g-2">
        <!-- Year Level Dropdown -->
        <div class="col-md-2">
            <select name="year_level" class="form-select" onchange="document.getElementById('filterForm').submit();">
                <option value="">All Years</option>
                <?php
                $seenYears = [];
                foreach($yearBlockOptions as $opt) {
                    if (!in_array($opt['year_level'], $seenYears)) {
                        $selected = (isset($_GET['year_level']) && $_GET['year_level']==$opt['year_level']) ? 'selected' : '';
                        echo "<option value='{$opt['year_level']}' $selected>{$opt['year_level']}</option>";
                        $seenYears[] = $opt['year_level'];
                    }
                }
                ?>
            </select>
        </div>

        <!-- Block Dropdown -->
        <div class="col-md-2">
            <select name="block" class="form-select" onchange="document.getElementById('filterForm').submit();">
                <option value="">All Blocks</option>
                <?php
                $seenBlocks = [];
                foreach($yearBlockOptions as $opt) {
                    if (!in_array($opt['block'], $seenBlocks)) {
                        $selected = (isset($_GET['block']) && $_GET['block']==$opt['block']) ? 'selected' : '';
                        echo "<option value='{$opt['block']}' $selected>{$opt['block']}</option>";
                        $seenBlocks[] = $opt['block'];
                    }
                }
                ?>
            </select>
        </div>

        <!-- Live Text Search -->
        <div class="col-md-4">
            <input type="text" name="q" id="liveSearch" class="form-control" placeholder="Search by surname, firstname, or middlename" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-warning w-100">Search</button>
        </div>
    </div>
</form>

<?php if (!empty($groupedStudents)): ?>
    <?php foreach($groupedStudents as $group): ?>

        <div class="group-header">
            YEAR LEVEL: <?= $group['year_level']; ?> | BLOCK: <?= $group['block']; ?>
        </div>

        <table class="table table-bordered table-hover text-white align-middle">
            <thead>
                <tr>
                    <th>Surname</th>
                    <th>Firstname</th>
                    <th> Middlename </th>
                    <th>Facebook Name</th>
                    <th>Phone Number</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach($group['students'] as $student): ?>
                <tr>
                    <form method="POST">

                        <td><input type="text" name="surname" class="form-control editable-field" value="<?= htmlspecialchars($student['surname']); ?>" disabled></td>
                        <td><input type="text" name="firstname" class="form-control editable-field" value="<?= htmlspecialchars($student['firstname']); ?>" disabled></td>
                        <td><input type="text" name="middlename" class="form-control editable-field" value="<?= htmlspecialchars($student['middlename']); ?>" disabled></td>
                        <td><input type="text" name="facebook_name" class="form-control editable-field" value="<?= htmlspecialchars($student['facebook_name']); ?>" disabled></td>
                        <td><input type="text" name="phone_number" class="form-control editable-field" value="<?= htmlspecialchars($student['phone_number']); ?>" disabled></td>

                        <td>
    <input type="hidden" name="student_id" value="<?= $student['student_id']; ?>">
    <button type="submit" name="delete_student" class="btn btn-danger btn-delete">Delete</button>
</td>

                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endforeach; ?>
<?php else: ?>
    <p>No students assigned.</p>
<?php endif; ?>

</div>

<footer class="text-center py-3" style="
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(8px);
    color:#fff;
    border-top:1px solid rgba(255,255,255,0.3);
    font-weight:600;
">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---------------- CONFIRM DELETE ----------------
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const confirmDelete = confirm("Are you sure you want to delete this student?");
        if (!confirmDelete) e.preventDefault();
    });
});

// ---------------- LIVE SEARCH (Scoped Per Table) ----------------
document.getElementById('liveSearch').addEventListener('input', function(){
    const query = this.value.toLowerCase();

    document.querySelectorAll('table').forEach(table => {
        table.querySelectorAll('tbody tr').forEach(tr => {
            let text = '';

            // Include text from disabled inputs
            tr.querySelectorAll('td').forEach(td => {
                const input = td.querySelector('input');
                if(input) text += ' ' + input.value.toLowerCase();
                else text += ' ' + td.innerText.toLowerCase();
            });

            tr.style.display = text.includes(query) ? '' : 'none';
        });
    });
});

// ---------------- MODAL LIVE SEARCH (WORKING) ----------------
document.addEventListener('DOMContentLoaded', function() {
    const modalSearchInput = document.getElementById('modalLiveSearch');
    const modalTableRows = document.querySelectorAll('#unassignedModal table tbody tr');

    modalSearchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();

        modalTableRows.forEach(tr => {
            // Combine all text in td except the last column (Action button)
            let text = '';
            tr.querySelectorAll('td:not(:last-child)').forEach(td => {
                text += td.textContent.toLowerCase() + ' ';
            });

            // Show row only if it contains query
            tr.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // Optional: reset search when modal closes
    const unassignedModal = document.getElementById('unassignedModal');
    unassignedModal.addEventListener('hidden.bs.modal', function () {
        modalSearchInput.value = '';
        modalTableRows.forEach(tr => tr.style.display = '');
    });
});
</script>

<script>
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

<!-- Unassigned Students Modal -->
<div class="modal fade" id="unassignedModal" tabindex="-1" aria-labelledby="unassignedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark text-white" style="background: rgba(7,27,42,0.95);">
      <div class="modal-header d-flex flex-column align-items-start gap-2">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    <h5 class="modal-title text-gold" id="unassignedModalLabel">Unassigned Students</h5>
    <input type="text" id="modalLiveSearch" class="form-control" placeholder="Search by surname, firstname, middlename, year, or block" style="width: 100%;">
</div>
      <div class="modal-body">
        <table class="table table-bordered table-hover text-white align-middle">
          <thead>
            <tr>
              <th>Surname</th>
              <th>Firstname</th>
              <th>Middlename</th>
              <th>Year Level</th>
              <th>Block</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($unassignedStudents as $student): ?>
            <tr>
              <td><?= htmlspecialchars($student['surname']); ?></td>
              <td><?= htmlspecialchars($student['firstname']); ?></td>
              <td><?= htmlspecialchars($student['middlename']); ?></td>
              <td><?= $student['year_level']; ?></td>
              <td><?= $student['block']; ?></td>
              <td>
                <form method="POST">
                  <input type="hidden" name="student_id" value="<?= $student['student_id']; ?>">
                  <button type="submit" name="assign_to_me" class="btn btn-warning btn-sm text-dark fw-bold">Assign to Me</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
