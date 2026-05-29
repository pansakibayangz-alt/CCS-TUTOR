<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    http_response_code(403);
    exit('Unauthorized');
}

require_once 'db.php';

$studentId = $_SESSION['student_id']; // <-- Use student_id, not school_id
$from_type = 'STUDENT';
$to_type = $_POST['to_type']; // INSTRUCTOR or ADMIN
$to_id = $_POST['to_instructor'] ?? $_POST['to_admin']; // instructor_id or admin_id
$course_id = $_POST['course_id'] ?? $_POST['course_id_admin'] ?? null;
$lesson_id = $_POST['lesson_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$to_id || !$message) {
    http_response_code(400);
    exit('Missing required fields.');
}

$stmt = $pdo->prepare("
    INSERT INTO feedback
    (from_type, from_id, to_type, to_id, course_id, lesson_id, message)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $from_type,
    $studentId,
    $to_type,
    $to_id,
    $course_id ?: null,
    $lesson_id ?: null,
    $message
]);

echo 'success';