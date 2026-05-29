<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die('Instructor not found');
$instructor_id = $instructor['instructor_id'];

$assessment_id = intval($_GET['id'] ?? 0);
if (!$assessment_id) die('Invalid Assessment ID');

// Fetch assessment details
$assessmentStmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_id = ? AND instructor_id = ?");
$assessmentStmt->execute([$assessment_id, $instructor_id]);
$assessment = $assessmentStmt->fetch(PDO::FETCH_ASSOC);
if (!$assessment) die('Assessment not found');

// Fetch assessment items
$itemsStmt = $pdo->prepare("SELECT * FROM assessment_items WHERE assessment_id = ? ORDER BY item_no ASC");
$itemsStmt->execute([$assessment_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses and lessons
$cstmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY category, course_name");
$cstmt->execute([$instructor_id]);
$courses = $cstmt->fetchAll(PDO::FETCH_ASSOC);

$lstmt = $pdo->prepare("SELECT * FROM lessons WHERE instructor_id = ? ORDER BY created_at DESC");
$lstmt->execute([$instructor_id]);
$lessons = $lstmt->fetchAll(PDO::FETCH_ASSOC);

// Collect unique year levels
$year_levels = [];
foreach ($lessons as $l) {
    if (!in_array($l['year_level'], $year_levels)) $year_levels[] = $l['year_level'];
}
sort($year_levels);

// Group blocks by year
$blocks_by_year = [];
foreach ($lessons as $l) {
    $yr = $l['year_level'];
    $blk = $l['block'];
    if (!isset($blocks_by_year[$yr])) $blocks_by_year[$yr] = [];
    if (!in_array($blk, $blocks_by_year[$yr])) $blocks_by_year[$yr][] = $blk;
}

// If Duplicate button was pressed, handle it first and exit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duplicate'])) {
    $new_block = $_POST['duplicate_block'] ?? '';

    if ($new_block && $assessment) {

        // 1. Get the original lesson linked to this assessment
        $lessonFetch = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
        $lessonFetch->execute([$assessment['lesson_id']]);
        $originalLesson = $lessonFetch->fetch(PDO::FETCH_ASSOC);

        if (!$originalLesson) {
            die("Error: Original lesson not found.");
        }

        // 2. Look for the SAME lesson but different block
        $lessonLookup = $pdo->prepare("
            SELECT lesson_id
            FROM lessons
            WHERE lesson_title = ?
            AND year_level = ?
            AND lesson_no = ?
            AND block = ?
            AND instructor_id = ?
            LIMIT 1
        ");

        $lessonLookup->execute([
            $originalLesson['lesson_title'],   // same title
            $originalLesson['year_level'],     // same year level
            $originalLesson['lesson_no'],      // same lesson no
            $new_block,                        // new block
            $instructor_id
        ]);

        $newLesson = $lessonLookup->fetch(PDO::FETCH_ASSOC);

        if (!$newLesson) {
            die("Error: No matching lesson found for this block.");
        }

        $newLessonId = $newLesson['lesson_id'];

        // 3. Duplicate assessment
        $stmt = $pdo->prepare("INSERT INTO assessments 
            (course_id, lesson_id, assessment_type, instructions, certificate_text, year_level, block, instructor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $assessment['course_id'],
            $newLessonId,
            $assessment['assessment_type'],
            $assessment['instructions'],
            $assessment['certificate_text'],
            $assessment['year_level'],
            $new_block,
            $instructor_id
        ]);

        $new_assessment_id = $pdo->lastInsertId();

        // 4. Copy all assessment items
        foreach ($items as $item) {
			// Only decode/encode options if it exists and it's a MULTIPLE CHOICE
			if ($assessment['assessment_type'] === 'MULTIPLE CHOICE' && $item['options']) {
				$options = json_decode($item['options'], true);
				$options_json = json_encode($options, JSON_UNESCAPED_UNICODE);
			} else {
				$options_json = null; // keep NULL for non-MCQ items
			}

			$itemStmt = $pdo->prepare("INSERT INTO assessment_items 
				(assessment_id, item_no, question, options, answer)
				VALUES (?, ?, ?, ?, ?)");

			$itemStmt->execute([
				$new_assessment_id,
				$item['item_no'],
				$item['question'],
				$options_json,
				$item['answer']
			]);
		}

        header("Location: instructor_manage_assessment.php?id=$new_assessment_id&msg=duplicated");
        exit;
    }
}

// Handle POST updates for assessment and items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ensure uploads directory exists and is writable
    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    $public_upload_dir = 'uploads/'; // relative path accessible by browser
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_certificate_filename = null;

    // Handle certificate file upload (if provided)
    if (isset($_FILES['certificate_html']) && $_FILES['certificate_html']['error'] === 0) {
    $certificate_file = $_FILES['certificate_html'];

    if ($certificate_file['type'] === 'text/html') {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Remove old certificate file if exists
        if (!empty($assessment['certificate_text']) && file_exists($assessment['certificate_text'])) {
            unlink($assessment['certificate_text']);
        }

        // Move new file
        $newFile = $upload_dir . uniqid('cert_') . '.html';
        if (move_uploaded_file($certificate_file['tmp_name'], $newFile)) {
            $certificate_text = $newFile;

            // OPTIONAL: Delete old generated certificates for this assessment
            $delCerts = $pdo->prepare("DELETE FROM certificates WHERE assessment_id=?");
            $delCerts->execute([$assessment_id]);
        } else {
            die('Upload failed. Check folder permissions.');
        }
    } else {
        die('Invalid file type. Please upload an HTML file.');
    }
}

    // Collect posted values
    $year_level = $_POST['year_level'] ?? null;
    $block = $_POST['block'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $lesson_id = $_POST['lesson_id'] ?? null;
    $assessment_type = $_POST['assessment_type'] ?? null;
    $instructions = $_POST['instructions'] ?? '';

    // Preserve old certificate if no new upload; otherwise use new filename
    $certificate_text = $new_certificate_filename ?? $assessment['certificate_text'] ?? null;

    if ($course_id && $lesson_id && $assessment_type) {
        // Update assessment info
        $update = $pdo->prepare("UPDATE assessments SET course_id=?, lesson_id=?, assessment_type=?, instructions=?, certificate_text=?, year_level=?, block=? WHERE assessment_id=?");
        $update->execute([$course_id, $lesson_id, $assessment_type, $instructions, $certificate_text, $year_level, $block, $assessment_id]);

        // Delete all existing items
        $del = $pdo->prepare("DELETE FROM assessment_items WHERE assessment_id=?");
        $del->execute([$assessment_id]);

        // Insert all submitted items
        $questions = $_POST['question'] ?? [];
        $answers = $_POST['answer'] ?? [];
        $optionsArr = $_POST['options'] ?? [];

        foreach ($questions as $i => $question) {
            $question = trim($question);
            $answer = trim($answers[$i] ?? '');
            $options = $optionsArr[$i] ?? null;

            if ($question && $answer) {
                $opt_json = null;
                if ($assessment_type === 'MULTIPLE CHOICE' && $options) {
                    $opt_json = json_encode($options, JSON_UNESCAPED_UNICODE);
                }

                $itemStmt = $pdo->prepare("INSERT INTO assessment_items (assessment_id, item_no, question, options, answer)
                                           VALUES (?, ?, ?, ?, ?)");
                $itemStmt->execute([$assessment_id, $i + 1, $question, $opt_json, $answer]);
            }
        }
    }

    header("Location: instructor_manage_assessment.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Assessment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#0047AB,#1E90FF); color:#fff; }
.navbar-custom { background: linear-gradient(135deg,#002F6C,#0047AB); }
.navbar-custom .nav-link,.navbar-custom .navbar-brand { color:#fff; font-weight:600; }
.navbar-custom .nav-link.active { color:#FFD700; }
.container {
    padding-bottom: 60px;
}
.container-box { background: rgba(255,255,255,0.15); padding:24px; border-radius:18px; margin-top:30px; backdrop-filter:blur(10px); }
.input-light { background: rgba(255,255,255,0.85); color:#000; }
.card { background: rgba(255,255,255,0.1); }
footer { position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.55); backdrop-filter: blur(8px); color: #ffffff; font-size: 1.1rem; font-weight: 600; font-family: 'Poppins', sans-serif; letter-spacing: 0.5px; text-shadow: 1px 1px 3px rgba(0,0,0,0.8); border-top: 1px solid rgba(255,255,255,0.3); z-index: 9999; text-align:center; padding:10px;}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="instructor_dashboard.php">Instructor Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">ABOUT ME</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_students.php">STUDENT LIST</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">LESSONS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">PRE-TEST</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_assessment.php" style="font-weight:700; color:#FFD700;">ASSESSMENT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">STUDENT PROGRESS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">FEEDBACK</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">LOGOUT</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="container-box">
    <h3>Edit Assessment</h3>

    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-2">
          <label>Year Level</label>
          <select name="year_level" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach ($year_levels as $y): ?>
                <option value="<?= $y ?>" <?= $assessment['year_level'] == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label>Block</label>
          <select name="block" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php
            $selected_year = $assessment['year_level'];
            if (isset($blocks_by_year[$selected_year])) {
                foreach ($blocks_by_year[$selected_year] as $b) {
                    echo '<option value="' . $b . '" ' . ($assessment['block'] == $b ? 'selected' : '') . '>' . $b . '</option>';
                }
            }
            ?>
          </select>
        </div>

        <div class="col-md-4">
          <label>Course</label>
          <select name="course_id" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($courses as $c): ?>
              <option value="<?= $c['course_id'] ?>" <?= $assessment['course_id'] == $c['course_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label>Lesson</label>
          <select name="lesson_id" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($lessons as $l): ?>
              <option value="<?= $l['lesson_id'] ?>" <?= $assessment['lesson_id'] == $l['lesson_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['lesson_title']) ?> (<?= $l['year_level'] . '-' . $l['block'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label>Assessment Type</label>
          <select name="assessment_type" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach(['MULTIPLE CHOICE', 'ENUMERATION', 'FILL IN THE BLANK', 'TRUE OR FALSE'] as $type): ?>
              <option value="<?= $type ?>" <?= $assessment['assessment_type'] == $type ? 'selected' : '' ?>>
                <?= $type ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-9">
          <label>Instructions</label>
          <input type="text" name="instructions" class="form-control input-light" value="<?= htmlspecialchars($assessment['instructions']) ?>">
        </div>

        <div class="col-md-12">
			<label>Certificate Template (.html)</label>
			<input type="file" name="certificate_html" class="form-control input-light" accept=".html,.htm,text/html">

			<h5 class="mt-3">Preview Certificate Template:</h5>
			<?php
			$cert_path = $assessment['certificate_text'] ?? '';
			$iframe_height = 500;

			if (!empty($cert_path) && file_exists($cert_path)) {
				// Make it clickable: open in new tab
				echo '<p style="margin-bottom:10px;">
						<a href="' . htmlspecialchars($cert_path, ENT_QUOTES) . '" target="_blank" 
						   style="color:#FFD700; text-decoration:underline;">
						   Click here to open full certificate in new tab
						</a>
					  </p>';

				// Show embedded preview
				echo '<iframe src="' . htmlspecialchars($cert_path, ENT_QUOTES) . '" width="100%" height="' . $iframe_height . 'px" style="border:1px solid #ccc; border-radius:8px;"></iframe>';
			} else {
				echo '<p style="color:#fff; background: rgba(0,0,0,0.3); padding:10px; border-radius:8px;">
						Uploaded certificate file cannot be found or not uploaded yet. Upload a .html file to preview it here.
					  </p>';
			}
			?>
		</div>
      </div>

      <hr>

      <!-- Assessment Items -->
      <h4>Assessment Items</h4>
      <div id="itemsContainer">
        <?php foreach ($items as $i => $it):
            $opt = $it['options'] ? json_decode($it['options'], true) : [];
        ?>
        <div class="card p-3 mb-2">
          <div class="row g-2">
            <div class="col-md-1">
              <label>#</label>
              <input type="number" name="item_no[]" class="form-control input-light" value="<?= $i + 1 ?>" required>
            </div>
            <div class="col-md-7">
              <label>Question</label>
              <input type="text" name="question[]" class="form-control input-light" value="<?= htmlspecialchars($it['question']) ?>" required>
            </div>
            <div class="col-md-3">
              <label>Answer</label>
              <input type="text" name="answer[]" class="form-control input-light" value="<?= htmlspecialchars($it['answer']) ?>" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
            </div>
            <?php if ($assessment['assessment_type'] == 'MULTIPLE CHOICE'): ?>
            <div class="col-12">
              <?php foreach(['A','B','C','D'] as $l): ?>
                <input type="text" name="options[<?= $i ?>][<?= $l ?>]" placeholder="Option <?= $l ?>" value="<?= htmlspecialchars($opt[$l] ?? '') ?>" class="form-control input-light mb-1">
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-success">Update Assessment</button>
      </div>
      <div class="mt-3">
        <a href="instructor_manage_assessment.php" class="btn btn-secondary">Back</a>
      </div>
    </form>

    <hr>

    <!-- Duplicate Assessment Button -->
    <form method="POST">
      <div class="row g-3">
        <div class="col-md-4">
          <label>Duplicate to Block</label>
          <select name="duplicate_block" class="form-select input-light" required>
            <option value="">-- Select Block --</option>
            <?php foreach ($blocks_by_year[$assessment['year_level']] as $block): ?>
              <option value="<?= $block ?>"><?= $block ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" name="duplicate" class="btn btn-warning">Duplicate Assessment</button>
      </div>
    </form>
  </div>
</div>

<!-- FOOTER -->
<footer>
    Developed by: <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add your JS code for removing items and handling dynamic generation of new items
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.card').remove();
    });
});
</script>
</body>
</html>
