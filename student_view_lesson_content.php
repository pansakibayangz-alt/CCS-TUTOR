<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$schoolId = $_SESSION['school_id'];

$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmt->execute([$schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");
$studentId = $student['student_id'];

$lessonId = intval($_GET['lesson_id'] ?? 0);
if ($lessonId <= 0) die("Invalid lesson ID");

$stmt = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) die("Lesson not found.");

$courseId = $lesson['course_id'];

$stmtLessons = $pdo->prepare("
    SELECT * FROM lessons
    WHERE course_id = ? AND year_level = ? AND block = ?
    ORDER BY lesson_no ASC
");
$stmtLessons->execute([$courseId, $student['year_level'], $student['block']]);
$lessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

$stmtIncomplete = $pdo->prepare("
    SELECT a.lesson_id, a.assessment_id,
           l.lesson_no,
           (SELECT COUNT(*) FROM assessment_items WHERE assessment_id=a.assessment_id) AS total_items,
           ssa.score
    FROM assessments a
    INNER JOIN lessons l ON a.lesson_id = l.lesson_id
    LEFT JOIN student_assessment_attempts ssa 
        ON ssa.assessment_id=a.assessment_id AND ssa.student_id=?
    WHERE a.course_id = ? AND a.year_level=? AND a.block=?
    ORDER BY l.lesson_no ASC
    LIMIT 1
");
$stmtIncomplete->execute([$studentId, $courseId, $student['year_level'], $student['block']]);
$incompleteAssessment = $stmtIncomplete->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($lesson['course_name'] ?? 'Course'); ?> — Lessons</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
:root{
    --navy:#071A2A;
    --navy2:#0B2540;
    --gold:#FFD700;
    --text:rgba(255,255,255,0.92);
    --glass:rgba(255,255,255,0.04);
    --border:rgba(255,215,0,0.12);
}

body{
    margin:0;
    font-family:'Poppins',sans-serif;
    background:linear-gradient(180deg,var(--navy),var(--navy2));
    color:var(--text);
    min-height:100vh;
    padding-bottom:90px;
}

/* NAVBAR */
.navbar-custom{
    background:linear-gradient(90deg,rgba(7,27,42,.95),rgba(8,48,79,.95));
    border-bottom:1px solid rgba(255,215,0,.08);
    box-shadow:0 8px 24px rgba(2,12,27,.45);
}
.navbar-custom h3,
.navbar-brand{
    font-family:'Merriweather',serif;
    font-weight:700;
    color:var(--gold);
}
.btn-back{
    background:var(--gold);
    color:#071A2A;
    font-weight:700;
}

/* SYLLABUS VIEW */
.syllabus-box{
    background:var(--glass);
    border:1px solid var(--border);
    border-radius:14px;
    box-shadow:0 14px 40px rgba(2,8,23,.5);
    color:#fff;
}

/* LESSON CARDS */
.lesson-card{
    background:var(--glass);
    border:1px solid var(--border);
    border-radius:14px;
    padding:20px;
    box-shadow:0 14px 40px rgba(2,8,23,.4);
    transition:.25s;
}
.lesson-card:hover{
    transform:translateY(-6px);
    box-shadow:0 20px 48px rgba(2,12,27,.6);
}
.lesson-card h4{
    font-weight:700;
    color:var(--gold);
}
.download-btn{
    background:var(--gold);
    color:#071A2A;
    font-weight:700;
    border-radius:10px;
}
.download-btn:hover{
    background:#ffea85;
}

/* FOOTER */
footer{
    position:fixed;
    bottom:0;
    width:100%;
    background:linear-gradient(90deg,rgba(7,27,42,.9),rgba(8,48,79,.9));
    border-top:1px solid rgba(255,215,0,.08);
    color:rgba(255,255,255,.85);
    font-weight:600;
    text-align:center;
    padding:10px 0;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
    <h3 class="mb-1"><?= htmlspecialchars($lesson['course_name'] ?? 'Course'); ?> — Lessons</h3>
    <a href="student_view_courses.php" class="btn btn-back">← Back to Courses</a>
  </div>
</nav>

<?php
$stmtRead = $pdo->prepare("SELECT * FROM student_syllabus_read WHERE student_id=? AND lesson_id=?");
$stmtRead->execute([$studentId, $lessonId]);
$hasReadSyllabus = $stmtRead->fetch();
?>

<?php if(!empty($lesson['syllabus_file'])): ?>
<div class="container mt-4">
<div class="syllabus-box p-4">
    <embed src="<?= htmlspecialchars($lesson['syllabus_file']); ?>"
           type="application/pdf"
           style="width:100%; min-height:520px;" />
    <div class="mt-3">
        <?php if(!$hasReadSyllabus): ?>
        <form method="POST" action="mark_syllabus_read.php">
            <input type="hidden" name="lesson_id" value="<?= $lessonId; ?>">
            <button class="btn download-btn">I Have Read the Syllabus</button>
        </form>
        <?php else: ?>
            <span class="badge bg-success">Syllabus Read ✔ You can now take the Pretest</span>
        <?php endif; ?>
    </div>
</div>
</div>
<?php endif; ?>

<div class="container mt-5">
<?php if (empty($lessons)): ?>
<div class="alert alert-warning fw-bold">No lessons available.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach ($lessons as $l):

$stmtPretest = $pdo->prepare("SELECT * FROM pretests WHERE lesson_id=? AND year_level=? AND block=?");
$stmtPretest->execute([$l['lesson_id'], $student['year_level'], $student['block']]);
$pretest = $stmtPretest->fetch();

$stmtAssessment = $pdo->prepare("SELECT * FROM assessments WHERE lesson_id=? AND year_level=? AND block=?");
$stmtAssessment->execute([$l['lesson_id'], $student['year_level'], $student['block']]);
$assessment = $stmtAssessment->fetch();

$disableAssessment = false;
if ($assessment && $incompleteAssessment && $incompleteAssessment['lesson_id'] != $l['lesson_id']) {
    $disableAssessment = true;
}

$stmtPretestDone = $pdo->prepare("
SELECT * FROM student_pretest_attempts 
WHERE student_id=? AND pretest_id=(
SELECT pretest_id FROM pretests WHERE lesson_id=? AND year_level=? AND block=? LIMIT 1)
");
$stmtPretestDone->execute([$schoolId, $l['lesson_id'], $student['year_level'], $student['block']]);
$pretestDone = $stmtPretestDone->fetch();
$viewLessonDisabled = !$pretestDone ? 'disabled' : '';

$stmtLessonRead = $pdo->prepare("SELECT * FROM student_lesson_completion WHERE student_id=? AND lesson_id=?");
$stmtLessonRead->execute([$schoolId, $l['lesson_id']]);
$lessonRead = $stmtLessonRead->fetch();
$assessmentDisabled = !$lessonRead ? 'disabled' : '';
?>
<div class="col-md-6">
<div class="lesson-card">
<h4>Lesson <?= $l['lesson_no']; ?><hr><?= htmlspecialchars($l['lesson_title']); ?></h4>

<div class="d-flex flex-wrap gap-2 mt-3">
<?php if ($pretest): ?>
<a class="btn download-btn" href="student_take_pretest.php?pretest_id=<?= $pretest['pretest_id']; ?>"
<?= !$hasReadSyllabus ? 'disabled' : '' ?>>Take Pretest</a>
<?php endif; ?>

<?php if (!empty($l['lesson_file'])): ?>
<a class="btn download-btn" href="student_view_lesson_file.php?lesson_id=<?= $l['lesson_id']; ?>"
<?= $viewLessonDisabled ?>>View Lesson</a>
<?php endif; ?>

<?php if ($assessment): ?>
<a class="btn download-btn" href="student_take_assessment.php?lesson_id=<?= $l['lesson_id']; ?>"
<?= $disableAssessment || $assessmentDisabled ? 'disabled' : '' ?>>Take Assessment</a>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
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


</body>
</html>
