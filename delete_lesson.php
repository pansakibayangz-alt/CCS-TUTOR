<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

if (!isset($_GET['id'])) {
    die("Missing lesson ID");
}

$lesson_id = intval($_GET['id']);

// Check if lesson exists AND belongs to logged-in instructor
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die("Instructor not found");
$instructor_id = $instructor['instructor_id'];

$check = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ? AND instructor_id = ?");
$check->execute([$lesson_id, $instructor_id]);
$lesson = $check->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    die("Lesson not found or you do not have permission to delete it.");
}

// Delete file if exists
if (!empty($lesson['lesson_file']) && file_exists($lesson['lesson_file'])) {
    unlink($lesson['lesson_file']);
}

// Delete from DB
$del = $pdo->prepare("DELETE FROM lessons WHERE lesson_id = ? AND instructor_id = ?");
$del->execute([$lesson_id, $instructor_id]);

header("Location: instructor_manage_lessons.php?msg=deleted");
exit;
?>
