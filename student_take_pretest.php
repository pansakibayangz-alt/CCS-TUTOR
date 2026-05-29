<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$schoolId = $_SESSION['school_id'];
$successMessage = "";

// Validate pretest_id
if (!isset($_GET['pretest_id'])) die("Invalid pretest ID.");
$pretestId = intval($_GET['pretest_id']);

// Fetch student info
$stmtStudent = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmtStudent->execute([$schoolId]);
$student = $stmtStudent->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

// Fetch pretest
$stmtPretest = $pdo->prepare("
    SELECT * FROM pretests 
    WHERE pretest_id = ? AND year_level = ? AND block = ?
");
$stmtPretest->execute([$pretestId, $student['year_level'], $student['block']]);
$pretest = $stmtPretest->fetch();
if (!$pretest) die("Pretest not found for your year/block.");

// Fetch pretest items
$stmtItems = $pdo->prepare("SELECT * FROM pretest_items WHERE pretest_id = ? ORDER BY item_no ASC");
$stmtItems->execute([$pretestId]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

$totalItems = count($items);

// Check last attempt
$stmtCheck = $pdo->prepare("
    SELECT * FROM student_pretest_attempts 
    WHERE student_id = ? AND pretest_id = ? 
    ORDER BY attempt_no DESC LIMIT 1
");
$stmtCheck->execute([$schoolId, $pretestId]);
$lastAttempt = $stmtCheck->fetch();

$alreadyPerfect = false;
$lastScore = 0;
$displayAttemptNo = 0;
$nextAttemptNo = 1;

if ($lastAttempt) {
    $lastScore = $lastAttempt['score'];
    $displayAttemptNo = $lastAttempt['attempt_no'];
    $nextAttemptNo = $displayAttemptNo + 1;
    if ($lastScore == $totalItems) $alreadyPerfect = true;
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyPerfect) {
    $score = 0;

    foreach ($items as $item) {
        $answer = trim($_POST['q_'.$item['item_id']] ?? '');
        $correct = trim($item['answer']);
        $is_correct = false;

        if ($item['options']) {
            $is_correct = ($answer === $correct);
        } else {
            $is_correct = (strcasecmp($answer, $correct) === 0);
        }

        if ($is_correct) $score++;

        // Update item_analysis
        $analysis = $item['item_analysis'] ? json_decode($item['item_analysis'], true) : ['correct'=>0,'wrong'=>0];
        if ($is_correct) $analysis['correct']++; else $analysis['wrong']++;
        $stmtUpdateItem = $pdo->prepare("UPDATE pretest_items SET item_analysis=? WHERE item_id=?");
        $stmtUpdateItem->execute([json_encode($analysis, JSON_UNESCAPED_UNICODE), $item['item_id']]);
    }

    // Save attempt
    $stmtSave = $pdo->prepare("
        INSERT INTO student_pretest_attempts (student_id, pretest_id, score, attempt_no)
        VALUES (?, ?, ?, ?)
    ");
    $stmtSave->execute([$schoolId, $pretestId, $score, $nextAttemptNo]);

    $remarks = ($score == $totalItems) ? "Perfect Score! ✅" : "Keep Trying ❌";
    $successMessage = "Attempt #$nextAttemptNo submitted. Score: $score / $totalItems. Remarks: $remarks";

    $alreadyPerfect = ($score == $totalItems);
    $lastScore = $score;
    $displayAttemptNo = $nextAttemptNo;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Pretest: <?= htmlspecialchars($pretest['instructions']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy), var(--navy2));
    color: var(--white);
    min-height: 100vh;
    padding-bottom: 80px;
}

/* NAVBAR */
.navbar-custom{
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}

.navbar-custom .navbar-brand {
    color: var(--gold) !important;
    font-weight: 700;
}

/* BOX */
.box {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.55);
    border-radius: 14px;
    backdrop-filter: blur(8px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.45);
    padding: 25px;
    margin: 30px 0;
}

/* PROGRESS */
.progress-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,215,0,0.25);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}

/* FORM */
.form-control {
    background: rgba(255,255,255,0.9);
    border-radius: 10px;
    border: none;
}

.form-check-input:checked {
    background-color: var(--gold);
    border-color: var(--gold);
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

/* FOOTER */
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    backdrop-filter: blur(8px);
    color:#fff;
    border-top:1px solid rgba(255,215,0,0.12);
    font-weight:600;
}
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="">
        CSTUTORHUB — PRETEST
    </a>
  </div>
</nav>

<div class="container mt-5">
    <a href="javascript:history.back()" class="btn btn-warning fw-bold mb-4">← Back</a>

    <div class="box">

        <?php if ($successMessage): ?>
            <div class="alert alert-info"><?= $successMessage ?></div>
        <?php endif; ?>

        <div class="progress-box">
            <p><strong>Attempt:</strong> <?= $displayAttemptNo ?> </p>
            <p><strong>Last Score:</strong> <?= $lastScore ?> / <?= $totalItems ?></p>
            <p><strong>Total Items:</strong> <?= $totalItems ?></p>
        </div>

        <?php if (!$alreadyPerfect && $items): ?>
        <form method="POST">
            <?php foreach ($items as $item): ?>
                <div class="mb-4">
                    <p><strong><?= $item['item_no'] ?>. <?= htmlspecialchars($item['question']); ?></strong></p>

                    <?php if ($item['options']):
                        $options = json_decode($item['options'], true);
                        foreach ($options as $key=>$val): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q_<?= $item['item_id'] ?>" value="<?= htmlspecialchars($key) ?>" required>
                            <label class="form-check-label"><?= htmlspecialchars($key) ?>. <?= htmlspecialchars($val) ?></label>
                        </div>
                    <?php endforeach; else: ?>
                        <input type="text" name="q_<?= $item['item_id'] ?>" class="form-control" required>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success fw-bold">Submit Pretest</button>
        </form>

        <?php elseif ($alreadyPerfect): ?>
            <div class="alert alert-success">
                You have achieved a perfect score! ✅ You can now proceed to the lesson.
            </div>
        <?php endif; ?>

    </div>
</div>

<footer class="text-center py-3">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

</body>
</html>