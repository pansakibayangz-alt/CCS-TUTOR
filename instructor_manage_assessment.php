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

// Handle assessment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_level = $_POST['year_level'] ?? null;
    $block = $_POST['block'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $lesson_id = $_POST['lesson_id'] ?? null;
    $assessment_type = $_POST['assessment_type'] ?? null;
    $instructions = $_POST['instructions'] ?? '';

    if ($course_id && $lesson_id && $assessment_type) {
        $ins = $pdo->prepare("
            INSERT INTO assessments (instructor_id, course_id, lesson_id, assessment_type, instructions, year_level, block)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$instructor_id, $course_id, $lesson_id, $assessment_type, $instructions, $year_level, $block]);
        $assessment_id = $pdo->lastInsertId();

        $num_items = intval($_POST['num_items'] ?? 0);
        $questions = $_POST['question'] ?? [];
        $answers = $_POST['answer'] ?? [];
        $optionsArr = $_POST['options'] ?? [];

        for ($i = 0; $i < $num_items; $i++) {
            $question = trim($questions[$i] ?? '');
            $answer = trim($answers[$i] ?? '');
            $options = $optionsArr[$i] ?? null;
            $opt_json = null;

            if ($assessment_type === 'MULTIPLE CHOICE' && $options) {
                $opt_json = json_encode($options, JSON_UNESCAPED_UNICODE);
            }
            if ($assessment_type === 'TRUE OR FALSE') {
                $opt_json = json_encode(["TRUE"=>"TRUE","FALSE"=>"FALSE"]);
            }

            if ($question && $answer) {
                $itemStmt = $pdo->prepare("
                    INSERT INTO assessment_items (assessment_id, item_no, question, options, answer)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $itemStmt->execute([$assessment_id, $i+1, $question, $opt_json, $answer]);
            }
        }
    }

    header("Location: instructor_manage_assessment.php?msg=created");
    exit;
}

// Fetch dropdown data
$stmt = $pdo->prepare("
    SELECT DISTINCT s.year_level, s.block
    FROM students s
    INNER JOIN lessons l 
        ON s.year_level = l.year_level 
        AND s.block = l.block
    WHERE s.instructor_id = ?
      AND l.instructor_id = ?
    ORDER BY s.year_level ASC, s.block ASC
");
$stmt->execute([$instructor_id, $instructor_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cstmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY category, course_name");
$cstmt->execute([$instructor_id]);
$courses = $cstmt->fetchAll(PDO::FETCH_ASSOC);

$lstmt = $pdo->prepare("SELECT * FROM lessons WHERE instructor_id = ? ORDER BY created_at DESC");
$lstmt->execute([$instructor_id]);
$lessons = $lstmt->fetchAll(PDO::FETCH_ASSOC);

$astmt = $pdo->prepare("
    SELECT a.*, c.course_name, l.lesson_title
    FROM assessments a
    LEFT JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN lessons l ON a.lesson_id = l.lesson_id
    WHERE a.instructor_id = ?
    ORDER BY a.created_at DESC
");
$astmt->execute([$instructor_id]);
$existingAssessments = $astmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instructor - Manage Assessments</title>
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
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
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
#assessmentsTable tbody tr {
    background: rgba(255, 255, 255, 0.05); /* subtle glass */
    backdrop-filter: blur(6px) saturate(140%);
    -webkit-backdrop-filter: blur(6px) saturate(140%);
    border-bottom: 1px solid rgba(255, 230, 0, 0.18);
    transition: 0.25s ease;
}

/* With spacing (floating rows effect) */
#assessmentsTable tbody tr td {
    padding-top: 14px !important;
    padding-bottom: 14px !important;
}

/* 🔥 When hovered: stronger gold glow */
#assessmentsTable tbody tr:hover {
    background: rgba(255, 215, 0, 0.12) !important;
    border-color: rgba(255, 215, 0, 0.45);
    box-shadow:
        0 0 14px rgba(255, 215, 0, 0.25),
        inset 0 0 8px rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
}

/* Slight spacing between rows */
#assessmentsTable tbody tr:not(:last-child) {
    margin-bottom: 6px;
}
/* ⭐ EMPHASIZED GLASS TABLE EDGES */
#assessmentsTable {
    border-collapse: separate !important;
    border-spacing: 0 12px;
}

#assessmentsTable tbody tr {
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

#assessmentsTable tbody tr td:first-child {
    border-radius: 14px 0 0 14px;
}
#assessmentsTable tbody tr td:last-child {
    border-radius: 0 14px 14px 0;
}

#assessmentsTable tbody tr td {
    padding: 14px 18px !important;
    border: none !important;
}

#assessmentsTable tbody tr:hover {
    border-color: rgba(255, 230, 0, 0.95) !important;
    box-shadow:
        0 0 18px rgba(255, 215, 0, 0.55),
        inset 0 0 12px rgba(255, 255, 255, 0.1);
    transform: translateY(-3px);
    background: rgba(255, 241, 150, 0.15) !important;
}

/* 🔥 POPUP MESSAGE */
.popup-message{
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); 
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
    0% { opacity:0; transform: translate(-50%, -60%) scale(0.9); }
    10% { opacity:1; transform: translate(-50%, -50%) scale(1); }
    80% { opacity:1; }
    100% { opacity:0; transform: translate(-50%, -60%) scale(0.9); }
}
</style>
</head>
<body>

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
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">Lessons</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">Pre-test</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_assessment.php">Assessment</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">Student Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_send_feedback.php">Feedback</a></li>
        <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_dashboard.php" style="font-size:25px;">🏠</a></li>
      </ul>
    </div>
  </div>
</nav>

<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container">
  <div class="container-box">
    <?php if (isset($_GET['msg'])): ?>
<div id="popupMessage" class="popup-message
    <?php 
        switch($_GET['msg']){
            case 'created': 
            case 'updated':
            case 'certificate_saved':
                echo 'success'; 
                break;
            case 'deleted': 
                echo 'danger'; 
                break;
        }
    ?>
">
    <?php 
        switch($_GET['msg']){
            case 'created': echo 'Assessment created successfully.'; break;
            case 'deleted': echo 'Assessment deleted successfully.'; break;
            case 'updated': echo 'Assessment updated successfully.'; break;
            case 'certificate_saved': echo 'Certificate template saved successfully.'; break;
        }
    ?>
</div>
<?php endif; ?>
	
    <form id="assessmentForm" method="POST">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Year Level</label>
          <?php
            $year_levels = array_unique(array_map(fn($x) => $x['year_level'], $groups));
            sort($year_levels);
          ?>
          <select name="year_level" id="yearSelect" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach($year_levels as $y): ?>
              <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Block</label>
          <?php
            $blocks = array_unique(array_map(fn($x)=>$x['block'], $groups));
            sort($blocks);
          ?>
          <select name="block" id="blockSelect" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach($blocks as $b) echo "<option>$b</option>"; ?>
          </select>
        </div>
        
        <div class="col-md-4" id="courseContainer" style="display: none;">
          <label class="form-label">Course</label>
          <select name="course_id" id="courseSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
          </select>
        </div>
        
        <div class="col-md-4" id="lessonContainer" style="display: none;">
          <label class="form-label">Lesson</label>
          <select name="lesson_id" id="lessonSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Assessment Type</label>
          <select name="assessment_type" id="assessmentType" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <option value="MULTIPLE CHOICE">MULTIPLE CHOICE</option>
            <option value="ENUMERATION">ENUMERATION</option>
            <option value="FILL IN THE BLANK">FILL IN THE BLANK</option>
            <option value="TRUE OR FALSE">TRUE OR FALSE</option>
          </select>
        </div>

        <div class="col-md-9">
          <label class="form-label">Instructions</label>
          <input type="text" name="instructions" class="form-control input-light">
        </div>

        <div class="col-md-3">
          <label class="form-label">Number of Items</label>
          <input type="number" name="num_items" id="numItems" min="1" value="1" class="form-control input-light">
        </div>
        <div class="col-md-3 align-self-end">
          <button type="button" id="generateItems" class="btn btn-primary">Generate Items</button>
        </div>
      </div>

      <hr>
      <div id="itemsContainer"></div>

      <div class="mt-3">
        <button type="submit" class="btn btn-success">Save Assessment</button>
      </div>
    </form>

    <hr>
    <h5>Existing Assessments</h5>
    <div class="mb-3">
      <input type="text" id="assessmentSearch" class="form-control input-light" placeholder="Search assessments...">
    </div>

    <div class="table-responsive">
		  <table class="table table-sm table-borderless table-light-custom text-white" id="assessmentsTable">
			<thead>
			  <tr>
				<th>Course</th>
				<th>Lesson</th>
				<th>Year-Level</th>
				<th>Block</th>
				<th>Type</th>
				<th>Instructions</th>
				<th>Actions</th>
			  </tr>
			</thead>
			<tbody>
			<?php foreach($existingAssessments as $a): ?>
			  <tr>
				<td><?= htmlspecialchars($a['course_name']); ?></td>
				<td><?= htmlspecialchars($a['lesson_title']); ?></td>
				<td><?= htmlspecialchars($a['year_level']); ?></td>
				<td><?= htmlspecialchars($a['block']); ?></td>
				<td><?= htmlspecialchars($a['assessment_type']); ?></td>
				<td><?= htmlspecialchars($a['instructions']); ?></td>
				<td>
				  <a class="btn btn-warning btn-sm" href="edit_assessment.php?id=<?= $a['assessment_id']; ?>">Edit</a>
				  <a class="btn btn-danger btn-sm" href="delete_assessment.php?id=<?= $a['assessment_id']; ?>" onclick="return confirm('Delete this assessment?');">Delete</a>
				</td>
			  </tr>
			<?php endforeach; ?>
			</tbody>
		  </table>
		</div>
  </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Generate assessment items
function createItemRow(index, type) {
    const row = document.createElement('div');
    row.className = 'card p-3 mb-2';
    let optionsHtml = '';
    if(type === 'MULTIPLE CHOICE') {
        optionsHtml = ['A','B','C','D'].map(l=>`
            <input type="text" name="options[${index}][${l}]" placeholder="Option ${l}" class="form-control input-light mb-1">
        `).join('');
    }
    if(type === 'TRUE OR FALSE') {
        optionsHtml = `<select name="answer[]" class="form-select input-light mb-2">
            <option value="TRUE">TRUE</option>
            <option value="FALSE">FALSE</option>
        </select>`;
    }

    row.innerHTML = `
      <div class="row g-2">
        <div class="col-md-1">
          <label>#</label>
          <input type="number" name="item_no[]" class="form-control input-light" value="${index+1}" required>
        </div>
        <div class="col-md-7">
          <label>Question</label>
          <input type="text" name="question[]" class="form-control input-light" required>
        </div>
        <div class="col-md-3">
          ${type !== 'TRUE OR FALSE' ? `<label>Answer</label><input type="text" name="answer[]" class="form-control input-light" required>` : ''}
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
        </div>
        <div class="col-12">${optionsHtml}</div>
      </div>
    `;
    row.querySelector('.remove-item').addEventListener('click', ()=> row.remove());
    return row;
}

document.getElementById('generateItems').addEventListener('click', ()=> {
    const num = parseInt(document.getElementById('numItems').value) || 0;
    const type = document.getElementById('assessmentType').value;
    if(!type) return alert('Select Assessment Type first');
    const container = document.getElementById('itemsContainer');
    container.innerHTML = '';
    for(let i=0;i<num;i++) container.appendChild(createItemRow(i, type));
});

// Filter existing assessments
const assessmentSearchInput = document.getElementById('assessmentSearch');
const assessmentsTable = document.getElementById('assessmentsTable').getElementsByTagName('tbody')[0];
assessmentSearchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    Array.from(assessmentsTable.rows).forEach(row => {
        const cellsText = Array.from(row.cells).slice(0,6).map(td => td.textContent.toLowerCase()).join(' ');
        row.style.display = cellsText.includes(filter) ? '' : 'none';
    });
});

// Cascading dropdowns & Dynamic Visibility
const courseContainer = document.getElementById('courseContainer');
const lessonContainer = document.getElementById('lessonContainer');
const courseSelect = document.getElementById('courseSelect');
const lessonSelect = document.getElementById('lessonSelect');
const yearSelect = document.getElementById('yearSelect');
const blockSelect = document.getElementById('blockSelect');

function loadCoursesAndLessons() {
    const year = yearSelect.value;
    const block = blockSelect.value;
    
    if (!year || !block) {
        courseContainer.style.display = 'none';
        lessonContainer.style.display = 'none';
        courseSelect.innerHTML = '<option value="">-- Choose --</option>';
        lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
        return;
    }

    fetch(`get_courses.php?year_level=${year}&block=${block}`)
        .then(res => res.json())
        .then(data => {
            courseSelect.innerHTML = '<option value="">-- Choose --</option>';
            data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.course_id;
                opt.textContent = c.course_name;
                courseSelect.appendChild(opt);
            });
            // Show course container once year and block are selected
            courseContainer.style.display = 'block';
            lessonContainer.style.display = 'none'; // Keep lesson hidden until course is selected
            lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
        });
}

courseSelect.addEventListener('change', function() {
    const courseId = courseSelect.value;
    const year = yearSelect.value;
    const block = blockSelect.value;

    if (!courseId) {
        lessonContainer.style.display = 'none';
        lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
        return;
    }

    lessonSelect.innerHTML = '<option value="">-- Loading lessons --</option>';
    // Show lesson container once course is selected
    lessonContainer.style.display = 'block'; 

    fetch(`get_lessons.php?course_id=${courseId}&year_level=${year}&block=${block}`)
        .then(res => res.json())
        .then(data => {
            lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
            data.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l.lesson_id;
                opt.textContent = `${l.lesson_title} (${l.year_level}-${l.block})`;
                lessonSelect.appendChild(opt);
            });
        });
});

yearSelect.addEventListener('change', loadCoursesAndLessons);
blockSelect.addEventListener('change', loadCoursesAndLessons);

</script>

<script>
// Auto remove popup after 3 seconds
const popup = document.getElementById('popupMessage');
if (popup) {
    setTimeout(() => {
        popup.remove();
    }, 3000);
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
