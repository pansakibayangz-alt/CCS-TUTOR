<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'config.php';

// Fetch instructor info
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch courses for this instructor
$stmtCourses = $pdo->prepare("SELECT course_id, course_name FROM courses WHERE instructor_id = ? ORDER BY course_name");
$stmtCourses->execute([$instructor['instructor_id']]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Filters
$selectedCourse = $_GET['course_id'] ?? '';
$selectedLesson = $_GET['lesson_id'] ?? '';
$searchStudent = $_GET['search_student'] ?? '';
$searchYear = $_GET['search_year'] ?? '';
$searchBlock = $_GET['search_block'] ?? '';

// Fetch lessons if course selected
$lessons = [];
if ($selectedCourse) {
    $stmtLessons = $pdo->prepare("SELECT lesson_id, lesson_title FROM lessons WHERE course_id = ? ORDER BY lesson_no");
    $stmtLessons->execute([$selectedCourse]);
    $lessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);
}

// MAIN QUERY: fetch last attempt per student per pretest & assessment
$sql = "
SELECT 
    l.lesson_id, l.course_id, c.course_name, l.lesson_title,
    s.school_id, CONCAT(s.surname, ', ', s.firstname, ' ', LEFT(s.middlename,1),'.') AS student_name,
    
    spa.score AS pretest_score,
    spa.total_items AS pretest_total,
    spa.completed_at AS pretest_completed,

    saa.score AS assessment_score,
    saa.total_items AS assessment_total,
    saa.taken_at AS assessment_taken

FROM lessons l
JOIN courses c ON l.course_id = c.course_id
JOIN students s ON s.year_level = l.year_level AND s.block = l.block

-- Last pretest attempt per student
LEFT JOIN (
    SELECT spa1.student_id, spa1.pretest_id, spa1.score, COUNT(pi.item_id) AS total_items, spa1.completed_at
    FROM student_pretest_attempts spa1
    JOIN pretests p ON spa1.pretest_id = p.pretest_id
    LEFT JOIN pretest_items pi ON pi.pretest_id = spa1.pretest_id
    WHERE spa1.attempt_no = (
        SELECT MAX(spa2.attempt_no)
        FROM student_pretest_attempts spa2
        WHERE spa2.student_id = spa1.student_id AND spa2.pretest_id = spa1.pretest_id
    )
    GROUP BY spa1.student_id, spa1.pretest_id
) spa ON spa.student_id = s.school_id

-- Last assessment attempt per student
LEFT JOIN (
    SELECT saa1.student_id, saa1.assessment_id, saa1.score, COUNT(ai.item_id) AS total_items, saa1.taken_at
    FROM student_assessment_attempts saa1
    JOIN assessments a ON saa1.assessment_id = a.assessment_id
    LEFT JOIN assessment_items ai ON ai.assessment_id = saa1.assessment_id
    WHERE saa1.attempt_no = (
        SELECT MAX(saa2.attempt_no)
        FROM student_assessment_attempts saa2
        WHERE saa2.student_id = saa1.student_id AND saa2.assessment_id = saa1.assessment_id
    )
    GROUP BY saa1.student_id, saa1.assessment_id
) saa ON saa.student_id = s.school_id

WHERE l.instructor_id = ?
";

$params = [$instructor['instructor_id']];

if ($selectedCourse) {
    $sql .= " AND l.course_id = ?";
    $params[] = $selectedCourse;
}
if ($selectedLesson) {
    $sql .= " AND l.lesson_id = ?";
    $params[] = $selectedLesson;
}
if ($searchStudent) {
    $sql .= " AND CONCAT(s.surname, ', ', s.firstname, ' ', LEFT(s.middlename,1),'.') LIKE ?";
    $params[] = "%$searchStudent%";
}
if ($searchYear) {
    $sql .= " AND l.year_level = ?";
    $params[] = $searchYear;
}
if ($searchBlock) {
    $sql .= " AND l.block = ?";
    $params[] = $searchBlock;
}

$sql .= " ORDER BY c.course_name, l.lesson_no, s.surname";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by course and lesson
$grouped = [];
foreach ($results as $row) {
    $key = $row['course_name'].'||'.$row['lesson_title'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'course' => $row['course_name'],
            'lesson' => $row['lesson_title'],
            'lesson_id' => $row['lesson_id'],
            'course_id' => $row['course_id'],
            'students' => []
        ];
    }
    $grouped[$key]['students'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Progress - Assessments & Pretests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#0047AB,#1E90FF); color:#fff; min-height:100vh; }
.navbar-custom { background: linear-gradient(135deg,#002F6C,#0047AB); }
.navbar-custom .navbar-brand, .navbar-custom .nav-link { color:#fff; font-weight:600; }
.navbar-custom .nav-link.active { color:#FFD700; font-weight:700; }
.table-container { margin:50px auto; max-width:1200px; }
.card { border-radius:12px; background:rgba(0,0,0,0.3); backdrop-filter: blur(4px); margin-bottom:30px; }
table { color:#fff; }
th, td { vertical-align: middle; }
.course-title, .lesson-title { color:#fff; font-weight:bold; }
footer { text-align:center; padding:10px; position:fixed; bottom:0; width:100%; background:rgba(0,0,0,0.5); }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand">ASSESSMENTS / PRETESTS</a>
  </div>
</nav>

<div class="container table-container">

    <!-- Back Button -->
    <div class="mb-3">
        <a href="instructor_view_progress.php" class="btn btn-warning">&larr; Back</a>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="course_id" class="form-label" style="color:#fff;">Courses</label>
                <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['course_id'] ?>" <?= ($selectedCourse == $c['course_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="lesson_id" class="form-label" style="color:#fff;">Lessons</label>
                <select name="lesson_id" id="lesson_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Lessons</option>
                    <?php foreach($lessons as $l): ?>
                        <option value="<?= $l['lesson_id'] ?>" <?= ($selectedLesson == $l['lesson_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['lesson_title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search_student" class="form-label" style="color:#fff;">Student Name</label>
                <input type="text" name="search_student" id="search_student" class="form-control" value="<?= htmlspecialchars($searchStudent) ?>" placeholder="Enter student name">
            </div>
            <div class="col-md-1">
                <label for="search_year" class="form-label" style="color:#fff;">Year</label>
                <select name="search_year" id="search_year" class="form-select">
                    <option value="">All</option>
                    <?php for($y=1;$y<=4;$y++): ?>
                        <option value="<?= $y ?>" <?= ($searchYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label for="search_block" class="form-label" style="color:#fff;">Block</label>
                <select name="search_block" id="search_block" class="form-select">
                    <option value="">All</option>
                    <?php foreach(['A','B','C','D','E','F'] as $b): ?>
                        <option value="<?= $b ?>" <?= ($searchBlock == $b) ? 'selected' : '' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary mt-3">Search</button>
            </div>
        </form>
    </div>

    <?php foreach ($grouped as $lessonData): ?>
<div class="card p-4 mb-4">
    <h5 class="course-title">Course Name: <?= strtoupper(htmlspecialchars($lessonData['course'])) ?></h5>
    <h6 class="lesson-title">Lesson: <?= strtoupper(htmlspecialchars($lessonData['lesson'])) ?></h6>

    <div class="table-responsive mt-3">
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Pretest Score</th>
                    <th>Pretest Remarks</th>
                    <th>Completed At</th>
                    <th>Assessment Score</th>
                    <th>Assessment Remarks</th>
                    <th>Finished At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php $i=1; foreach ($lessonData['students'] as $student): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= strtoupper(htmlspecialchars($student['student_name'])) ?></td>
    <td><?= $student['pretest_total'] ? $student['pretest_score'].'/'.$student['pretest_total'] : '-' ?></td>
    <td><?= ($student['pretest_total'] && $student['pretest_score'] !== null) ? ($student['pretest_score']/$student['pretest_total']*100 >= 50 ? 'PASSED' : 'FAILED') : '-' ?></td>
    <td><?= $student['pretest_completed'] ?? '-' ?></td>
    <td><?= $student['assessment_total'] ? $student['assessment_score'].'/'.$student['assessment_total'] : '-' ?></td>
    <td><?= ($student['assessment_total'] && $student['assessment_score'] !== null) ? ($student['assessment_score']/$student['assessment_total']*100 >= 50 ? 'PASSED' : 'FAILED') : '-' ?></td>
    <td><?= $student['assessment_taken'] ?? '-' ?></td>
    <td>
        <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#attemptCollapse<?= $student['school_id'] ?>" aria-expanded="false" aria-controls="attemptCollapse<?= $student['school_id'] ?>">
            VIEW ATTEMPTS
        </button>
    </td>
</tr>

<!-- COLLAPSIBLE ROW -->
<tr class="collapse" id="attemptCollapse<?= $student['school_id'] ?>">
    <td colspan="9">
        <?php
        // Fetch all attempts
        $stmtPre = $pdo->prepare("SELECT spa.attempt_no, spa.score, COUNT(pi.item_id) total_items
            FROM student_pretest_attempts spa
            JOIN pretests p ON spa.pretest_id = p.pretest_id
            LEFT JOIN pretest_items pi ON pi.pretest_id = spa.pretest_id
            WHERE spa.student_id=? AND p.course_id=? AND p.lesson_id=?
            GROUP BY spa.attempt_id ORDER BY spa.attempt_no");
        $stmtPre->execute([$student['school_id'], $student['course_id'], $lessonData['lesson_id']]);
        $preAttempts = $stmtPre->fetchAll(PDO::FETCH_ASSOC);

        $stmtAss = $pdo->prepare("SELECT saa.attempt_no, saa.score, COUNT(ai.item_id) total_items
            FROM student_assessment_attempts saa
            JOIN assessments a ON saa.assessment_id = a.assessment_id
            LEFT JOIN assessment_items ai ON ai.assessment_id = a.assessment_id
            WHERE saa.student_id=? AND a.course_id=? AND a.lesson_id=?
            GROUP BY saa.attempt_id ORDER BY saa.attempt_no");
        $stmtAss->execute([$student['school_id'], $student['course_id'], $lessonData['lesson_id']]);
        $assAttempts = $stmtAss->fetchAll(PDO::FETCH_ASSOC);

        $maxAttempts = max(count($preAttempts), count($assAttempts));
        ?>

        <?php if($maxAttempts > 0): ?>
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>No. of Attempt</th>
                    <th>Pretest Score</th>
                    <th>Assessment Score</th>
                </tr>
            </thead>
            <tbody>
            <?php for($j=0; $j<$maxAttempts; $j++): ?>
                <tr>
                    <td><?= $j+1 ?></td>
                    <td><?= isset($preAttempts[$j]) ? $preAttempts[$j]['score'].'/'.$preAttempts[$j]['total_items'] : '-' ?></td>
                    <td><?= isset($assAttempts[$j]) ? $assAttempts[$j]['score'].'/'.$assAttempts[$j]['total_items'] : '-' ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-warning mb-0">No attempts yet.</p>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

</div>

<footer>
    Developed by <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>