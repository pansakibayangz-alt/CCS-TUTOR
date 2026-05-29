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
    SELECT DISTINCT year_level, block 
    FROM students 
    WHERE instructor_id = ?
    ORDER BY year_level ASC, block ASC
");
$stmt->execute([$instructor_id]);
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
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#0047AB,#1E90FF); color:#fff; min-height:100vh; margin:0;}
.navbar-custom { background: linear-gradient(135deg,#002F6C,#0047AB); }
.navbar-custom .nav-link,.navbar-custom .navbar-brand { color:#fff; font-weight:600; }
.navbar-custom .nav-link.active { color:#FFD700; }
.container-box { background: rgba(255,255,255,0.15); padding:24px; border-radius:18px; margin-top:30px; backdrop-filter:blur(10px); }
.input-light { background: rgba(255,255,255,0.85); color:#000; }
.table-light-custom { background: rgba(255,255,255,0.06); }
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
    <h3>Manage Assessments</h3>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success">Assessment created successfully.</div>
    <?php endif; ?>

    <form id="assessmentForm" method="POST">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Year Level</label>
          <?php
            $year_levels = array_unique(array_map(fn($x) => $x['year_level'], $groups));
            sort($year_levels);
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
          <?php
            $blocks = array_unique(array_map(fn($x)=>$x['block'], $groups));
            sort($blocks);
          ?>
          <select name="block" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach($blocks as $b) echo "<option>$b</option>"; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Course</label>
          <select name="course_id" id="courseSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($courses as $c): ?>
              <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Lesson</label>
          <select name="lesson_id" id="lessonSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($lessons as $l): ?>
              <option value="<?= $l['lesson_id']; ?>"><?= htmlspecialchars($l['lesson_title']); ?> (<?= htmlspecialchars($l['year_level'].'-'.$l['block']); ?>)</option>
            <?php endforeach; ?>
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

<footer>
    Developed by: <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
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

// Cascading dropdowns
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
            lessonSelect.innerHTML = '<option value="">-- Choose --</option>';
        });
}

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

yearSelect.addEventListener('change', loadCoursesAndLessons);
blockSelect.addEventListener('change', loadCoursesAndLessons);

</script>

</body>
</html>

