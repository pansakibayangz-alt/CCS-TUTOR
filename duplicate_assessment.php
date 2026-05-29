<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once "db.php";

if (!isset($_GET['id'])) {
    header("Location: instructor_manage_assessment.php?msg=invalid");
    exit;
}

$assessment_id = $_GET['id'];
$new_block = $_GET['block'] ?? '';

if (!$new_block) {
    header("Location: instructor_manage_assessment.php?msg=noblock");
    exit;
}

// Fetch original assessment
$stmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    header("Location: instructor_manage_assessment.php?msg=notfound");
    exit;
}

// Fetch original lesson to know lesson_no
$stmtL = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
$stmtL->execute([$assessment['lesson_id']]);
$origLesson = $stmtL->fetch(PDO::FETCH_ASSOC);

if (!$origLesson) {
    header("Location: instructor_manage_assessment.php?msg=nolesson");
    exit;
}

$lesson_no = $origLesson['lesson_no'];

// FIND EQUIVALENT LESSON IN NEW BLOCK
$stmtTarget = $pdo->prepare("
    SELECT * FROM lessons
    WHERE course_id = ?
      AND year_level = ?
      AND block = ?
      AND lesson_no = ?
");
$stmtTarget->execute([
    $origLesson['course_id'],
    $origLesson['year_level'],
    $new_block,
    $lesson_no
]);

$newLesson = $stmtTarget->fetch(PDO::FETCH_ASSOC);

if (!$newLesson) {
    header("Location: instructor_manage_assessment.php?msg=nolessonmatch");
    exit;
}

$new_lesson_id = $newLesson['lesson_id'];

// DUPLICATE ASSESSMENT
$insert = $pdo->prepare("
    INSERT INTO assessments (instructor_id, course_id, lesson_id, assessment_type, instructions, year_level, block)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$insert->execute([
    $assessment['instructor_id'],
    $assessment['course_id'],
    $new_lesson_id,
    $assessment['assessment_type'],
    $assessment['instructions'] . ' (COPY)',
    $newLesson['year_level'],
    $newLesson['block']
]);

$new_assessment_id = $pdo->lastInsertId();

// DUPLICATE ITEMS
$itemStmt = $pdo->prepare("SELECT * FROM assessment_items WHERE assessment_id = ?");
$itemStmt->execute([$assessment_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$insertItem = $pdo->prepare("
    INSERT INTO assessment_items (assessment_id, item_no, question, options, answer)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($items as $it) {
    $insertItem->execute([
        $new_assessment_id,
        $it['item_no'],
        $it['question'],
        $it['options'],
        $it['answer']
    ]);
}

header("Location: instructor_manage_assessment.php?msg=duplicated");
exit;
?>
