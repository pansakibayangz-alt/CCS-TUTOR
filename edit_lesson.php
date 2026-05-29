<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$lesson_id = intval($_GET['id'] ?? 0);
if ($lesson_id <= 0) die("Invalid lesson ID");

$stmt = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) die("Lesson not found");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Edit Lesson</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #0047AB, #1E90FF);
    color: #fff;
    min-height: 100vh;
    margin: 0;
}
.navbar-custom {
    background: linear-gradient(135deg, #002F6C, #0047AB);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}
.navbar-custom .nav-link,
.navbar-custom .navbar-brand {
    color: #fff;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}
.navbar-custom .nav-link:hover { color: #FFD700; }

.container-box {
    background: rgba(255,255,255,0.15);
    padding: 24px;
    border-radius: 18px;
    margin-top: 40px;
    margin-bottom: 60px;
    backdrop-filter: blur(10px);
    box-shadow: 0 0 15px rgba(0,0,0,0.35);
}

.input-light { background: rgba(255,255,255,0.85); color: #000; }

footer {
    position: fixed;
    bottom: 0; left: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(8px);
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    letter-spacing: 0.5px;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
    border-top: 1px solid rgba(255,255,255,0.3);
    z-index: 9999;
    text-align: center;
    padding: 10px;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="instructor_dashboard.php">Instructor Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav"></button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">ABOUT ME</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_students.php">STUDENT LIST</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_lessons.php" style="font-weight:700; color:#FFD700;">LESSONS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">PRE-TEST</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_assessment.php">ASSESSMENT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">STUDENT PROGRESS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">FEEDBACK</a></li>
		<li class="nav-item"><a class="nav-link" href="login.php">LOGOUT</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
    <div class="container-box">

        <h3>Edit Lesson</h3>
        <hr>

        <form action="update_lesson.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="lesson_id" value="<?= $lesson['lesson_id']; ?>">

            <div class="mb-3">
                <label class="form-label">Lesson Number</label>
                <input type="number" name="lesson_no" value="<?= htmlspecialchars($lesson['lesson_no']); ?>"
                       class="form-control input-light" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Lesson Title</label>
                <input type="text" name="lesson_title" value="<?= htmlspecialchars($lesson['lesson_title']); ?>"
                       class="form-control input-light" required>
            </div>

            <!-- Existing Lesson File -->
            <div class="mb-3">
                <label class="form-label">Current File</label><br>
                <?php if ($lesson['lesson_file']): ?>
                    <a class="text-warning" href="<?= $lesson['lesson_file']; ?>" target="_blank">View Existing File</a>
                <?php else: ?>
                    <span>No file uploaded.</span>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload New Lesson File (optional)</label>
                <input type="file" name="lesson_file" accept=".pdf,.ppt,.pptx,.doc,.docx" class="form-control input-light">
            </div>

            <!-- New Syllabus Section -->
            <div class="mb-3 mt-4">
                <label class="form-label">Current Syllabus</label><br>
                <?php if (!empty($lesson['syllabus_file'])): ?>
                    <a class="text-warning" href="<?= $lesson['syllabus_file']; ?>" target="_blank">View Current Syllabus</a>
                <?php else: ?>
                    <span>No syllabus uploaded.</span>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload New Syllabus (PDF only)</label>
                <input type="file" name="syllabus_file" accept=".pdf" class="form-control input-light">
            </div>

            <button class="btn btn-primary" type="submit">Update Lesson</button>
            <a href="instructor_manage_lessons.php" class="btn btn-secondary">Cancel</a>

        </form>

    </div>
</div>

<footer>
    Developed by: <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

</body>
</html>
