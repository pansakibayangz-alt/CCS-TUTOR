<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

// Pending approvals badge
$pendingCount = 0;
try {
    $pi = $pdo->query("SELECT COUNT(*) FROM instructor WHERE status='pending'")->fetchColumn();
    $ps = $pdo->query("SELECT COUNT(*) FROM students WHERE status='pending'")->fetchColumn();
    $pendingCount = (int)$pi + (int)$ps;
} catch(Exception $e) { $pendingCount = 0; }

// Fetch distinct year_level and block combinations
$stmtGroups = $pdo->prepare("SELECT DISTINCT year_level, block FROM students ORDER BY year_level, block");
$stmtGroups->execute();
$groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students
$stmtStudents = $pdo->prepare("SELECT * FROM students ORDER BY surname ASC");
$stmtStudents->execute();
$allStudents = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses count per student
$stmtCourseCount = $pdo->prepare("
SELECT year_level, block, COUNT(DISTINCT course_name) AS course_count
FROM lessons
GROUP BY year_level, block
");
$stmtCourseCount->execute();
$courseCountsRaw = $stmtCourseCount->fetchAll(PDO::FETCH_ASSOC);

$courseCounts = [];
foreach($courseCountsRaw as $c) {
    $key = $c['year_level'] . '_' . $c['block'];
    $courseCounts[$key] = $c['course_count'];
}

// Fetch lessons completed per student
$stmtLessonsCompleted = $pdo->prepare("
    SELECT s.school_id, COUNT(DISTINCT l.lesson_id) AS lessons_finished
    FROM students s
    LEFT JOIN lessons l ON s.instructor_id = l.instructor_id AND s.year_level = l.year_level AND s.block = l.block
    LEFT JOIN student_lesson_completion slc ON slc.student_id = s.school_id AND slc.lesson_id = l.lesson_id
    LEFT JOIN student_pretest_attempts spa ON spa.student_id = s.school_id
    LEFT JOIN student_assessment_attempts saa ON saa.student_id = s.school_id
    GROUP BY s.school_id
");
$stmtLessonsCompleted->execute();
$lessonsFinishedRaw = $stmtLessonsCompleted->fetchAll(PDO::FETCH_ASSOC);
$lessonsFinished = [];
foreach($lessonsFinishedRaw as $l) {
    $lessonsFinished[$l['school_id']] = $l['lessons_finished'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{
    --gold: #FFD700;
    --bg-dark: #0A1228;
    --glass-bg: rgba(255, 255, 255, 0.08);
    --glass-border: rgba(255, 255, 255, 0.18);
    --text-muted: rgba(255,255,255,0.85);
}
body { font-family: 'Poppins', sans-serif; background: linear-gradient(180deg,#071A2A,#0B2540); color:white; margin:0; }
.navbar-custom { background: linear-gradient(90deg,#071B2A,#08304F); border-bottom:1px solid rgba(255,215,0,0.06); box-shadow:0 8px 24px rgba(2,12,27,0.45);}
.navbar-brand { font-family:'Merriweather', serif; font-size:1.25rem; color:var(--gold)!important; font-weight:700;}
.navbar-custom .nav-link{ color:var(--text-muted); text-transform: uppercase; font-size: .85rem; font-weight:600;}
.navbar-custom .nav-link:hover, .navbar-custom .nav-link.active{ color:var(--gold); font-weight:700; text-decoration:underline; }

.card-custom { background: var(--glass-bg); border:1px solid var(--glass-border); backdrop-filter:blur(14px); border-radius:18px; padding:30px; box-shadow:0 8px 24px rgba(0,0,0,0.45); margin-bottom:30px;}
h2 { font-family:'Merriweather', serif; color: var(--gold); font-size:1.8rem; margin-bottom:20px; }

table { background: rgba(0,0,0,0.35); color:white; }
table th, table td { vertical-align: middle !important; text-align:center; }
.link-gold { color: #FFD700; font-weight:700; cursor:pointer; }
.link-gold:hover { text-decoration:underline; }
footer { position: fixed; bottom:0; width:100%; background: rgba(0,0,0,0.55); backdrop-filter: blur(10px); color:white; text-align:center; font-weight:600; border-top:1px solid rgba(255,255,255,0.3); padding:10px;}
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

</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
<div class="container-fluid" style="max-width:1200px; margin:0 auto;">
    <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px;">
        <img src="ccs.png" alt="CCS Logo" style="height:36px;">
        <span>CSTUTORHUB — ADMIN</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
        <svg width="28" height="28" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            <li class="nav-item"><a class="nav-link" href="admin_about.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_manage_instructors.php">Instructors</a></li>
            <li class="nav-item"><a class="nav-link active" href="#">Students</a></li>
            <li class="nav-item">
                <a class="nav-link" href="admin_pending_approvals.php">
                    Approvals
                    <?php if($pendingCount > 0): ?>
                        <span style="background:#f59e0b;color:#000;font-weight:700;font-size:.72rem;padding:2px 8px;border-radius:20px;margin-left:4px;"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item"><a class="nav-link" href="admin_feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container mt-5 pb-5">

<div class="mb-4">
    <input type="text" id="searchInput" class="form-control" placeholder="Search Student..." style="max-width:400px;">
</div>

<?php foreach($groups as $group): 
    $year = $group['year_level'];
    $block = $group['block'];
?>
<div class="card card-custom">
    <h4 class="mb-3" style="color:white;">
Year Level: <span style="color:white; font-weight:bold;"><?= $year ?></span style="color:white;"> 
Block: <span style="color:white; font-weight:bold;"><?= $block ?></span>
</h4>
    <table class="table table-bordered table-hover text-white">
        <thead>
            <tr>
                <th>School ID</th>
                <th>Surname</th>
                <th>Firstname</th>
                <th>Middlename</th>
                <th>Contact No.</th>
                <th>Facebook</th>
                <th>No. of Courses Enrolled</th>
                <th>No. of Lessons Finished</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $hasStudent = false;
        foreach($allStudents as $s){
            if($s['year_level']==$year && $s['block']==$block){
                $hasStudent = true;
                $schoolId = $s['school_id'];
        ?>
            <tr>
                <td><?= $schoolId ?></td>
                <td><?= ucfirst(strtolower($s['surname'])) ?></td>
<td><?= ucfirst(strtolower($s['firstname'])) ?></td>
<td><?= ucfirst(strtolower($s['middlename'] ?? '')) ?></td>
                <td><?= $s['phone_number'] ?? '' ?></td>
                <td><?= $s['facebook_name'] ?></td>
                <?php
$key = $s['year_level'] . '_' . $s['block'];
$courseCount = $courseCounts[$key] ?? 0;
$lessonCount = $lessonsFinished[$schoolId] ?? 0;

// FORCE 0 lessons if no courses
if($courseCount == 0){
    $lessonCount = 0;
}
?>

<td><?= $courseCount ?></td>
<td><?= $lessonCount ?></td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="confirmRemoval('<?= $schoolId ?>', '<?= addslashes($s['firstname'].' '.$s['surname']) ?>')">Remove</button>
                </td>
            </tr>
        <?php 
            }
        }
        if(!$hasStudent){
            echo '<tr><td colspan="9">No students assigned</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

</div>

<footer>Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();

    document.querySelectorAll('.card-custom').forEach(card => {
        let hasVisibleRow = false;
        card.querySelectorAll('tbody tr').forEach(tr => {
            const tds = tr.querySelectorAll('td');
            let showRow = false;
            for(let td of tds){
                if(td.textContent.toLowerCase().includes(query)){
                    showRow = true;
                    break;
                }
            }
            tr.style.display = showRow ? '' : 'none';
            if(showRow) hasVisibleRow = true;
        });
        card.style.display = hasVisibleRow ? '' : 'none';
    });
});

function confirmRemoval(studentId, studentName) {
    if(confirm(`Are you sure you want to remove ${studentName} as a student? This action cannot be undone.`)) {
        window.location.href = `remove_student.php?id=${studentId}`;
    }
}
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
</body>
</html>
