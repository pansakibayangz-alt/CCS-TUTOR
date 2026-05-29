<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Fetch student info
$schoolId = $_SESSION['school_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmt->execute([$schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

// Fetch courses that contain lessons for this student's year + block
$stmtCourses = $pdo->prepare("
    SELECT DISTINCT c.*
    FROM courses c
    INNER JOIN lessons l ON l.course_id = c.course_id
    WHERE l.year_level = ? AND l.block = ?
");
$stmtCourses->execute([$student['year_level'], $student['block']]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Progress</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Merriweather:wght@700&display=swap" rel="stylesheet">

<style>
/* =======================
   UNIVERSITY NAVY + GOLD THEME
======================= */
:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}

/* Body */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy), var(--navy2));
    color: var(--white);
    min-height: 100vh;
    padding-bottom: 120px;
    margin: 0;
}

/* ACTIVE LINK */
.active-link {
    color: var(--gold) !important;
    text-decoration: underline;
    text-underline-offset: 5px;
    font-weight: 700;
}

/* NAVBAR */
.navbar-custom {
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
    padding-top: 6px;
    padding-bottom: 6px;
}

/* BRAND */
.navbar-brand {
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    font-weight: 700;
    letter-spacing: 0.6px;
}

/* NAV LINKS */
.navbar-custom .nav-link {
    color: rgba(255,255,255,0.85) !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.6px;
}
.navbar-custom .nav-link:hover {
    color: var(--gold) !important;
}

/* CARDS */
.card {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.4);
    border-radius: 14px;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.45);
    margin-bottom: 25px;
}
.card-header {
    background: rgba(255,215,0,0.15);
    border-bottom: 1px solid rgba(255,215,0,0.3);
    font-weight: 700;
    color: var(--gold);
}

/* LESSON ROW */
.lesson-row {
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 12px 0;
    color: var(--white);
}
.lesson-row h5 {
    font-weight: 700;
    color: var(--white);
}


/* LABEL */
.label {
    color: var(--gold);
    font-weight: 600;
}

/* PROGRESS BARS */
.progress {
    background: rgba(255,255,255,0.15);
    height: 20px;
    border-radius: 10px;
	
}
.progress-bar {
    font-weight: 700;
    font-size: 0.85rem;
    color: black;
}

/* FOOTER */
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    color: #fff;
    padding: 10px;
    text-align: center;
    border-top: 1px solid rgba(255,215,0,0.2);
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

<!-- =======================
         NAVBAR
======================= -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">

    <!-- BRAND -->
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

        <li class="nav-item">
          <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='student_about.php')?'active-link':'' ?>"
             href="student_about.php">ABOUT</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='student_view_courses.php')?'active-link':'' ?>"
             href="student_view_courses.php">COURSES</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='student_progress.php')?'active-link':'' ?>"
             href="student_progress.php">MY PROGRESS</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='student_feedback.php')?'active-link':'' ?>"
             href="student_feedback.php">FEEDBACK</a>
        </li>

        <li class="nav-item">
         <a class="nav-link" href="logout.php">LOGOUT</a>

        </li>

      </ul>
    </div>

  </div>
</nav>

<!-- =======================
        PAGE CONTENT
======================= -->
<div class="container mt-4" style="max-width:1100px;">
    <h4 class="fw-bold mb-4">
        School ID: <?= htmlspecialchars($student['school_id']) ?> • 
        Year: <?= $student['year_level'] ?> • Block: <?= $student['block'] ?>
    </h4>

    <input type="text" id="lessonSearch" class="form-control mb-4"
           placeholder="Search courses or lessons...">

    <?php if(empty($courses)): ?>
        <div class="alert alert-warning fw-bold text-dark">No courses available.</div>
    <?php else: ?>

    <?php foreach($courses as $course): ?>
    <div class="card">
        <div class="card-header">
            <?= htmlspecialchars($course['course_name']); ?>
        </div>

        <div class="card-body">

            <?php
            // Fetch lessons
            $stmtLessons = $pdo->prepare("
                SELECT * FROM lessons 
                WHERE course_id=? AND year_level=? AND block=? 
                ORDER BY lesson_no ASC
            ");
            $stmtLessons->execute([$course['course_id'], $student['year_level'], $student['block']]);
            $lessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

            $completedLessonsCount = 0;
            $totalLessonsCount = count($lessons);
            ?>

            <?php foreach($lessons as $lesson): ?>

            <?php
            // Reading completion
            $stmtDone = $pdo->prepare("SELECT * FROM student_lesson_completion WHERE student_id=? AND lesson_id=?");
            $stmtDone->execute([$schoolId, $lesson['lesson_id']]);
            $doneReading = $stmtDone->fetch() ? true : false;
            if ($doneReading) $completedLessonsCount++;

            // PRETEST
            $stmtPre = $pdo->prepare("SELECT * FROM pretests WHERE lesson_id=? AND year_level=? AND block=?");
            $stmtPre->execute([$lesson['lesson_id'], $student['year_level'], $student['block']]);
            $pretest = $stmtPre->fetch();

            // PRETEST ATTEMPTS
            $prePercent = 0;
            $preAttempts = [];

            if ($pretest) {
                $stmtPreA = $pdo->prepare("
                    SELECT attempt_no, score, completed_at
                    FROM student_pretest_attempts
                    WHERE student_id=? AND pretest_id=?
                    ORDER BY attempt_no ASC
                ");
                $stmtPreA->execute([$schoolId, $pretest['pretest_id']]);
                $preAttempts = $stmtPreA->fetchAll(PDO::FETCH_ASSOC);

                $scores = array_column($preAttempts, 'score');
                $highest = !empty($scores) ? max($scores) : 0;

                $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM pretest_items WHERE pretest_id=?");
                $stmtTotal->execute([$pretest['pretest_id']]);
                $total = $stmtTotal->fetch()['total'];

                $prePercent = $total > 0 ? round(($highest / $total) * 100) : 0;
            }

            // ASSESSMENT
            $stmtAs = $pdo->prepare("SELECT * FROM assessments WHERE lesson_id=? AND year_level=? AND block=?");
            $stmtAs->execute([$lesson['lesson_id'], $student['year_level'], $student['block']]);
            $assessment = $stmtAs->fetch();

            $asPercent = 0;
            $asAttempts = [];

            if ($assessment) {
                $stmtAsA = $pdo->prepare("
                    SELECT attempt_no, score, taken_at
                    FROM student_assessment_attempts
                    WHERE student_id=? AND assessment_id=?
                    ORDER BY attempt_no ASC
                ");
                $stmtAsA->execute([$schoolId, $assessment['assessment_id']]);
                $asAttempts = $stmtAsA->fetchAll(PDO::FETCH_ASSOC);

                $scores = array_column($asAttempts, 'score');
                $highest = !empty($scores) ? max($scores) : 0;

                $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM assessment_items WHERE assessment_id=?");
                $stmtTotal->execute([$assessment['assessment_id']]);
                $total = $stmtTotal->fetch()['total'];

                $asPercent = $total > 0 ? round(($highest / $total) * 100) : 0;
            }

            $readingPercent = $doneReading ? 100 : 0;
            ?>

            <div class="lesson-row">
                <h5 class="mb-2">
                    <?= $lesson['lesson_no'] . ". " . htmlspecialchars($lesson['lesson_title']) ?>
                    <?php if ($doneReading): ?>
                    <span class="badge bg-success">Reading Done</span>
                    <?php endif; ?>
                </h5>

                <!-- Reading -->
                <div class="label">Reading Progress</div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" style="width: <?= $readingPercent ?>%;">
                        <?= $readingPercent ?>%
                    </div>
                </div>

                <!-- Pretest -->
                <div class="label">Pretest Progress</div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-info" style="width: <?= $prePercent ?>%;">
                        <?= $prePercent ?>%
                    </div>
                </div>

                <?php if (!empty($preAttempts)): ?>
                <strong class="attempt-title">Pretest Attempts:</strong>
                <ul class="attempt-list">
                    <?php foreach ($preAttempts as $pa): ?>
                    <li>Attempt <?= $pa['attempt_no'] ?> — Score: <?= $pa['score'] ?> (<?= $pa['completed_at'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <!-- Assessment -->
                <div class="label">Assessment Progress</div>
                <div class="progress mb-2">
                    <div class="progress-bar" style="width: <?= $asPercent ?>%; background: linear-gradient(90deg, #a56eff, #7d56ff);">
                        <?= $asPercent ?>%
                    </div>
                </div>

                <?php if (!empty($asAttempts)): ?>
                <strong class="attempt-title">Assessment Attempts:</strong>
                <ul class="attempt-list">
                    <?php foreach ($asAttempts as $aa): ?>
                    <li>Attempt <?= $aa['attempt_no'] ?> — Score: <?= $aa['score'] ?> (<?= $aa['taken_at'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <?php endforeach; ?>

            <?php
            // Overall Course Progress
            $overall = $totalLessonsCount > 0
                ? round(($completedLessonsCount / $totalLessonsCount) * 100)
                : 0;
            ?>

            <p class="mt-3 fw-bold" style="color: var(--gold);">
                Overall Lesson Completion: <?= $completedLessonsCount ?>/<?= $totalLessonsCount ?>
            </p>

            <div class="progress mb-2">
                <div class="progress-bar bg-warning" style="width: <?= $overall ?>%; color:black;">
                    <?= $overall ?>%
                </div>
            </div>

        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>


<!-- FOOTER -->
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
// Live search filter
document.getElementById('lessonSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const cards = document.querySelectorAll('.card');

    cards.forEach(card => {
        const courseName = card.querySelector('.card-header').textContent.toLowerCase();
        const lessons = card.querySelectorAll('.lesson-row');
        let visible = false;

        lessons.forEach(row => {
            const title = row.querySelector('h5').textContent.toLowerCase();
            if (title.includes(filter) || courseName.includes(filter)) {
                row.style.display = '';
                visible = true;
            } else {
                row.style.display = 'none';
            }
        });

        card.style.display = visible ? '' : 'none';
    });
});
</script>

</body>
</html>
