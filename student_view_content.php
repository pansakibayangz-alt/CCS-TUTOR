<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$schoolId = $_SESSION['school_id'];

// Get student info
$stmtStudent = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmtStudent->execute([$schoolId]);
$student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Student not found.";
    exit;
}

$studentInstructor = $student['instructor_id'];
$studentYear = $student['year_level'];
$studentBlock = $student['block'];

// Get lesson_id
if (!isset($_GET['lesson_id'])) {
    echo "Invalid lesson.";
    exit;
}
$lessonId = $_GET['lesson_id'];

// Fetch lesson
$stmtLesson = $pdo->prepare("
    SELECT l.*, c.course_name 
    FROM lessons l
    LEFT JOIN courses c ON l.course_id = c.course_id
    WHERE l.lesson_id = ? 
      AND l.instructor_id = ? 
      AND l.year_level = ? 
      AND l.block = ?
");
$stmtLesson->execute([$lessonId, $studentInstructor, $studentYear, $studentBlock]);
$lesson = $stmtLesson->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    echo "Lesson not found or not assigned to your year/block.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lesson <?= htmlspecialchars($lesson['lesson_no']); ?> - <?= htmlspecialchars($lesson['lesson_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #6a0dad, #c39bd3);
    min-height: 100vh;
    padding-bottom: 80px;
    color: #fff;
}
.navbar-custom {
    background: linear-gradient(135deg, #4B0082, #6a0dad);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}
.navbar-custom .nav-link, .navbar-brand {
    color: #fff;
    font-weight: 600;
}
.navbar-custom .nav-link:hover {
    color: #FFD700;
}
.lesson-content {
    background: rgba(0,0,0,0.25);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 0 12px rgba(255,255,255,0.3);
}
.download-btn {
    background: #FFD700;
    color: #000;
    font-weight: 600;
    border-radius: 10px;
}
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    color: #fff;
    text-align: center;
    padding: 10px;
    font-weight: 600;
    backdrop-filter: blur(8px);
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="student_dashboard.php">Student Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="logout.php">LOGOUT</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <a href="student_view_courses.php?course_id=<?= $lesson['course_id']; ?>" class="btn btn-warning mb-4 fw-bold">← Back to Lessons</a>

    <div class="lesson-content">
		<h2>Lesson <?= $lesson['lesson_no']; ?>: <?= htmlspecialchars($lesson['lesson_title']); ?></h2>
		<p><strong>Course:</strong> <?= htmlspecialchars($lesson['course_name']); ?></p>
		<p><strong>Year Level:</strong> <?= $lesson['year_level']; ?> | <strong>Block:</strong> <?= $lesson['block']; ?></p>
		<p><strong>Category:</strong> <?= $lesson['category']; ?></p>
		<hr>

		<?php if (!empty($lesson['lesson_file'])): ?>
			<p>Lesson file available:</p>
			<a href="uploads/<?= htmlspecialchars($lesson['lesson_file']); ?>" class="btn download-btn" target="_blank">Download/View File</a>
		<?php else: ?>
			<p>No lesson file uploaded.</p>
		<?php endif; ?>

		<!-- ADD BUTTONS HERE -->
		<div class="mt-3 d-flex gap-2">
			<a href="student_take_pretest.php?lesson_id=<?= $lesson['lesson_id']; ?>" class="btn download-btn">📝 Take Pretest</a>
			<a href="student_take_assessment.php?lesson_id=<?= $lesson['lesson_id']; ?>" class="btn download-btn">📄 Take Assessment</a>
		</div>
	</div>
</div>

<footer>
    Developed by <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

</body>
</html>
