<?php
session_start();
require_once 'db.php';

$from_type = $_POST['from_type'];
$to_type   = $_POST['to_type'];

$from_id = $_SESSION['instructor_id'];

$to_id = ($to_type == 'STUDENT') 
    ? $_POST['to_student'] 
    : $_POST['to_admin'];

$course_id = $_POST['course_id'] ?? null;
$lesson_id = $_POST['lesson_id'] ?? null;
$message   = $_POST['message'];

$stmt = $pdo->prepare("
INSERT INTO feedback
(from_type,from_id,to_type,to_id,course_id,lesson_id,message)
VALUES (?,?,?,?,?,?,?)
");

$stmt->execute([
    $from_type,
    $from_id,
    $to_type,
    $to_id,
    $course_id,
    $lesson_id,
    $message
]);

echo "success";