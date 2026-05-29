<?php
session_start();
require_once 'db.php';

// SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

// GET ADMIN ID
$admin_id = $_SESSION['admin_id'];

// GET FORM DATA
$to_id      = $_POST['to_id'] ?? null;
$course_id  = $_POST['course_id'] ?? null;
$lesson_id  = $_POST['lesson_id'] ?? null;
$message    = trim($_POST['message'] ?? '');
$from_type  = $_POST['from_type'] ?? 'ADMIN';
$to_type    = $_POST['to_type'] ?? 'STUDENT';

// VALIDATION
if(!$to_id || $message == ''){
    echo json_encode(['success'=>false,'error'=>'Missing fields']);
    exit;
}

/*
========================================
IMPORTANT:
to_id MUST MATCH:
- STUDENT → student_id
- INSTRUCTOR → instructor_id
========================================
*/

// INSERT FEEDBACK
$stmt = $pdo->prepare("
    INSERT INTO feedback (
        from_type,
        from_id,
        to_type,
        to_id,
        course_id,
        lesson_id,
        message
    ) VALUES (?,?,?,?,?,?,?)
");

$success = $stmt->execute([
    $from_type,
    $admin_id,
    $to_type,
    $to_id,       // ✅ THIS IS student_id OR instructor_id
    $course_id ?: null,
    $lesson_id ?: null,
    $message
]);

if($success){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'error'=>'Insert failed']);
}