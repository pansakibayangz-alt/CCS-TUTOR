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

function fixPath($path){
    // remove local server path
    $path = str_replace("C:\\xampp\\htdocs\\BSCS PROGRESS EDIT\\", "", $path);

    // convert backslashes to forward slashes
    $path = str_replace("\\", "/", $path);

    // encode spaces
    $path = str_replace(" ", "%20", $path);

    return $path;
}

// Fetch all instructors
$stmt = $pdo->prepare("SELECT * FROM instructor ORDER BY surname ASC");
$stmt->execute();
$instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students count grouped by instructor, year_level, block, course
$studentCountsStmt = $pdo->prepare("
    SELECT s.instructor_id, s.year_level, s.block, c.course_name, COUNT(s.student_id) AS student_count
    FROM students s
    LEFT JOIN courses c ON s.instructor_id = c.instructor_id
    GROUP BY s.instructor_id, s.year_level, s.block, c.course_name
");

// Fetch lessons count and files grouped by course
$lessonStmt = $pdo->prepare("
    SELECT course_id, course_name, COUNT(lesson_id) AS lesson_count, 
           MAX(syllabus_file) AS syllabus_file, 
           GROUP_CONCAT(lesson_file SEPARATOR '|') AS lesson_files
    FROM lessons
    GROUP BY course_id, course_name
");

$studentCountsStmt->execute();
$studentCounts = $studentCountsStmt->fetchAll(PDO::FETCH_ASSOC);

$lessonStmt->execute();
$lessons = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);

// Convert lessons into associative array by course_name for easier lookup
$lessonsMap = [];
foreach ($lessons as $l) {
    $lessonsMap[$l['course_name']] = $l;
}

// Convert student counts into associative array for lookup
$studentMap = [];
foreach ($studentCounts as $sc) {
    $studentMap[$sc['instructor_id']][$sc['year_level']][$sc['block']][$sc['course_name']] = $sc['student_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Instructors</title>
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
.modal-content { 
    color:#000;
}
footer { position: fixed; bottom:0; width:100%; background: rgba(0,0,0,0.55); backdrop-filter: blur(10px); color:white; text-align:center; font-weight:600; border-top:1px solid rgba(255,255,255,0.3); padding:10px;}
.card-custom h4,
.card-custom h6 {
    color: white !important;
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
            <li class="nav-item"><a class="nav-link active" href="#">Instructors</a></li>
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
            <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container mt-5 pb-5">

<div class="mb-4">
    <input type="text" id="searchInput" class="form-control" placeholder="Search Instructor..." style="max-width:400px;">
</div>

    <?php foreach($instructors as $ins): 
        $fullName = trim($ins['firstname'] . ' ' . ($ins['middlename'] ?? '') . ' ' . $ins['surname']);

// Only append degree_designation if it's not empty and not "N/A"
if (!empty($ins['degree_designation']) && strtoupper($ins['degree_designation']) !== "N/A") {
    $fullName .= ', ' . $ins['degree_designation'];
}
    ?>
    <div class="card card-custom">
        <h4 class="mb-3 d-flex align-items-center justify-content-between">
    <span>INSTRUCTOR: <strong><?= $fullName ?></strong></span>
    <button class="btn btn-sm btn-danger" onclick="confirmRemoval(<?= $ins['instructor_id'] ?>, '<?= addslashes($fullName) ?>')">Remove</button>
</h4>
        <h6>ASSIGNED STUDENT/S:</h6>

        <table class="table table-bordered table-hover text-white">
            <thead>
                <tr>
                    <th>Year Level</th>
                    <th>Block</th>
                    <th>No. of Students</th>
                    <th>Course Name</th>
                    <th>No. of Lessons</th>
                    <th>Syllabus File</th>
                    <th>Lesson Files</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(isset($studentMap[$ins['instructor_id']])) {
                    foreach($studentMap[$ins['instructor_id']] as $year => $blocks) {
                        foreach($blocks as $block => $courses) {
                            foreach($courses as $course => $studentCount) {
                                $lesson = $lessonsMap[$course] ?? null;
                                ?>
                                <tr>
                                    <td><?= $year ?></td>
                                    <td><?= $block ?></td>
                                    <td><?= $studentCount ?></td>
                                    <td>
<?php
$displayCourse = "No Course Yet"; // default

// Only display if:
// 1. There are students assigned for this instructor/year/block
// 2. There is at least 1 lesson for this course/year/block
if(!empty($course) && $studentCount > 0) {
    // check if there is a lesson for this course in this year/block
    $lessonForThisBlock = false;
    if(isset($lessonsMap[$course])) {
        $lesson = $lessonsMap[$course];
        // Since lessons have year_level & block, we need to check if any lesson matches
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM lessons 
                                    WHERE course_name=? AND year_level=? AND block=?");
        $stmtCheck->execute([$course, $year, $block]);
        if($stmtCheck->fetchColumn() > 0) {
            $lessonForThisBlock = true;
        }
    }

    if($lessonForThisBlock) {
        $displayCourse = $course;
    }
}

echo $displayCourse;
?>
</td>
                                    <td>
<?php 
if($displayCourse == "No Course Yet") {
    echo "";
} else {
    echo $lesson['lesson_count'] ?? 0;
}
?>
</td>
                                    <td>
                                        <?php 
if($displayCourse == "No Course Yet" || empty($lesson['syllabus_file'])) {
    echo "";
} else { ?>
<a class="link-gold" target="_blank"
href="view_file.php?file=<?= urlencode($lesson['syllabus_file']) ?>">
View
</a>
                                        <!-- Modal -->
                                        <div class="modal fade" id="syllabusModal<?= $lesson['course_id'] ?>" tabindex="-1">
                                          <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
    <div class="modal-body">
                                              <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="modal-title"><?= $course ?> - Syllabus</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                              </div>
                                              <iframe 
src="<?= fixPath($lesson['syllabus_file']) ?>" 
style="width:100%; height:85vh; border:none;">
</iframe>
                                            </div>
                                          </div>
                                        </div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php 
if($displayCourse == "No Course Yet" || empty($lesson['lesson_files'])) {
    echo "";
} else {
    $files = explode('|',$lesson['lesson_files']);
    foreach($files as $idx => $file): ?>
        <a class="link-gold" target="_blank"
        href="view_file.php?file=<?= urlencode($file) ?>">
        Lesson <?= $idx+1 ?>
        </a><br>
                                            <!-- Modal -->
                                            <div class="modal fade" id="lessonModal<?= $lesson['course_id'].'_'.$idx ?>" tabindex="-1">
                                              <div class="modal-dialog modal-xl">
                                                <div class="modal-content p-3">
                                                  <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h5 class="modal-title"><?= $course ?> - Lesson <?= $idx+1 ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                  </div>
                                                  <iframe 
src="<?= fixPath($file) ?>" 
style="width:100%; height:85vh; border:none;">
</iframe>
                                                </div>
                                              </div>
                                            </div>
                                        <?php endforeach; } ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    }
                } else {
                    echo '<tr><td colspan="7">No students assigned</td></tr>';
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

        // Get instructor name words
        const fullNameEl = card.querySelector('h4');
        let nameText = fullNameEl.textContent.replace(/^INSTRUCTOR:\s*/i, '').trim(); // remove "INSTRUCTOR:" prefix
        const nameWords = nameText.split(/\s+/); // split by all spaces

        card.querySelectorAll('tbody tr').forEach(tr => {
            const tds = tr.querySelectorAll('td');

            const yearLevel = tds[0]?.textContent.trim();
            const block = tds[1]?.textContent.trim();
            const courseName = tds[3]?.textContent.trim();

            let showRow = false;

            // 1️⃣ Year level exact match
            if(query === yearLevel) showRow = true;

            // 2️⃣ Prefix match for block, courseName (exclude "No Course Yet")
if(block.toLowerCase().startsWith(query)) showRow = true;
if(courseName.toLowerCase() !== 'no course yet' && courseName.toLowerCase().startsWith(query)) showRow = true;

// ✅ New check: if query is "no course", match rows with course_name "No Course Yet"
if(query === 'no course' && courseName.toLowerCase() === 'no course yet') showRow = true;

            // 3️⃣ Instructor name words match
            for (let word of nameWords) {
                if(word.toLowerCase().startsWith(query)) {
                    showRow = true;
                    break;
                }
            }

            // Apply row visibility
            tr.style.display = showRow ? '' : 'none';
            if(showRow) hasVisibleRow = true;
        });

        // Hide entire card if no rows are visible
        card.style.display = hasVisibleRow ? '' : 'none';
    });
});

function confirmRemoval(instructorId, instructorName) {
    if(confirm(`Are you sure you want to remove ${instructorName} as an instructor? This action cannot be undone.`)) {
        // Redirect to remove script (you will need to create remove_instructor.php)
        window.location.href = `remove_instructor.php?id=${instructorId}`;
    }
}

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
