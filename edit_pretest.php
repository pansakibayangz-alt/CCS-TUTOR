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

$pretest_id = intval($_GET['id'] ?? 0);
if (!$pretest_id) die('Invalid Pretest ID');

// Fetch pretest
$ptestStmt = $pdo->prepare("SELECT * FROM pretests WHERE pretest_id = ? AND instructor_id = ?");
$ptestStmt->execute([$pretest_id, $instructor_id]);
$pretest = $ptestStmt->fetch(PDO::FETCH_ASSOC);
if (!$pretest) die('Pretest not found');

// Fetch pretest items
$itemsStmt = $pdo->prepare("SELECT * FROM pretest_items WHERE pretest_id = ? ORDER BY item_no ASC");
$itemsStmt->execute([$pretest_id]);
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

// Group lessons by year -> block -> course
$lessonsByYB = [];
foreach ($lessons as $l) {
    $yr = $l['year_level'];
    $blk = $l['block'];
    $cid = $l['course_id'];
    if (!isset($lessonsByYB[$yr])) $lessonsByYB[$yr] = [];
    if (!isset($lessonsByYB[$yr][$blk])) $lessonsByYB[$yr][$blk] = [];
    if (!isset($lessonsByYB[$yr][$blk][$cid])) $lessonsByYB[$yr][$blk][$cid] = [];
    $lessonsByYB[$yr][$blk][$cid][] = [
        'lesson_id' => $l['lesson_id'],
        'lesson_title' => $l['lesson_title']
    ];
}

/* ===========================================================
   DUPLICATE PRETEST HANDLER (DON'T MOVE THIS)
   =========================================================== */
if (isset($_POST['duplicate_pretest'])) {

    $new_block = $_POST['new_block'] ?? '';

    if (!$new_block) die("No block selected");

    // Duplicate pretest
    $dup = $pdo->prepare("
        INSERT INTO pretests (instructor_id, course_id, lesson_id, pretest_type, instructions, year_level, block)
        SELECT instructor_id, course_id, lesson_id, pretest_type, instructions, year_level, ?
        FROM pretests WHERE pretest_id = ?
    ");
    $dup->execute([$new_block, $pretest_id]);

    $new_pretest_id = $pdo->lastInsertId();

    // Duplicate items
    $itemDup = $pdo->prepare("
        INSERT INTO pretest_items (pretest_id, item_no, question, options, answer)
        SELECT ?, item_no, question, options, answer
        FROM pretest_items WHERE pretest_id = ?
    ");
    $itemDup->execute([$new_pretest_id, $pretest_id]);

    header("Location: instructor_manage_pretest.php?msg=duplicated");
    exit;
}

// Handle POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_level = $_POST['year_level'] ?? null;
    $block = $_POST['block'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $lesson_id = $_POST['lesson_id'] ?? null;
    $pretest_type = $_POST['pretest_type'] ?? null;
    $instructions = $_POST['instructions'] ?? '';

    if ($course_id && $lesson_id && $pretest_type) {
        // Update pretest info
        $update = $pdo->prepare("UPDATE pretests SET course_id=?, lesson_id=?, pretest_type=?, instructions=?, year_level=?, block=? WHERE pretest_id=?");
        $update->execute([$course_id, $lesson_id, $pretest_type, $instructions, $year_level, $block, $pretest_id]);

        // Delete all existing items
        $del = $pdo->prepare("DELETE FROM pretest_items WHERE pretest_id=?");
        $del->execute([$pretest_id]);

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
                if ($pretest_type === 'MULTIPLE CHOICE' && $options) {
                    $opt_json = json_encode($options, JSON_UNESCAPED_UNICODE);
                }

                $itemStmt = $pdo->prepare("INSERT INTO pretest_items (pretest_id, item_no, question, options, answer)
                                           VALUES (?, ?, ?, ?, ?)");
                $itemStmt->execute([$pretest_id, $i + 1, $question, $opt_json, $answer]);
            }
        }
    }

    header("Location: instructor_manage_pretest.php?msg=updated");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Pretest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#0047AB,#1E90FF); color:#fff; }
.navbar-custom { background: linear-gradient(135deg,#002F6C,#0047AB); }
.navbar-custom .nav-link,.navbar-custom .navbar-brand { color:#fff; font-weight:600; }
.navbar-custom .nav-link.active { color:#FFD700; }
.container-box { background: rgba(255,255,255,0.15); padding:24px; border-radius:18px; margin-top:30px; margin-bottom:60px; backdrop-filter:blur(10px); }
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
        <li class="nav-item"><a class="nav-link active" href="instructor_manage_pretest.php" style="font-weight:700; color:#FFD700;">PRE-TEST</a></li>
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
    <h3>Edit Pretest</h3>

    <form id="pretestForm" method="POST">
      <div class="row g-3">
        <div class="col-md-2">
          <label>Year Level</label>
          <select name="year_level" id="yearLevel" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach ($year_levels as $y): ?>
                <option value="<?= $y ?>" <?= $pretest['year_level']==$y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label>Block</label>
          <select name="block" id="blockSelect" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php
            $selected_year = $pretest['year_level'];
            if (isset($blocks_by_year[$selected_year])) {
                foreach ($blocks_by_year[$selected_year] as $b) {
                    echo '<option value="'.$b.'" '.($pretest['block']==$b?'selected':'').'>'.$b.'</option>';
                }
            }
            ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Course</label>
          <select name="course_id" id="courseSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($courses as $c): ?>
              <option value="<?= $c['course_id'] ?>" <?= $pretest['course_id']==$c['course_id']?'selected':'' ?>>
                <?= htmlspecialchars($c['course_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Lesson</label>
          <select name="lesson_id" id="lessonSelect" class="form-select input-light" required>
            <option value="">-- Choose --</option>
            <?php foreach($lessons as $l): ?>
              <option value="<?= $l['lesson_id'] ?>" data-year="<?= $l['year_level'] ?>" data-block="<?= $l['block'] ?>" data-course="<?= $l['course_id'] ?>" 
  <?= $pretest['lesson_id']==$l['lesson_id']?'selected':'' ?>>
  <?= htmlspecialchars($l['lesson_title']) ?>
</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label>Pretest Type</label>
          <select name="pretest_type" id="pretestType" class="form-select input-light" required>
            <option value="">-- Select --</option>
            <?php foreach(['MULTIPLE CHOICE','ENUMERATION','FILL IN THE BLANK'] as $t): ?>
              <option value="<?= $t ?>" <?= $pretest['pretest_type']==$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-9">
          <label>Instructions</label>
          <input type="text" name="instructions" class="form-control input-light" value="<?= htmlspecialchars($pretest['instructions']) ?>">
        </div>

        <div class="col-md-3">
          <label>Number of Items</label>
          <input type="number" name="num_items" id="numItems" min="1" value="<?= count($items) ?>" class="form-control input-light">
        </div>
        <div class="col-md-3 align-self-end">
          <button type="button" id="generateItems" class="btn btn-primary">Generate Items</button>
        </div>
      </div>

      <hr>
      <div id="itemsContainer">
        <?php foreach($items as $i => $it): 
            $opt = $it['options'] ? json_decode($it['options'], true) : [];
        ?>
        <div class="card p-3 mb-2">
          <div class="row g-2">
            <div class="col-md-1">
              <label>#</label>
              <input type="number" name="item_no[]" class="form-control input-light" value="<?= $i+1 ?>" required>
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
            <?php if($pretest['pretest_type']=='MULTIPLE CHOICE'): ?>
            <div class="col-12">
              <?php foreach(['A','B','C','D'] as $l): ?>
                <input type="text" name="options[<?= $i ?>][<?= $l ?>]" placeholder="Option <?= $l ?>" value="<?= htmlspecialchars($opt[$l]??'') ?>" class="form-control input-light mb-1">
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-3 d-flex justify-content-between align-items-center">
    
    <!-- LEFT SIDE -->
    <button type="button" class="btn btn-warning" id="duplicateBtn">
        Duplicate Pretest
    </button>

    <!-- RIGHT SIDE -->
    <div>
        <button type="submit" class="btn btn-success">Update Pretest</button>
        <a href="instructor_manage_pretest.php" class="btn btn-secondary ms-2">Back</a>
    </div>

</div>
    </form>
  </div>
</div>

<!-- DUPLICATE PRETEST MODAL -->
<div class="modal fade" id="duplicateModal">
  <div class="modal-dialog">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title">Duplicate Pretest</h5>
      </div>
      <div class="modal-body">
        <label>Select Block:</label>
        <select class="form-select" id="duplicateBlock">
          <option value="">-- Choose Block --</option>
          <?php
$selected_year = $pretest['year_level'];
$original_block = $pretest['block'];
if(isset($blocks_by_year[$selected_year])){
    foreach($blocks_by_year[$selected_year] as $b){
        if($b === $original_block) continue; // skip original block
        // Only show blocks that have lessons for this instructor
        if(isset($lessonsByYB[$selected_year][$b]) && count($lessonsByYB[$selected_year][$b]) > 0){
            echo "<option value='$b'>$b</option>";
        }
    }
}
?>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="confirmDuplicate" class="btn btn-primary">Duplicate</button>
      </div>
    </div>
  </div>
</div>

<!-- SUCCESS POPUP -->
<div id="successPopup" style="
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 128, 0, 0.9);
    color: #fff;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    display: none;
    z-index: 99999;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
">Item removed successfully!</div> 

<!-- FOOTER -->
<footer>
    Developed by: <strong>Riza Group</strong> for Thesis S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const yearLevel = document.getElementById('yearLevel');
const blockSelect = document.getElementById('blockSelect');
const courseSelect = document.getElementById('courseSelect');
const lessonSelect = document.getElementById('lessonSelect');

// PHP arrays to JS
const blocksByYear = <?= json_encode($blocks_by_year) ?>;
const lessonsByYBJS = <?= json_encode($lessonsByYB) ?>;
const courseNames = <?= json_encode(array_column($courses, 'course_name', 'course_id')) ?>;

// ✅ FLAG for initial load
let isInitialLoad = true;

// ================= COURSE =================
function updateCourseOptions() {
    const year = yearLevel.value;
    const block = blockSelect.value;

    // 👉 KEEP original selected values on first load
    if (isInitialLoad) {
        return;
    }

    courseSelect.innerHTML = '<option value="">-- Choose --</option>';
    lessonSelect.innerHTML = '<option value="">-- Choose --</option>';

    if (lessonsByYBJS[year] && lessonsByYBJS[year][block]) {
        Object.keys(lessonsByYBJS[year][block]).forEach(cid => {
            const opt = document.createElement('option');
            opt.value = cid;
            opt.textContent = courseNames[cid] || 'Course ' + cid;
            courseSelect.appendChild(opt);
        });
    }
}

// ================= LESSON =================
function updateLessonOptions() {
    const year = yearLevel.value;
    const block = blockSelect.value;
    const course = courseSelect.value;

    // 👉 DO NOTHING on first load
    if (isInitialLoad) return;

    lessonSelect.innerHTML = '<option value="">-- Choose --</option>';

    if (lessonsByYBJS[year] && lessonsByYBJS[year][block] && lessonsByYBJS[year][block][course]) {
        lessonsByYBJS[year][block][course].forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.lesson_id;
            opt.textContent = l.lesson_title;
            lessonSelect.appendChild(opt);
        });
    }
}

// ================= EVENTS =================

// Year change
yearLevel.addEventListener('change', () => {
    const year = yearLevel.value;

    blockSelect.innerHTML = '<option value="">-- Select --</option>';
    if (blocksByYear[year]) {
        blocksByYear[year].forEach(b => {
            const opt = document.createElement('option');
            opt.value = b;
            opt.textContent = b;
            blockSelect.appendChild(opt);
        });
    }

    isInitialLoad = false;
    updateCourseOptions();
});

// Block change
blockSelect.addEventListener('change', () => {
    isInitialLoad = false;
    updateCourseOptions();
});

// Course change
courseSelect.addEventListener('change', () => {
    isInitialLoad = false;
    updateLessonOptions();
});

// Optional: when user clicks dropdown first time
courseSelect.addEventListener('focus', () => {
    if (isInitialLoad) {
        isInitialLoad = false;
        updateCourseOptions();
    }
});

lessonSelect.addEventListener('focus', () => {
    if (isInitialLoad) {
        isInitialLoad = false;
        updateLessonOptions();
    }
});

// ================= INITIAL LOAD =================
updateCourseOptions();
updateLessonOptions();


// ================= Pretest Items JS =================
function createItemRow(index, type, question='', answer='', options={}) {
    const row = document.createElement('div');
    row.className = 'card p-3 mb-2';

    let optionsHtml = '';
    if(type==='MULTIPLE CHOICE') {
        optionsHtml = ['A','B','C','D'].map(l=>`
            <input type="text" name="options[${index}][${l}]" placeholder="Option ${l}" value="${options[l]??''}" class="form-control input-light mb-1">
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
          <input type="text" name="question[]" class="form-control input-light" value="${question}" required>
        </div>
        <div class="col-md-3">
          <label>Answer</label>
          <input type="text" name="answer[]" class="form-control input-light" value="${answer}" required>
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
        </div>
        <div class="col-12">${optionsHtml}</div>
      </div>
    `;

    row.querySelector('.remove-item').addEventListener('click', ()=> {

    if (confirm("Are you sure you want to remove this item?")) {
        row.remove();
        updateItemNumbers();
    }

});

    return row;
}

document.getElementById('generateItems').addEventListener('click', () => {
    const num = parseInt(document.getElementById('numItems').value) || 0;
    const type = document.getElementById('pretestType').value;

    if (!type) return alert('Select Pretest Type first');

    const container = document.getElementById('itemsContainer');
    const existingCount = container.querySelectorAll('.card').length;
    const toAdd = num - existingCount;

    if (toAdd <= 0) return;

    for (let i = 0; i < toAdd; i++) {
        container.appendChild(createItemRow(existingCount + i, type));
    }

    updateItemNumbers();
});

function updateItemNumbers() {
    const rows = document.querySelectorAll('#itemsContainer .card');

    rows.forEach((row, i) => {
        const item_no = i + 1;
        row.querySelector('input[name="item_no[]"]').value = item_no;

        row.querySelectorAll('input[name^="options"]').forEach(opt => {
            const letter = opt.getAttribute('name').match(/\[([A-D])\]/)[1];
            opt.setAttribute('name', `options[${item_no}][${letter}]`);
        });
    });
}

function showSuccessPopup(msg) {
    const popup = document.getElementById('successPopup');
    popup.textContent = msg;
    popup.style.display = 'block';

    // Trigger fade in
    setTimeout(() => {
        popup.style.opacity = '1';
    }, 10);

    // Fade out after 2 seconds
    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 500); // wait for fade-out transition to finish
    }, 2000);
}

// REMOVE ITEM HANDLER
document.querySelectorAll('#itemsContainer .remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm("Are you sure you want to remove this item?")) {
            this.closest('.card').remove();
            updateItemNumbers();
            showSuccessPopup("Item removed successfully!");
        }
    });
});


// ================= DUPLICATE PRETEST =================

// Open modal
document.getElementById("duplicateBtn").addEventListener("click", function(){
    let modal = new bootstrap.Modal(document.getElementById("duplicateModal"));
    modal.show();
});

// Submit duplicate
document.getElementById("confirmDuplicate").addEventListener("click", function(){

    const blk = document.getElementById("duplicateBlock").value;
    if(!blk) return alert("Please select a Block.");

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "";

    let inp1 = document.createElement("input");
    inp1.type = "hidden";
    inp1.name = "duplicate_pretest";
    inp1.value = "1";

    let inp2 = document.createElement("input");
    inp2.type = "hidden";
    inp2.name = "new_block";
    inp2.value = blk;

    form.appendChild(inp1);
    form.appendChild(inp2);
    document.body.appendChild(form);
    form.submit();
});
</script>
</body>
</html>
