<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Get actual student_id from session school_id
$schoolId = $_SESSION['school_id'];
$stmt = $pdo->prepare("SELECT student_id FROM students WHERE school_id = ?");
$stmt->execute([$schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) die("Student not found.");

$studentId = $student['student_id'];
$lessonId = intval($_POST['lesson_id'] ?? 0);
if ($lessonId <= 0) die("Invalid lesson ID");

// Insert into student_syllabus_read
$stmt = $pdo->prepare("
    INSERT INTO student_syllabus_read (student_id, lesson_id) 
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
");
$stmt->execute([$studentId, $lessonId]);

// Redirect back to the lesson page
header("Location: student_view_lesson_content.php?lesson_id=$lessonId");
exit;
