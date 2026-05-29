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

:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy), var(--navy2));
    min-height: 100vh;
    margin: 0;
    color: var(--white);
}

/* ============================
   UNIVERSAL STUDENT NAVBAR
   ============================ */
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
    color: rgba(255,255,255,0.9) !important;
    font-weight:600;
    text-transform:uppercase;
    font-size:0.83rem;
    padding-left: 12px;
    padding-right: 12px;
}

.navbar-custom .nav-link:hover{
    color: var(--gold) !important;
    text-decoration: underline;
}

/* ACTIVE */
.active-link{
    color: var(--gold) !important;
    text-decoration: underline;
    font-weight:700;
}

/* ============================
   PAGE UI (UNCHANGED)
   ============================ */

.card-course {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.55);
    border-radius: 14px;
    backdrop-filter: blur(8px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.45);
    transition: 0.3s;
}

.card-course:hover {
    transform: translateY(-4px);
}

.course-btn {
    width: 100%;
    padding: 20px;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--white);
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,215,0,0.25);
    backdrop-filter: blur(6px);
    border-radius: 14px;
    transition: 0.3s;
}

.course-btn:hover {
    background: rgba(255,215,0,0.15);
    transform: scale(1.03);
}

/* SEARCH */
#searchCourse {
    background: rgba(255,255,255,0.85);
    border: none;
    border-radius: 10px;
    padding: 10px 38px 10px 14px;
    font-size: 0.95rem;
    background-image: url('https://cdn-icons-png.flaticon.com/512/622/622669.png');
    background-size: 18px;
    background-repeat: no-repeat;
    background-position: right 12px center;
}

/* FOOTER */
.footer-fixed {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    padding: 10px;
    text-align: center;
    border-top: 1px solid rgba(255,215,0,0.12);
    font-weight: 600;
}
/* Force highlight for active link */
.navbar-custom .nav-link.active-link {
    color: var(--gold) !important;
    text-decoration: underline !important;
    text-underline-offset: 5px;
    font-weight: 700 !important;
}

</style>
</head>

<body>

<!-- NAVBAR (ONLY PART UPDATED) -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">

    <a class="navbar-brand d-flex align-items-center gap-2" href="student_dashboard.php">
        <img src="jrmsu.png" style="height:36px;">
        <img src="ccs.png" style="height:36px;">
        CSTUTORHUB — STUDENT
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18"
              stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">

        <!-- ABOUT -->
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_about.php' ? 'active-link' : '' ?>"
             href="student_about.php">
             ABOUT
          </a>
        </li>

        <!-- COURSES (FIXED) -->
     <li class="nav-item">
  <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'student_view_courses') !== false ? 'active-link' : '' ?>"
     href="student_view_courses.php">
     COURSES
  </a>
</li>

        <!-- PROGRESS -->
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_progress.php' ? 'active-link' : '' ?>"
             href="student_progress.php">
             MY PROGRESS
          </a>
        </li>

        <!-- FEEDBACK -->
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_feedback.php' ? 'active-link' : '' ?>"
             href="student_feedback.php">
             FEEDBACK
          </a>
        </li>

        <!-- LOGOUT -->
        <li class="nav-item">
          <a class="nav-link" href="logout.php">LOGOUT</a>
        </li>

      </ul>
    </div>

  </div>
</nav>



<!-- MAIN CONTENT (UNCHANGED) -->
<div class="container mt-5 mb-5">

    <input type="text" id="searchCourse" class="form-control form-control-lg mb-4"
           placeholder="Search courses...">

    <?php if (empty($courses)): ?>
        <div class="alert alert-warning fw-bold text-dark">
            No courses assigned yet.
        </div>

    <?php else: ?>
        <div class="row g-4">

        <?php foreach ($courses as $course):

            $stmtFirstLesson = $pdo->prepare("
                SELECT lesson_id 
                FROM lessons 
                WHERE course_id = ? AND year_level = ? AND block = ?
                ORDER BY lesson_no ASC LIMIT 1
            ");
            $stmtFirstLesson->execute([$course['course_id'], $student['year_level'], $student['block']]);
            $firstLesson = $stmtFirstLesson->fetch(PDO::FETCH_ASSOC);

            $lessonId = $firstLesson['lesson_id'] ?? 0;
        ?>

        <div class="col-md-4">
            <div class="card card-course p-3">

                <?php if ($lessonId): ?>
                    <a href="student_view_lesson_content.php?lesson_id=<?= $lessonId; ?>">
                        <button class="course-btn">
                            <?= htmlspecialchars($course['course_name']); ?> <br>
                            <span style="font-size:0.9rem; font-weight:500;">
                                (<?= $course['category']; ?>)
                            </span>
                        </button>
                    </a>

                <?php else: ?>
                    <button class="course-btn" disabled>
                        <?= htmlspecialchars($course['course_name']); ?><br>
                        <span style="font-size:0.9rem; font-weight:500;">
                            No lessons for your year/block
                        </span>
                    </button>
                <?php endif; ?>

            </div>
        </div>

        <?php endforeach; ?>

        </div>
    <?php endif; ?>

</div>

<!-- FOOTER FIXED -->
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

<script>
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
