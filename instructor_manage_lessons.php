<?php
// instructor_manage_lessons.php (CLEAN VERSION – NO PRETEST / LEARNING / ASSESSMENT)
// ----------------------------------------------------------------------------
// Required DB schema (example):
//
// CREATE TABLE instructor (instructor_id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100));
// CREATE TABLE students (student_id INT AUTO_INCREMENT PRIMARY KEY, surname VARCHAR(100), firstname VARCHAR(100), middlename VARCHAR(100), year_level TINYINT, block CHAR(1), instructor_id INT DEFAULT NULL, facebook_name VARCHAR(255), phone_number VARCHAR(50));
// CREATE TABLE courses (course_id INT AUTO_INCREMENT PRIMARY KEY, course_name VARCHAR(255), category ENUM('Minor','Major'), instructor_id INT, UNIQUE(course_name, category, instructor_id));
// CREATE TABLE lessons (
//   lesson_id INT AUTO_INCREMENT PRIMARY KEY,
//   instructor_id INT,
//   course_id INT NULL,
//   course_name VARCHAR(255),
//   category ENUM('Minor','Major'),
//   year_level TINYINT,
//   block CHAR(1),
//   lesson_no INT,
//   lesson_title VARCHAR(255),
//   lesson_file VARCHAR(255),
//   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// );
//
// Folder: uploads/lessons/
// ----------------------------------------------------------------------------

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php'; // PDO connection

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die('Instructor not found');
$instructor_id = $instructor['instructor_id'];

// helper for file upload
function handleUpload($fileField, $subfolder = '') {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) return null;
    $uploadDir = __DIR__ . '/uploads/lessons/' . $subfolder;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $original = basename($_FILES[$fileField]['name']);
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    $safe = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target = $uploadDir . '/' . $safe;
    if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $target)) {
        return 'uploads/lessons/' . ($subfolder ? ($subfolder . '/') : '') . $safe;
    }
    return null;
}

// Handle creation of lessons
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category = $_POST['category'] ?? null;
    $year_level = $_POST['year_level'] ?? null;
    $block = $_POST['block'] ?? null;

    // ✅ SERVER-SIDE VALIDATION: Ensure Year Level + Block belongs to this instructor
    $valid = $pdo->prepare("SELECT 1 FROM students WHERE instructor_id=? AND year_level=? AND block=? LIMIT 1");
    $valid->execute([$instructor_id, $year_level, $block]);
    if (!$valid->fetch()) {
        die("Error: Selected Year Level / Block is invalid for this instructor.");
    }

    // Determine course
    $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;
    $manual_course = trim($_POST['course_name'] ?? '');

    if (!$course_id && $manual_course !== '') {
        $cStmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_name=? AND category=? AND instructor_id=?");
        $cStmt->execute([$manual_course, $category, $instructor_id]);
        $existing = $cStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $course_id = $existing['course_id'];
        } else {
            $ins = $pdo->prepare("INSERT INTO courses (course_name, category, instructor_id) VALUES (?, ?, ?)");
            $ins->execute([$manual_course, $category, $instructor_id]);
            $course_id = $pdo->lastInsertId();
        }
    }

    $num_lessons = max(0, intval($_POST['num_lessons'] ?? 0));
    $lesson_titles = $_POST['lesson_title'] ?? [];
    $lesson_nos = $_POST['lesson_no'] ?? [];

    // process each lesson
    for ($i = 0; $i < $num_lessons; $i++) {

        $ln = intval($lesson_nos[$i] ?? ($i + 1));
        $title = trim($lesson_titles[$i] ?? '');

        // handle upload
        $lessonFileField = 'lesson_file_' . $i;
        $lesson_path = handleUpload($lessonFileField, $instructor_id);
        $syllabusFileField = 'syllabus_file_' . $i;
        $syllabus_path = handleUpload($syllabusFileField, $instructor_id);

        // ✅ INSERT LESSON
        $ins = $pdo->prepare("INSERT INTO lessons (
            instructor_id, course_id, course_name, category, year_level, block,
            lesson_no, lesson_title, lesson_file, syllabus_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $ins->execute([
            $instructor_id,
            $course_id,
            $manual_course ?: null,
            $category,
            $year_level,
            $block,
            $ln,
            $title,
            $lesson_path,
            $syllabus_path
        ]);
    }

    header('Location: instructor_manage_lessons.php?msg=created');
    exit;
}

// Fetch assigned students
$stmt = $pdo->prepare("SELECT DISTINCT year_level, block FROM students WHERE instructor_id = ? ORDER BY year_level, block");
$stmt->execute([$instructor_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses
$cstmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY category, course_name");
$cstmt->execute([$instructor_id]);
$courses = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing lessons
$lessonsStmt = $pdo->prepare("SELECT l.*, COALESCE(c.course_name, l.course_name) AS display_course 
                              FROM lessons l 
                              LEFT JOIN courses c ON l.course_id = c.course_id 
                              WHERE l.instructor_id = ? ORDER BY l.created_at DESC");
$lessonsStmt->execute([$instructor_id]);
$existingLessons = $lessonsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instructor - Manage Lessons</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>

:root{
    --navy: #0b2b4a;
    --navy-2: #08304f;

    --gold: #FFD700;
    --muted: rgba(255,255,255,0.9);

    --card-bg: rgba(255,255,255,0.04);
    --glass-border: rgba(255,230,0,0.14);
}

/* BODY */
body{
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
    margin:0;
    min-height:100vh;
}
	.container {
    padding-bottom: 120px !important;
}

/* NAVBAR (Copied from dashboard) */
.navbar-custom{
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
}
.navbar-brand{
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    letter-spacing: 0.6px;
    font-weight:700;
}
.navbar-custom .nav-link{
    color: rgba(255,255,255,0.9);
    font-weight:600;
    text-transform:uppercase;
    font-size:0.83rem;
}
.navbar-custom .nav-link:hover{
    color: var(--gold);
    text-decoration:underline;
}
.link-gold { color: var(--gold); font-weight:700; }
.link-gold:hover{ text-decoration:underline; color:#ffea85; }

/* MAIN CONTAINER */
.table-container{
    background: var(--card-bg);
    border:1px solid var(--glass-border);
    padding:32px;
    border-radius:14px;
    max-width:1200px;
    margin:40px auto;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 8px 32px rgba(2,8,23,0.55);
    backdrop-filter: blur(8px) saturate(120%);
}

/* TITLE */
h3{
    font-family:'Merriweather', serif;
    color: var(--gold);
    letter-spacing:0.4px;
}


/* TABLE (JRMSU Navy + Gold Theme) */
table{
    color:white;
}
table thead{
    background: rgba(7,27,42,0.85);
    color: var(--gold);
    font-weight:700;
    border-bottom: 2px solid var(--gold);
}
table tbody tr:hover{
    background: rgba(255,215,0,0.14) !important;
}
table td input{
    background: rgba(255,255,255,0.85);
    border:none;
    font-weight:500;
    border-radius:6px;
}
table td input:disabled{
    background: rgba(255,255,255,0.5);
}
.editable-field:focus{
    border:2px solid var(--gold) !important;
}

/* GROUP HEADER */
.group-header{
    font-size:1.4rem;
    color:var(--gold);
    font-family:'Merriweather', serif;
    text-shadow: 0px 0px 3px rgba(0,0,0,0.8);
    margin-top:40px;
}

/* BUTTONS */
.btn-warning{
    background-color: var(--gold);
    color:#000;
    font-weight:600;
}
.btn-warning:hover{
    background-color:#e4c200;
}
.btn-danger, .btn-success{
    font-weight:600;
}

/* UNASSIGNED BOX */
.unassigned-box{
    font-family:'Merriweather', serif;
    background: rgba(255,255,255,0.06);
    border:1px solid var(--gold);
    border-left:5px solid var(--gold);
    padding:18px;
    border-radius:12px;
    backdrop-filter: blur(6px);
}

/* Fade-in animation */
.fade-in{
    animation: fadeUp .55s ease both;
}
@keyframes fadeUp{
    from{ opacity:0; transform:translateY(10px); }
    to{ opacity:1; transform:translateY(0); }
}

/* LIVE TIME BAR */
#liveDateTimeBar{
    width:100%;
    background:rgba(0,0,0,0.35);
    backdrop-filter:blur(6px);
    padding:10px 0;
    text-align:center;
    color:var(--gold);
    font-weight:700;
    border-bottom:1px solid rgba(255,215,0,0.25);
}

/* Footer */
footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(10px);
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
    border-top: 1px solid rgba(255,255,255,0.3);
}

/* 🔥 GLASS EFFECT FOR TABLE ROWS */
#lessonsTable tbody tr {
    background: rgba(255, 255, 255, 0.05); /* subtle glass */
    backdrop-filter: blur(6px) saturate(140%);
    -webkit-backdrop-filter: blur(6px) saturate(140%);
    border-bottom: 1px solid rgba(255, 230, 0, 0.18);
    transition: 0.25s ease;
}

/* With spacing (floating rows effect) */
#lessonsTable tbody tr td {
    padding-top: 14px !important;
    padding-bottom: 14px !important;
}

/* 🔥 When hovered: stronger gold glow */
#lessonsTable tbody tr:hover {
    background: rgba(255, 215, 0, 0.12) !important;
    border-color: rgba(255, 215, 0, 0.45);
    box-shadow:
        0 0 14px rgba(255, 215, 0, 0.25),
        inset 0 0 8px rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
}

/* Slight spacing between rows */
#lessonsTable tbody tr:not(:last-child) {
    margin-bottom: 6px;
}
/* ⭐ EMPHASIZED GLASS TABLE EDGES */
#lessonsTable {
    border-collapse: separate !important;
    border-spacing: 0 10px; /* floating row spacing */
}

/* Row container effect */
#lessonsTable tbody tr {
    background: rgba(255, 255, 255, 0.09) !important;
    backdrop-filter: blur(12px) saturate(180%);
    -webkit-backdrop-filter: blur(12px) saturate(180%);
    border: 2px solid rgba(255, 215, 0, 0.55) !important; /* ⭐ strong gold edge */
    border-radius: 14px;
    overflow: hidden;
    transition: 0.25s ease-in-out;
    box-shadow:
        0 0 10px rgba(255, 215, 0, 0.25),
        inset 0 0 10px rgba(255, 255, 255, 0.06);
}

/* Rounded edges per cell group */
#lessonsTable tbody tr td {
    padding: 14px 18px !important;
    border: none !important;
}

/* Left and right rounded corners */
#lessonsTable tbody tr td:first-child {
    border-radius: 14px 0 0 14px;
}
#lessonsTable tbody tr td:last-child {
    border-radius: 0 14px 14px 0;
}

/* Hover effect: glowing edges */
#lessonsTable tbody tr:hover {
    border-color: rgba(255, 230, 0, 0.95) !important;
    box-shadow:
        0 0 18px rgba(255, 215, 0, 0.55),
        inset 0 0 12px rgba(255, 255, 255, 0.1);
    transform: translateY(-3px);
    background: rgba(255, 241, 150, 0.15) !important; /* subtle gold tint */
}

/* 🔥 POPUP MESSAGE */
.popup-message{
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); /* center magic */
    
    padding: 16px 28px;
    border-radius: 12px;
    font-weight: 600;
    color: #fff;
    z-index: 99999;
    
    box-shadow: 0 15px 40px rgba(0,0,0,0.6);
    text-align: center;
    min-width: 280px;

    animation: fadeInOut 3s forwards;
}

.popup-message.success{ background: #28a745; }
.popup-message.danger{ background: #dc3545; }
.popup-message.info{ background: #17a2b8; }

@keyframes fadeInOut{
    0% {
        opacity:0;
        transform: translate(-50%, -60%) scale(0.9);
    }
    10% {
        opacity:1;
        transform: translate(-50%, -50%) scale(1);
    }
    80% {
        opacity:1;
    }
    100% {
        opacity:0;
        transform: translate(-50%, -60%) scale(0.9);
    }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">
   <a class="navbar-brand d-flex align-items-center gap-2" href="instructor_dashboard.php">
    <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px; width:auto;">
    <img src="ccs.png" alt="CCS Logo" style="height:36px; width:auto;">
    <span>CSTUTORHUB — INSTRUCTOR</span>
</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_students.php">Students</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_lessons.php">Lessons</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">Pre-test</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_assessment.php">Assessment</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">Student Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">Feedback</a></li>
        <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_dashboard.php" style="font-size:25px;">🏠</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container">
  <div class="container-box">

    <?php if (isset($_GET['msg'])): ?>
    <div id="popupMessage" class="popup-message
        <?php 
            if ($_GET['msg'] === 'created') echo 'success';
            elseif ($_GET['msg'] === 'deleted') echo 'danger';
            elseif ($_GET['msg'] === 'duplicated') echo 'info';
            elseif ($_GET['msg'] === 'updated') echo 'success';
        ?>">
        <?php 
            if ($_GET['msg'] === 'created') echo 'Lessons created successfully.';
            elseif ($_GET['msg'] === 'deleted') echo 'Lesson deleted successfully.';
            elseif ($_GET['msg'] === 'duplicated') echo 'Lesson duplicated successfully.';
            elseif ($_GET['msg'] === 'updated') echo 'Lesson updated successfully.';
        ?>
    </div>
<?php endif; ?>

    <form id="setupForm" method="POST" enctype="multipart/form-data">
      <div class="row g-3">

        <div class="col-md-3">
          <label class="form-label">Year Level</label>
          <select name="year_level" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php 
			$years = array_unique(array_map(fn($x)=>$x['year_level'], $groups));
			sort($years);
			foreach ($years as $y): ?>
				<option><?= htmlspecialchars($y) ?></option>
			<?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Block</label>
          <select name="block" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php
              $blocks = array_unique(array_map(fn($x)=>$x['block'], $groups));
              sort($blocks);
              foreach($blocks as $b) echo "<option>$b</option>";
            ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Category</label>
          <select name="category" id="category" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <option value="Minor">Minor</option>
            <option value="Major">Major</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Existing Course</label>
          <select id="course_id" name="course_id" class="form-select input-light">
            <option value="">-- Choose --</option>
            <?php foreach($courses as $c): ?>
              <option value="<?= $c['course_id']; ?>" data-category="<?= $c['category']; ?>">
                <?= htmlspecialchars($c['course_name']); ?> (<?= $c['category']; ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Or type new course name</label>
          <input type="text" name="course_name" class="form-control input-light">
        </div>

        <div class="col-md-3">
          <label class="form-label">Number of Lessons</label>
          <input type="number" name="num_lessons" id="num_lessons" min="1" value="1" class="form-control input-light">
        </div>

        <div class="col-md-3 align-self-end">
          <button type="button" id="generateBtn" class="btn btn-primary">Generate Lesson Rows</button>
        </div>
      </div>

      <hr>

      <div id="lessonsContainer"></div>

      <div class="mt-3">
        <button type="submit" class="btn btn-success">Save Lessons</button>
      </div>
    </form>

    <hr>

    <h5>Existing Lessons</h5>

		<div class="mb-3">
		  <input type="text" id="lessonSearch" class="form-control input-light" placeholder="Search by Year+Block, Title, or File...">
		</div>

		<div class="table-responsive">
		  <table class="table table-sm table-borderless table-light-custom text-white" id="lessonsTable">
			<thead>
			  <tr>
				<th>Course</th>
				<th>Ylvl</th>
				<th>Block</th>
				<th>Lesson #</th>
				<th>Title</th>
				<th>Lesson File</th>
				<th>Syllabus</th>
				<th>Actions</th>
			  </tr>
			</thead>
			<tbody>
			  <?php foreach($existingLessons as $l): ?>
				<tr>
				  <td><?= htmlspecialchars($l['display_course']); ?></td>
				  <td><?= htmlspecialchars($l['year_level']); ?></td>
				  <td><?= htmlspecialchars($l['block']); ?></td>
				  <td><?= htmlspecialchars($l['lesson_no']); ?></td>
				  <td><?= htmlspecialchars($l['lesson_title']); ?></td>
				  <td>
					<?php if ($l['lesson_file']): ?>
						<a class="text-info" href="<?= htmlspecialchars($l['lesson_file']); ?>" target="_blank"><?= basename($l['lesson_file']); ?></a>
					<?php endif; ?>
				  </td>
				  <td>
					<?php if ($l['syllabus_file']): ?>
						<a class="text-warning" href="<?= htmlspecialchars($l['syllabus_file']); ?>" target="_blank">Syllabus</a>
					<?php else: ?>
						<span class="text-secondary">None</span>
					<?php endif; ?>
				</td>
				  <td>
					<a class="btn btn-sm btn-warning" href="edit_lesson.php?id=<?= $l['lesson_id']; ?>">Edit</a>
					<a class="btn btn-sm btn-danger" href="delete_lesson.php?id=<?= $l['lesson_id']; ?>" onclick="return confirmDelete();">Delete</a>
					<a class="btn btn-sm btn-info" href="duplicate_lesson.php?id=<?= $l['lesson_id']; ?>">Duplicate</a>
				  </td>
				</tr>
			  <?php endforeach; ?>
			</tbody>
		  </table>
		</div>

  </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filter courses by category
document.getElementById('category').addEventListener('change', function () {
    const cat = this.value;
    document.querySelectorAll('#course_id option').forEach(o => {
        if (!o.value) return o.hidden = false;
        o.hidden = (o.dataset.category !== cat);
    });
});

// Create simple lesson row
function createLessonRow(index) {
    const row = document.createElement('div');
    row.className = 'card p-3 mb-2';
    row.innerHTML = `
      <div class="row g-2">

        <div class="col-md-1">
          <label class="form-label">#</label>
          <input type="number" name="lesson_no[]" class="form-control input-light" value="${index+1}" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Lesson Title</label>
          <input type="text" name="lesson_title[]" class="form-control input-light" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Lesson File 
            <small class="text-warning d-block">Upload only PDF files</small>
          </label>
          <input type="file" name="lesson_file_${index}" accept=".pdf,.ppt,.pptx,.doc,.docx" class="form-control input-light">
        </div>

        <!-- ✅ NEW SYLLABUS PDF FIELD -->
        <div class="col-md-4">
          <label class="form-label">Syllabus PDF 
            <small class="text-warning d-block">Upload PDF only</small>
          </label>
          <input type="file" name="syllabus_file_${index}" accept=".pdf" class="form-control input-light">
        </div>
        <!-- END SYLLABUS -->

        <div class="col-md-2 d-flex align-items-end">
          <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
        </div>

      </div>
    `;

    row.querySelector('.remove-row').addEventListener('click', () => row.remove());
    return row;
}

// Generate rows
document.getElementById('generateBtn').addEventListener('click', () => {
    const num = parseInt(document.getElementById('num_lessons').value) || 0;
    const container = document.getElementById('lessonsContainer');
    container.innerHTML = '';
    for (let i = 0; i < num; i++) container.appendChild(createLessonRow(i));
});

const courseSelect = document.getElementById('course_id');
const newCourseInput = document.querySelector('input[name="course_name"]');

courseSelect.addEventListener('change', () => {
    if (courseSelect.value) {
        newCourseInput.value = '';
        newCourseInput.disabled = true;
        newCourseInput.placeholder = 'Disabled – Existing course selected';
    } else {
        newCourseInput.disabled = false;
        newCourseInput.placeholder = '';
    }
});

const lessonSearchInput = document.getElementById('lessonSearch');
const lessonsTable = document.getElementById('lessonsTable').getElementsByTagName('tbody')[0];

lessonSearchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();

    Array.from(lessonsTable.rows).forEach(row => {
        const yearBlock = (row.cells[1].textContent + row.cells[2].textContent).toLowerCase();
        const title = row.cells[4].textContent.toLowerCase();
        const file = row.cells[5].textContent.toLowerCase();

        if (yearBlock.includes(filter) || title.includes(filter) || file.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Auto remove popup after 3 seconds
const popup = document.getElementById('popupMessage');
if (popup) {
    setTimeout(() => {
        popup.remove();
    }, 3000);
}

function confirmDelete() {
    return confirm("Are you sure you want to delete this lesson?");
}
</script>
<script>
// LIVE DATE & TIME
function updateDateTime(){
    document.getElementById("liveDateTimeBar").innerText =
        new Date().toLocaleString("en-PH", { 
            timeZone: "Asia/Manila",
            dateStyle: "full",
            timeStyle: "medium"
        });
}
setInterval(updateDateTime,1000);
updateDateTime();
</script>

</body>
</html>


