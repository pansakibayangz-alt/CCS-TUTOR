<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

if (!isset($_GET['lesson_id'])) { die("Invalid lesson."); }
$lessonId = $_GET['lesson_id'];
$schoolId = $_SESSION['school_id'];

// Fetch lesson
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) die("Lesson not found.");

// Handle marking as done
if (isset($_POST['mark_done'])) {
    $stmtInsert = $pdo->prepare("INSERT IGNORE INTO student_lesson_completion (student_id, lesson_id) VALUES (?, ?)");
    $stmtInsert->execute([$schoolId, $_POST['lesson_id']]);
    header("Location: student_view_lesson_content.php?lesson_id=" . $_POST['lesson_id']);
    exit;
}

// Check pretest
$stmtPretest = $pdo->prepare("SELECT * FROM pretests WHERE lesson_id = ? AND year_level = ? AND block = ?");
$stmtPretest->execute([$lessonId, $lesson['year_level'], $lesson['block']]);
$pretest = $stmtPretest->fetch(PDO::FETCH_ASSOC);

if ($pretest) {
    $stmtAttempt = $pdo->prepare("SELECT * FROM student_pretest_attempts WHERE student_id = ? AND pretest_id = ?");
    $stmtAttempt->execute([$schoolId, $pretest['pretest_id']]);
    if (!$stmtAttempt->fetch()) {
        die("You must complete the pretest before viewing this lesson.");
    }
}

// Check completion
$stmtDone = $pdo->prepare("SELECT * FROM student_lesson_completion WHERE student_id = ? AND lesson_id = ?");
$stmtDone->execute([$schoolId, $lessonId]);
$doneReading = $stmtDone->fetch();
?>

<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($lesson['lesson_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}

/* BODY (MATCH PRETEST THEME) */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy), var(--navy2));
    color: var(--white);
    min-height: 100vh;
    padding-bottom: 80px;
}

/* NAVBAR */
.navbar-custom {
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}

.navbar-custom .navbar-brand {
    color: var(--gold) !important;
    font-weight: 700;
}

/* LESSON BOX (GLASS STYLE LIKE PRETEST) */
.lesson-box {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.55);
    border-radius: 14px;
    backdrop-filter: blur(8px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.45);
    padding: 25px;
}

/* BUTTONS */
.btn-success {
    background: var(--gold);
    border: none;
    color: #000;
    font-weight: 700;
}

.btn-success:hover {
    background: #e6c200;
}

.btn-warning {
    background: var(--gold);
    border: none;
    color: #000;
    font-weight: 700;
}

.btn-warning:hover {
    background: #e6c200;
}

/* IFRAME */
iframe {
    width: 100%;
    height: 80vh;
    border: none;
    border-radius: 10px;
}

/* FOOTER (MATCH PRETEST) */
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    backdrop-filter: blur(8px);
    color: #fff;
    border-top: 1px solid rgba(255,215,0,0.12);
    font-weight: 600;
}
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand"><?= htmlspecialchars($lesson['lesson_title']); ?></a>
  </div>
</nav>

<div class="container mt-5">
    <a href="javascript:history.back()" class="btn btn-warning fw-bold mb-4">← Back</a>

    <div class="lesson-box">

        <?php if (!empty($lesson['lesson_file'])): ?>
            <iframe src="/BSCS%20PROGRESS%20EDIT/<?= $lesson['lesson_file']; ?>"></iframe>

            <div class="mt-3">
                <?php if ($doneReading): ?>
                    <button class="btn btn-success" disabled>✅ Done Reading</button>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                        <button type="submit" name="mark_done" class="btn btn-success">
                            Mark as Done Reading
                        </button>
                    </form>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p class="text-warning">No lesson file uploaded.</p>
        <?php endif; ?>

    </div>
</div>

<footer class="text-center py-3">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

</body>
</html>