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

$instructor_id = $lesson['instructor_id'];
$year_level = $lesson['year_level'];
$course_id = $lesson['course_id'];
$course_name = $lesson['course_name'];
$category = $lesson['category'];

// Fetch available blocks for this year_level **where instructor has students**
$blockStmt = $pdo->prepare("
    SELECT DISTINCT block 
    FROM students 
    WHERE year_level = ? AND instructor_id = ? 
    ORDER BY block
");
$blockStmt->execute([$year_level, $instructor_id]);
$blocks = $blockStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($blocks)) {
    die("You have no students in this year level to duplicate the lesson.");
}

// Ensure original block is first in the dropdown
$originalBlock = $lesson['block'];
$blocks = array_diff($blocks, [$originalBlock]); // remove original from blocks
array_unshift($blocks, $originalBlock); // add original at start

// Check if duplicate already exists
$checkStmt = $pdo->prepare("
    SELECT COUNT(*) FROM lessons 
    WHERE course_name = ? 
    AND category = ?
    AND lesson_no = ?
    AND lesson_title = ?
    AND year_level = ?
    AND block = ?
");
$checkStmt->execute([
    $course_name,
    $category,
    $lesson['lesson_no'],
    $lesson['lesson_title'],
    $year_level,
    $_POST['target_block'] ?? $lesson['block']
]);
$isDuplicate = $checkStmt->fetchColumn() > 0;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_block = $_POST['target_block'] ?? '';

    // ✅ Validate that selected block has students assigned to this instructor
    $validBlockStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM students 
        WHERE year_level = ? AND block = ? AND instructor_id = ?
    ");
    $validBlockStmt->execute([$year_level, $target_block, $instructor_id]);
    if ($validBlockStmt->fetchColumn() == 0) {
        die("Error: Selected block has no students assigned to you.");
    }

    // Duplicate lesson file
    $newLessonFile = null;
    if (!empty($lesson['lesson_file'])) {
        $srcPath = __DIR__ . '/' . $lesson['lesson_file'];
        if (file_exists($srcPath)) {
            $ext = pathinfo($srcPath, PATHINFO_EXTENSION);
            $newName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $dir = __DIR__ . '/uploads/lessons/' . $instructor_id;
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $dstPath = $dir . '/' . $newName;
            copy($srcPath, $dstPath);
            $newLessonFile = "uploads/lessons/$instructor_id/$newName";
        }
    }

    // Duplicate syllabus file
    $newSyllabusFile = null;
    if (!empty($lesson['syllabus_file'])) {
        $srcPath = __DIR__ . '/' . $lesson['syllabus_file'];
        if (file_exists($srcPath)) {
            $ext = pathinfo($srcPath, PATHINFO_EXTENSION);
            $newName = "syllabus_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $dir = __DIR__ . '/uploads/lessons/' . $instructor_id;
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $dstPath = $dir . '/' . $newName;
            copy($srcPath, $dstPath);
            $newSyllabusFile = "uploads/lessons/$instructor_id/$newName";
        }
    }

    // Insert duplicate lesson
    $ins = $pdo->prepare("
        INSERT INTO lessons 
        (instructor_id, course_id, course_name, category, year_level, block,
         lesson_no, lesson_title, lesson_file, syllabus_file)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    $ins->execute([
        $instructor_id,
        $course_id,
        $course_name,
        $category,
        $year_level,
        $target_block,
        $lesson['lesson_no'],
        $lesson['lesson_title'],
        $newLessonFile,
        $newSyllabusFile
    ]);

    $newLessonId = $pdo->lastInsertId();

    // Duplicate pretests + items
    $preStmt = $pdo->prepare("SELECT * FROM pretests WHERE lesson_id = ?");
    $preStmt->execute([$lesson_id]);
    $pretests = $preStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pretests as $pretest) {
        $insPre = $pdo->prepare("
            INSERT INTO pretests
            (instructor_id, course_id, lesson_id, pretest_type, instructions, year_level, block)
            VALUES (?,?,?,?,?,?,?)
        ");
        $insPre->execute([
            $pretest['instructor_id'],
            $pretest['course_id'],
            $newLessonId,
            $pretest['pretest_type'],
            $pretest['instructions'],
            $year_level,
            $target_block
        ]);
        $newPretestId = $pdo->lastInsertId();

        $itemsStmt = $pdo->prepare("SELECT * FROM pretest_items WHERE pretest_id = ?");
        $itemsStmt->execute([$pretest['pretest_id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $insItem = $pdo->prepare("
                INSERT INTO pretest_items
                (pretest_id, item_no, question, options, answer)
                VALUES (?,?,?,?,?)
            ");
            $insItem->execute([
                $newPretestId,
                $item['item_no'],
                $item['question'],
                $item['options'],
                $item['answer']
            ]);
        }
    }

    header("Location: instructor_manage_lessons.php?msg=duplicated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Duplicate Lesson</title>
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
    position: fixed; bottom: 0; left: 0;
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

        <h3>Duplicate Lesson: <?= htmlspecialchars($lesson['lesson_title']); ?></h3>
        <hr>

        <form method="POST" onsubmit="return confirmDuplicate();">

            <!-- Read-only Lesson Details -->
            <div class="mb-3">
                <label class="form-label">Course Name</label>
                <input type="text" class="form-control input-light" value="<?= htmlspecialchars($lesson['course_name']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" class="form-control input-light" value="<?= htmlspecialchars($lesson['category']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Lesson Number</label>
                <input type="text" class="form-control input-light" value="<?= htmlspecialchars($lesson['lesson_no']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Lesson Title</label>
                <input type="text" class="form-control input-light" value="<?= htmlspecialchars($lesson['lesson_title']); ?>" readonly>
            </div>

            <!-- Existing Lesson File -->
            <div class="mb-3">
                <label class="form-label">Existing Lesson File</label><br>
                <?php if ($lesson['lesson_file']): ?>
                    <a class="text-warning" href="<?= $lesson['lesson_file']; ?>" target="_blank">View Lesson File</a>
                <?php else: ?>
                    <span>No lesson file uploaded.</span>
                <?php endif; ?>
            </div>

            <!-- Existing Syllabus File -->
            <div class="mb-3">
                <label class="form-label">Existing Syllabus File</label><br>
                <?php if (!empty($lesson['syllabus_file'])): ?>
                    <a class="text-warning" href="<?= $lesson['syllabus_file']; ?>" target="_blank">View Syllabus</a>
                <?php else: ?>
                    <span>No syllabus uploaded.</span>
                <?php endif; ?>
            </div>
			
			<div class="mb-3">
                <label class="form-label">Year Level</label>
                <input type="text" class="form-control input-light" value="<?= htmlspecialchars($lesson['year_level']); ?>" readonly>
            </div>

            <!-- Editable Target Block -->
            <div class="mb-3 mt-3">
                <label class="form-label">Select Target Block:</label>
                <select name="target_block" class="form-select input-light" required>
    <?php foreach($blocks as $block): ?>
        <option value="<?= $block; ?>" <?= $block === $originalBlock ? 'selected' : ''; ?>>
            <?= $block; ?>
        </option>
    <?php endforeach; ?>
</select>
            </div>

            <button 
    class="btn btn-primary" 
    type="submit"
    <?= $isDuplicate ? 'disabled title="Already exists"' : '' ?>
>
    Duplicate Lesson
</button>
            <a href="instructor_manage_lessons.php" class="btn btn-secondary">Cancel</a>

        </form>

    </div>
</div>

<footer>
    Developed by: <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

<script>
const selectBlock = document.querySelector('select[name="target_block"]');
const duplicateBtn = document.querySelector('button[type="submit"]');

// original block
const originalBlock = "<?= $originalBlock; ?>";

// Set initial state: disable if the original block is selected
if (selectBlock.value === originalBlock) {
    duplicateBtn.disabled = true;
    duplicateBtn.title = "Already exists for this block";
}

// Listen for changes in dropdown
selectBlock.addEventListener('change', () => {
    if (selectBlock.value === originalBlock) {
        duplicateBtn.disabled = true;
        duplicateBtn.title = "Already exists for this block";
    } else {
        duplicateBtn.disabled = false;
        duplicateBtn.title = "";
    }
});

function confirmDuplicate() {
    return confirm("Are you sure you want to duplicate this lesson?");
}
</script>
</body>
</html>
