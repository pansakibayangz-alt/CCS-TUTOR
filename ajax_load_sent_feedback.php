<?php
session_start();
require_once 'db.php';

$instructor_id = $_SESSION['instructor_id'];

// INSTRUCTOR → STUDENT
$sentStudentsStmt = $pdo->prepare("
SELECT f.*, s.surname, s.firstname, s.middlename, s.year_level, s.block, c.course_name, l.lesson_title
FROM feedback f
LEFT JOIN students s ON f.to_type='STUDENT' AND f.to_id=s.student_id
LEFT JOIN courses c ON f.course_id=c.course_id
LEFT JOIN lessons l ON f.lesson_id=l.lesson_id
WHERE f.from_type='INSTRUCTOR' AND f.from_id=? AND f.to_type='STUDENT'
ORDER BY f.created_at DESC
");
$sentStudentsStmt->execute([$instructor_id]);
$sentStudents = $sentStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

// INSTRUCTOR → ADMIN
$sentAdminsStmt = $pdo->prepare("
SELECT f.*, a.surname, a.firstname, a.middlename, a.position, c.course_name
FROM feedback f
LEFT JOIN admin a ON f.to_type='ADMIN' AND f.to_id=a.admin_id
LEFT JOIN courses c ON f.course_id=c.course_id
WHERE f.from_type='INSTRUCTOR' AND f.from_id=? AND f.to_type='ADMIN'
ORDER BY f.created_at DESC
");
$sentAdminsStmt->execute([$instructor_id]);
$sentAdmins = $sentAdminsStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'students'=>$sentStudents,
    'admins'=>$sentAdmins
]);