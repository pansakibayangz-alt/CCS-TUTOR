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

// Handle pretest creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_level = $_POST['year_level'] ?? null;
    $block = $_POST['block'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $lesson_id = $_POST['lesson_id'] ?? null;
    $pretest_type = $_POST['pretest_type'] ?? null;
    $instructions = $_POST['instructions'] ?? '';

    if ($course_id && $lesson_id && $pretest_type) {
        $ins = $pdo->prepare("INSERT INTO pretests (instructor_id, course_id, lesson_id, pretest_type, instructions, year_level, block) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$instructor_id, $course_id, $lesson_id, $pretest_type, $instructions, $year_level, $block]);
        $pretest_id = $pdo->lastInsertId();

        // Insert pretest items
        $num_items = intval($_POST['num_items'] ?? 0);
        $questions = $_POST['question'] ?? [];
        $answers = $_POST['answer'] ?? [];
        $optionsArr = $_POST['options'] ?? [];

        for ($i = 0; $i < $num_items; $i++) {
            $question = trim($questions[$i] ?? '');
            $answer = trim($answers[$i] ?? '');
            $options = $optionsArr[$i] ?? null;

            if ($question && $answer) {
                $opt_json = null;
                if ($pretest_type === 'MULTIPLE CHOICE' && $options) {
                    $opt_json = json_encode($options, JSON_UNESCAPED_UNICODE);
                }

                $itemStmt = $pdo->prepare("INSERT INTO pretest_items (pretest_id, item_no, question, options, answer)
                                           VALUES (?, ?, ?, ?, ?)");
                $itemStmt->execute([$pretest_id, $i+1, $question, $opt_json, $answer]);
            }
        }
    }

    header('Location: instructor_manage_pretest.php?msg=created');
    exit;
}

// 1️⃣ Fetch only assigned Year-Level & Block
$stmt = $pdo->prepare("
    SELECT DISTINCT year_level, block 
    FROM students 
    WHERE instructor_id = ? 
    ORDER BY year_level ASC, block ASC
");
$stmt->execute([$instructor_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2️⃣ Fetch courses for this instructor only
$cstmt = $pdo->prepare("
    SELECT * 
    FROM courses 
    WHERE instructor_id = ? 
    ORDER BY category, course_name
");
$cstmt->execute([$instructor_id]);
$courses = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// 3️⃣ Fetch lessons for this instructor only
$lstmt = $pdo->prepare("
    SELECT * 
    FROM lessons 
    WHERE instructor_id = ? 
    ORDER BY created_at DESC
");
$lstmt->execute([$instructor_id]);
$lessons = $lstmt->fetchAll(PDO::FETCH_ASSOC);

// 4️⃣ Fetch existing pretests for this instructor only
$ptestStmt = $pdo->prepare("
    SELECT p.*, c.course_name, l.lesson_title 
    FROM pretests p
    LEFT JOIN courses c ON p.course_id = c.course_id
    LEFT JOIN lessons l ON p.lesson_id = l.lesson_id
    WHERE p.instructor_id = ?
    ORDER BY p.created_at DESC
");
$ptestStmt->execute([$instructor_id]);
$existingPretests = $ptestStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instructor - Manage Pretests</title>
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
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
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
#pretestTable tbody tr {
    background: rgba(255, 255, 255, 0.05); /* subtle glass */
    backdrop-filter: blur(6px) saturate(140%);
    -webkit-backdrop-filter: blur(6px) saturate(140%);
    border-bottom: 1px solid rgba(255, 230, 0, 0.18);
    transition: 0.25s ease;
}

/* With spacing (floating rows effect) */
#pretestTable tbody tr td {
    padding-top: 14px !important;
    padding-bottom: 14px !important;
}

/* 🔥 When hovered: stronger gold glow */
#pretestTable tbody tr:hover {
    background: rgba(255, 215, 0, 0.12) !important;
    border-color: rgba(255, 215, 0, 0.45);
    box-shadow:
        0 0 14px rgba(255, 215, 0, 0.25),
        inset 0 0 8px rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
}

/* Slight spacing between rows */
#pretestTable tbody tr:not(:last-child) {
    margin-bottom: 6px;
}
/* ⭐ EMPHASIZED GLASS TABLE EDGES (SAME AS MANAGE LESSONS) */
#pretestsTable {
    border-collapse: separate !important;
    border-spacing: 0 12px;
}

#pretestsTable tbody tr {
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

#pretestsTable tbody tr td:first-child {
    border-radius: 14px 0 0 14px;
}
#pretestsTable tbody tr td:last-child {
    border-radius: 0 14px 14px 0;
}

#pretestsTable tbody tr td {
    padding: 14px 18px !important;
    border: none !important;
}

#pretestsTable tbody tr:hover {
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
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">Lessons</a></li>
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_pretest.php">Pre-test</a></li>
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
        switch($_GET['msg']){
            case 'created': echo 'success'; break;
            case 'deleted': echo 'danger'; break;
            case 'updated': echo 'success'; break;
            case 'duplicated': echo 'info'; break;
        }
    ?>
">
    <?php 
        switch($_GET['msg']){
            case 'created': echo 'Pretest created successfully.'; break;
            case 'deleted': echo 'Pretest deleted successfully.'; break;
            case 'updated': echo 'Pretest updated successfully.'; break;
            case 'duplicated': echo 'Pretest duplicated successfully.'; break;
        }
    ?>
</div>
<?php endif; ?>

    <form id="pretestForm" method="POST">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Year Level</label>
          <?php
			$year_levels = array_unique(array_map(fn($x) => $x['year_level'], $groups));
			sort($year_levels); // optional, to order 1,2,3,4
			?>
			<select name="year_level" class="form-select input-light" required>
				<option value="">-- Select --</option>
				<?php foreach($year_levels as $y): ?>
					<option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
				<?php endforeach; ?>
			</select>
        </div>
        <div class="col-md-2">
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
        <div class="col-md-4">
          <label class="form-label">Course</label>
          <select name="course_id" id="courseSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($courses as $c): ?>
              <option value="<?= $c['course_id']; ?>"><?= htmlspecialchars($c['course_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Lesson</label>
          <select name="lesson_id" id="lessonSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($lessons as $l): ?>
              <option value="<?= $l['lesson_id']; ?>"><?= htmlspecialchars($l['lesson_title']); ?> (<?= htmlspecialchars($l['year_level']).'-'.htmlspecialchars($l['block']); ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Pretest Type</label>
          <select name="pretest_type" id="pretestType" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <option value="MULTIPLE CHOICE">MULTIPLE CHOICE</option>
            <option value="ENUMERATION">ENUMERATION</option>
            <option value="FILL IN THE BLANK">FILL IN THE BLANK</option>
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
        <button type="submit" class="btn btn-success">Save Pretest</button>
      </div>
    </form>

    <hr>
	<h5>Existing Pretests</h5>

	<div class="mb-3">
	  <input type="text" id="pretestSearch" class="form-control input-light" placeholder="Search by Course, Lesson, Year/Block, or Type...">
	</div>

	<div class="table-responsive">
	  <table class="table table-sm table-borderless table-light-custom text-white" id="pretestsTable">
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
		<?php foreach($existingPretests as $p): ?>
		  <tr>
			<td><?= htmlspecialchars($p['course_name']); ?></td>
			<td><?= htmlspecialchars($p['lesson_title']); ?></td>
			<td><?= htmlspecialchars($p['year_level']); ?></td>
			<td><?= htmlspecialchars($p['block']); ?></td>
			<td><?= htmlspecialchars($p['pretest_type']); ?></td>
			<td><?= htmlspecialchars($p['instructions']); ?></td>
			<td>
			  <a class="btn btn-sm btn-warning" href="edit_pretest.php?id=<?= $p['pretest_id']; ?>">Edit</a>
			  <a class="btn btn-sm btn-danger" href="delete_pretest.php?id=<?= $p['pretest_id']; ?>" onclick="return confirm('Delete this pretest?');">Delete</a>
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
// Generate pretest items
function createItemRow(index, type) {
    const row = document.createElement('div');
    row.className = 'card p-3 mb-2';
    let optionsHtml = '';
    if(type === 'MULTIPLE CHOICE') {
        optionsHtml = ['A','B','C','D'].map(l=>`
            <input type="text" name="options[${index}][${l}]" placeholder="Option ${l}" class="form-control input-light mb-1">
        `).join('');
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
          <label>Answer</label>
          <input type="text" name="answer[]" class="form-control input-light" required>
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
    const type = document.getElementById('pretestType').value;
    if(!type) return alert('Select Pretest Type first');
    const container = document.getElementById('itemsContainer');
    container.innerHTML = '';
    for(let i=0;i<num;i++) container.appendChild(createItemRow(i, type));
});

// Filter existing pretests
const pretestSearchInput = document.getElementById('pretestSearch');
const pretestsTable = document.getElementById('pretestsTable').getElementsByTagName('tbody')[0];

pretestSearchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    Array.from(pretestsTable.rows).forEach(row => {
        const cellsText = Array.from(row.cells).slice(0,6).map(td => td.textContent.toLowerCase()).join(' ');
        row.style.display = cellsText.includes(filter) ? '' : 'none';
    });
});

// Cascading dropdowns: Year/Block → Courses → Lessons
const courseSelect = document.getElementById('courseSelect');
const lessonSelect = document.getElementById('lessonSelect');
const yearSelect = document.querySelector('select[name="year_level"]');
const blockSelect = document.querySelector('select[name="block"]');

function loadCoursesAndLessons() {
    const year = yearSelect.value;
    const block = blockSelect.value;

    if (!year || !block) {
        courseSelect.innerHTML = '<option value="">-- Choose --</option>';
        lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
        return;
    }

    // Fetch courses for selected Year-Level & Block
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
            lessonSelect.innerHTML = '<option value="">-- Choose --</option>'; // reset lessons
        });
}

// Load lessons for selected course
courseSelect.addEventListener('change', function() {
    const courseId = courseSelect.value;
    const year = yearSelect.value;
    const block = blockSelect.value;

    lessonSelect.innerHTML = '<option value="">-- Loading lessons --</option>';
    if (!courseId) {
        lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
        return;
    }

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

// Trigger course reload when Year-Level or Block changes
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
