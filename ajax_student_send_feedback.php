<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    http_response_code(403);
    exit('Unauthorized');
}

require_once 'db.php';

$studentId = $_SESSION['student_id'];
$from_type = 'STUDENT';
$to_type = $_POST['to_type'] ?? null;
$message = trim($_POST['message'] ?? '');
$course_id = $_POST['course_id'] ?? $_POST['course_id_admin'] ?? null;
$lesson_id = $_POST['lesson_id'] ?? null;

// Correctly determine to_id
$to_id = null;
if (!empty($_POST['to_instructor'])) {
    $to_id = $_POST['to_instructor'];
} elseif (!empty($_POST['to_admin'])) {
    $to_id = $_POST['to_admin'];
}

// Validate
if (!$to_id || !$to_type || !$message) {
    http_response_code(400);
    exit('Missing required fields.');
}

// Insert into feedback
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