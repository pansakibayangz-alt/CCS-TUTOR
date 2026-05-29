<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'db.php';

$username = $_SESSION['username'] ?? '';
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username=?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) {
    echo json_encode([]);
    exit;
}
$instructor_id = $instructor['instructor_id'];

// Get course_id from GET
$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    echo json_encode([]);
    exit;
}

// Fetch lessons for this course and instructor
$lessonStmt = $pdo->prepare("
    SELECT lesson_id, lesson_no, lesson_title, year_level, block
    FROM lessons 
    WHERE instructor_id = ? AND course_id = ? 
    ORDER BY lesson_no ASC
");
$lessonStmt->execute([$instructor_id, $course_id]);
$lessons = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($lessons);
