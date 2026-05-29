<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['instructor_id'])) {
    echo json_encode(['students'=>[], 'admins'=>[]]);
    exit;
}

$instructor_id = $_SESSION['instructor_id'];

/* ===============================
   STUDENT → INSTRUCTOR
=================================*/
$stmtStudents = $pdo->prepare("
SELECT 
    f.feedback_id,
    f.message,
    f.is_read,
    f.created_at,
    f.reply_message,
    f.reply_from_type,
    f.reply_from_id,
    f.reply_created_at,
    f.reply_is_read,
    s.surname,
    s.firstname,
    s.middlename,
    s.year_level,
    s.block
FROM feedback f
JOIN students s ON s.student_id = f.from_id   -- <-- FIXED: was s.school_id
WHERE f.to_type='INSTRUCTOR'
AND f.from_type='STUDENT'
AND f.to_id = :instructor_id
ORDER BY f.created_at DESC
");
$stmtStudents->execute(['instructor_id'=>$instructor_id]);
$students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   ADMIN → INSTRUCTOR
=================================*/
$stmtAdmins = $pdo->prepare("
SELECT 
    f.feedback_id,
    f.message,
    f.is_read,
    f.created_at,
    f.reply_message,
    f.reply_from_type,
    f.reply_from_id,
    f.reply_created_at,
    f.reply_is_read,
    a.surname,
    a.firstname,
    a.middlename,
    a.position
FROM feedback f
JOIN admin a ON a.admin_id = f.from_id
WHERE f.to_type='INSTRUCTOR'
AND f.from_type='ADMIN'
AND f.to_id = :instructor_id
ORDER BY f.created_at DESC
");
$stmtAdmins->execute(['instructor_id'=>$instructor_id]);
$admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'students'=>$students,
    'admins'=>$admins
]);