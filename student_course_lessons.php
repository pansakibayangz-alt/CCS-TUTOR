<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$schoolId = $_SESSION['school_id'];

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmt->execute([$schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Student not found.";
    exit;
}

$instructorId = $student['instructor_id'];

// Fetch courses assigned to this instructor
$stmtCourses = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$stmtCourses->execute([$instructorId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Courses</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #6a0dad, #c39bd3); min-height: 100vh; padding-bottom: 80px; }
.navbar-custom { background: linear-gradient(135deg, #4B0082, #6a0dad); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
.navbar-custom .nav-link, .navbar-brand { color: #fff; font-weight: 600; }
.navbar-custom .nav-link:hover { color: #FFD700; }
.course-btn { width: 100%; padding: 20px; font-size: 1.3rem; font-weight: 700; border-radius: 15px; border: none; color: #fff; background: rgba(0,0,0,0.35); backdrop-filter: blur(8px); transition: 0.3s; }
.course-btn:hover { background: rgba(0,0,0,0.55); transform: scale(1.03); }
.card-course { border: 2px solid rgba(255,255,255,0.7); background: rgba(0,0,0,0.2); border-radius: 18px; box-shadow: 0 0 12px rgba(255,255,255,0.3); }
h2 { color: #fff; text-shadow: 2px 2px 4px #000; }
footer { position: fixed; bottom: 0; width: 100%; background: rgba(0,0,0,0.55); color: #fff; text-align: center; padding: 10px; font-weight: 600; backdrop-filter: blur(8px); }
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="student_dashboard.php">Student Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="student_about.php">ABOUT ME</a></li>
        <li class="nav-item"><a class="nav-link active" style="color:#FFD700;" href="student_view_courses.php">COURSES</a></li>
        <li class="nav-item"><a class="nav-link" href="student_progress.php">MY PROGRESS</a></li>
        <li class="nav-item"><a class="nav-link" href="student_feedback.php">FEEDBACK</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">LOGOUT</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">

    <!-- SEARCH BAR -->
    <div class="mb-4">
        <input type="text" id="searchCourse" class="form-control form-control-lg"
               placeholder="Search course..." 
               style="border-radius: 12px; font-weight:600;">
    </div>

    <?php if (empty($courses)): ?>
        <div class="alert alert-warning text-dark fw-bold">
            No courses assigned yet. Please contact your instructor.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($courses as $course): 
                // Get the first lesson for this student (year_level/block) in the course
                $stmtFirstLesson = $pdo->prepare("
                    SELECT lesson_id 
                    FROM lessons 
                    WHERE course_id = ? AND year_level = ? AND block = ?
                    ORDER BY lesson_no ASC 
                    LIMIT 1
                ");
                $stmtFirstLesson->execute([$course['course_id'], $student['year_level'], $student['block']]);
                $firstLesson = $stmtFirstLesson->fetch(PDO::FETCH_ASSOC);

                $lessonId = $firstLesson['lesson_id'] ?? 0;
            ?>
                <div class="col-md-4">
                    <div class="card card-course p-3">
                        <?php if($lessonId): ?>
                            <a href="student_view_lesson_content.php?lesson_id=<?= $lessonId; ?>">
                                <button class="course-btn">
                                    <?= htmlspecialchars($course['course_name']); ?><br>
                                    <span style="font-size: 0.9rem; font-weight:500;">
                                        (<?= $course['category']; ?>)
                                    </span>
                                </button>
                            </a>
                        <?php else: ?>
                            <button class="course-btn" disabled>
                                <?= htmlspecialchars($course['course_name']); ?><br>
                                <span style="font-size: 0.9rem; font-weight:500;">
                                    (No lessons for your year/block)
                                </span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    Developed by <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

<script>
// Search Filter
document.getElementById('searchCourse').addEventListener('keyup', function () {
    const query = this.value.toLowerCase();
    const cards = document.querySelectorAll('.card-course');

    cards.forEach(card => {
        const text = card.innerText.toLowerCase();
        card.parentElement.style.display = text.includes(query) ? '' : 'none';
    });
});
</script>

</body>
</html>
