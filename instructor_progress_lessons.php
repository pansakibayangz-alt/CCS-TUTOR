<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

// Helper to format student name with middle initial
function format_student_name($s, $f, $m) {
    $mInitial = '';
    if ($m && strlen(trim($m)) > 0) {
        $mInitial = ' ' . strtoupper(substr(trim($m), 0, 1)) . '.';
    }
    return htmlspecialchars($s . ', ' . $f . $mInitial);
}

// Get instructor info
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die("Instructor not found.");
$instructor_id = $instructor['instructor_id'];

// Fetch all courses that this instructor has lessons for (use lessons.course_name and course_id)
$sqlCourses = "
    SELECT DISTINCT COALESCE(l.course_id, 0) AS course_id, COALESCE(l.course_name, '(Unassigned)') AS course_name
    FROM lessons l
    WHERE l.instructor_id = ?
    ORDER BY l.course_name
";
$stmtCourses = $pdo->prepare($sqlCourses);
$stmtCourses->execute([$instructor_id]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students under this instructor
$sqlStudents = "
    SELECT student_id, school_id, surname, firstname, middlename, year_level, block
    FROM students
    WHERE instructor_id = ?
    ORDER BY year_level, block, surname, firstname
";
$stmtStudents = $pdo->prepare($sqlStudents);
$stmtStudents->execute([$instructor_id]);
$students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Build an index of students by year-block for quick access
$studentsByGroup = [];
foreach ($students as $st) {
    $g = $st['year_level'] . '-' . $st['block'];
    if (!isset($studentsByGroup[$g])) $studentsByGroup[$g] = [];
    $studentsByGroup[$g][$st['school_id']] = $st;
}

// Fetch all lessons for this instructor
$sqlLessons = "
    SELECT l.*
    FROM lessons l
    WHERE l.instructor_id = ?
    ORDER BY l.course_name, l.year_level, l.block, l.lesson_no
";
$stmtLessons = $pdo->prepare($sqlLessons);
$stmtLessons->execute([$instructor_id]);
$lessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

// Organize lessons by course -> year-block
$lessonsByCourseGroup = []; // [course_name][year-block] => [lessons]
$allLessonIds = [];
foreach ($lessons as $ls) {
    $courseName = $ls['course_name'] ?? '(Unassigned)';
    $group = $ls['year_level'] . '-' . $ls['block'];
    if (!isset($lessonsByCourseGroup[$courseName])) $lessonsByCourseGroup[$courseName] = [];
    if (!isset($lessonsByCourseGroup[$courseName][$group])) $lessonsByCourseGroup[$courseName][$group] = [];
    $lessonsByCourseGroup[$courseName][$group][] = $ls;
    $allLessonIds[] = $ls['lesson_id'];
}

// Fetch all completions for students for these lessons (single query)
$completionsMap = []; // [student_id][lesson_id] = completed_at
if (!empty($allLessonIds)) {
    // prepare placeholders
    $placeholders = implode(',', array_fill(0, count($allLessonIds), '?'));
    $sqlComp = "
        SELECT student_id, lesson_id, completed_at
        FROM student_lesson_completion
        WHERE lesson_id IN ($placeholders)
    ";
    $stmtComp = $pdo->prepare($sqlComp);
    $stmtComp->execute($allLessonIds);
    $comps = $stmtComp->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comps as $c) {
        $sid = $c['student_id'];
        $lid = $c['lesson_id'];
        $completionsMap[$sid][$lid] = $c['completed_at'];
    }
}

// Utility to create a searchable text blob for an item
function make_search_blob($courseName, $group, $student, $lessonTitles = [], $completions = []) {
    $parts = [];
    $parts[] = strtolower($courseName);
    $parts[] = strtolower($group); // e.g. "2-a"
    $parts[] = strtolower($student['surname'] . ' ' . $student['firstname'] . ' ' . ($student['middlename'] ?? '') . ' ' . $student['school_id']);
    foreach ($lessonTitles as $lt) $parts[] = strtolower($lt);
    foreach ($completions as $comp) $parts[] = strtolower($comp);
    return implode(' ', $parts);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Learning Progress — Lessons Completed</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg,#0047AB,#1E90FF);
    color: #fff;
    min-height: 100vh;
    margin: 0;
}
.navbar-custom { 
    background: linear-gradient(135deg,#002F6C,#0047AB); 
    box-shadow:0 6px 20px rgba(0,0,0,0.25); 
}
.navbar-custom .navbar-brand { color:#fff; font-weight:700; }
.container-box { padding: 30px; }

.top-controls { display:flex; gap:12px; align-items:center; margin-bottom:18px; }
.btn-back { background: #3D2C8D; color:#fff; font-weight:600; border:none; padding:8px 12px; border-radius:8px; text-decoration:none; }
.btn-back:hover { background:#5C4D99; color:#fff; }
.search-input { flex:1; max-width:520px; }

.course-card {
    background: linear-gradient(135deg,#2E2B6F,#4A3F8F);
    border-radius:12px;
    padding:16px 20px;
    margin-bottom:20px;
    border: 1px solid rgba(255,255,255,0.12);
    transition: transform 0.2s;
}
.course-card:hover { transform: scale(1.01); }
.course-title { font-size:1.2rem; font-weight:700; margin-bottom:5px; }
.group-title { font-size:1rem; font-weight:700; margin-top:12px; margin-bottom:6px; color:#FFD; }

/* Make table rows more visible */
.table-student tbody tr {
    background: rgba(255,255,255,0.12); /* brighter than before */
    color: #fff;
    transition: background 0.2s;
}
.table-student tbody tr:hover {
    background: rgba(255,255,255,0.25);
}

/* Buttons */
.view-btn { 
    background: #FFD700;  /* bright gold */
    border: none;
    color: #000;
    padding: 6px 12px; 
    border-radius: 8px; 
    font-weight:600;
    transition: 0.2s;
}
.view-btn:hover { 
    background: #FFC300; /* darker gold on hover */
    color: #000;
}

/* Completed Lessons Card */
.lesson-item.completed {
    background: rgba(0, 255, 136, 0.25);
    border-left: 4px solid #00FF88;
    color: #000; /* dark text for readability */
}

/* Not Completed Lessons Card */
.lesson-item.not-completed {
    background: rgba(255, 215, 0, 0.25);
    border-left: 4px solid #FFD700;
    color: #000; /* keep dark text */
}

/* Titles in lesson cards */
.lesson-title {
    font-weight: 700;
    color: #000; /* make text black to stand out */
}

/* Status inside lesson cards */
.lesson-status.completed { 
    color: #006400; /* dark green */
    font-weight:700;
}
.lesson-status.not-completed { 
    color: #8B0000; /* dark red */
    font-weight:700;
}

/* Collapse lesson container */
.collapse-lesson-list {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 12px;
    margin: 6px 0 12px 0;
}

/* Column titles */
.group-title, .text-white-50 {
    color: #FFD700 !important; /* gold for better contrast */
}

/* Completed/Not Completed headings */
h6.text-white-50 {
    font-weight:700;
}

/* Adjust badge for better visibility */
.badge-count {
    background: #FFD700;
    color:#000;
    font-weight:700;
    padding:6px 12px;
    border-radius:12px;
}

/* Responsive adjustment for mobile */
@media(max-width:768px){
    .lesson-column { margin-bottom:12px; }
    .view-btn { width:100%; text-align:center; }
}

.no-data { color: rgba(255,255,255,0.75); padding:18px; text-align:center; font-style:italic; }

footer { 
    position: fixed; 
    bottom: 0; 
    left:0; 
    width:100%; 
    background: rgba(0,0,0,0.55); 
    padding:12px; 
    text-align:center; 
    font-weight:600; 
    color:#fff; 
}

@media (max-width:768px) {
    .search-input { max-width:100%; width:100%; }
}

</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand ps-3">📚 Learning Progress / Lessons</a>
  </div>
</nav>

<div class="container container-box">

    <div class="top-controls">
        <a href="instructor_view_progress.php" class="btn-back">← Back</a>

        <input id="globalSearch" class="form-control search-input" placeholder="Search course, year, block, lesson, student, date or time...">
    </div>

    <?php if (empty($lessonsByCourseGroup)): ?>
        <div class="no-data">No lessons found for this instructor.</div>
    <?php else: ?>
        <?php foreach ($lessonsByCourseGroup as $courseName => $groups): ?>
            <div class="course-card course-block" data-course="<?= htmlspecialchars(strtolower($courseName)) ?>">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="course-title"><?= htmlspecialchars($courseName) ?></div>
                    </div>
                    <div class="badge-count"><?= array_sum(array_map('count', $groups)) ?> lessons</div>
                </div>

                <!-- For each year-block inside this course -->
                <?php foreach ($groups as $groupKey => $groupLessons): 
                    list($year, $block) = explode('-', $groupKey);
                    // students in this group
                    $studentsInGroup = $studentsByGroup[$groupKey] ?? [];
                ?>
                    <div class="group-title"><?= "Year Level: " . htmlspecialchars($year) . " — Block: " . htmlspecialchars($block) ?></div>

                    <?php if (empty($studentsInGroup)): ?>
                        <div class="no-data">No students assigned for Year <?= htmlspecialchars($year) ?> Block <?= htmlspecialchars($block) ?>.</div>
                    <?php else: ?>
                        <div class="table-responsive mb-3">
                        <table class="table table-student table-bordered">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>Student</th>
                                    <th style="width:170px">School ID</th>
                                    <th style="width:160px">Progress</th>
                                    <th style="width:130px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $r = 1; ?>
                                <?php foreach ($studentsInGroup as $school_id => $st): 
                                    // compute completed & not completed lists for this student in this course & group
                                    $completed = [];
                                    $notCompleted = [];
                                    $lessonTitles = [];
                                    foreach ($groupLessons as $ls) {
                                        $lid = $ls['lesson_id'];
                                        $lessonTitles[] = $ls['lesson_title'];
                                        if (isset($completionsMap[$school_id]) && isset($completionsMap[$school_id][$lid])) {
                                            $completed[$lid] = $completionsMap[$school_id][$lid];
                                        } else {
                                            $notCompleted[$lid] = true;
                                        }
                                    }

                                    $total = count($groupLessons);
                                    $doneCount = count($completed);

                                    // build a searchable blob for JS search
                                    $completionTimes = array_values($completed);
                                    $searchBlob = make_search_blob($courseName, $groupKey, $st, $lessonTitles, $completionTimes);
                                ?>
                                    <tr class="student-row" data-search="<?= htmlspecialchars($searchBlob) ?>">
                                        <td><?= $r++ ?></td>
                                        <td><?= format_student_name($st['surname'], $st['firstname'], $st['middlename']) ?></td>
                                        <td><?= htmlspecialchars($st['school_id']) ?></td>
                                        <td>
                                            <span class="badge-count"><?= $doneCount ?>/<?= $total ?></span>
                                        </td>
                                        <td>
                                            <button class="view-btn" type="button" data-bs-toggle="collapse" data-bs-target="#studentCollapse_<?= htmlspecialchars($st['school_id'] . '_' . md5($courseName . $groupKey)) ?>" aria-expanded="false">
                                                View Lessons
                                            </button>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="5" class="p-0">
                                            <div id="studentCollapse_<?= htmlspecialchars($st['school_id'] . '_' . md5($courseName . $groupKey)) ?>" class="collapse collapse-lesson-list">
                                                <div class="p-3">
                                                    <div class="row">
    <div class="col-md-6 lesson-column">
        <h6 class="text-white-50">✅ Completed Lessons (<?= $doneCount ?>)</h6>
        <?php if ($doneCount === 0): ?>
            <div class="no-data">No completed lessons.</div>
        <?php else: ?>
            <div class="completed-lessons">
                <?php foreach ($groupLessons as $ls): 
                    $lid = $ls['lesson_id'];
                    if (!isset($completionsMap[$st['school_id']][$lid])) continue;
                    $completedAt = $completionsMap[$st['school_id']][$lid];
                ?>
                    <div class="lesson-item completed">
                        <div class="lesson-title"><?= htmlspecialchars($ls['lesson_no'] . '. ' . $ls['lesson_title']) ?></div>
                        <div class="lesson-status completed">✓ <?= htmlspecialchars($completedAt) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-6 lesson-column">
        <h6 class="text-white-50">⚠️ Not Completed (<?= count($notCompleted) ?>)</h6>
        <?php if (count($notCompleted) === 0): ?>
            <div class="no-data">All lessons completed.</div>
        <?php else: ?>
            <div class="not-completed-lessons">
                <?php foreach ($groupLessons as $ls): 
                    $lid = $ls['lesson_id'];
                    if (isset($completionsMap[$st['school_id']][$lid])) continue;
                ?>
                    <div class="lesson-item not-completed">
                        <div class="lesson-title"><?= htmlspecialchars($ls['lesson_no'] . '. ' . $ls['lesson_title']) ?></div>
                        <div class="lesson-status not-completed">—</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
                                                </div> <!-- p-3 -->
                                            </div> <!-- collapse -->
                                        </td>
                                    </tr>

                                <?php endforeach; // students ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; // students exist ?>
                <?php endforeach; // groups ?>
            </div>
        <?php endforeach; // courses ?>
    <?php endif; ?>

</div>

<footer>
    Developed by <strong>Limetares Group</strong> — S.Y. 2025–2026
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global search: matches course card, groups, and student rows via data-search blobs.
// The search will hide course-cards entirely if none of its student rows or its text match.
document.getElementById('globalSearch').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();

    // For each course block
    document.querySelectorAll('.course-block').forEach(courseEl => {
        const courseText = (courseEl.getAttribute('data-course') || '').toLowerCase();
        let courseMatches = !q || courseText.includes(q);

        // Check student rows inside this course block
        let anyVisibleStudent = false;
        courseEl.querySelectorAll('.student-row').forEach(row => {
            const blob = (row.getAttribute('data-search') || '').toLowerCase();
            const matches = !q || blob.includes(q);
            row.style.display = matches ? '' : 'none';

            // also hide the following collapse row if the student row hidden
            const nextTr = row.nextElementSibling;
            if (nextTr) nextTr.style.display = matches ? '' : 'none';

            if (matches) anyVisibleStudent = true;
        });

        // If course text matches OR any visible student found -> show course block, else hide
        courseEl.style.display = (courseMatches || anyVisibleStudent) ? '' : 'none';
    });
});
</script>
</body>
</html>
