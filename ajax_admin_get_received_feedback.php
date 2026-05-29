<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit("Unauthorized");
}

require_once 'db.php';

// GET LOGGED-IN ADMIN ID
$admin_id = $_SESSION['admin_id'] ?? 0;

// ===================== FROM STUDENT =====================
$stmtStudent = $pdo->prepare("
    SELECT 
        f.feedback_id,
        CONCAT(UPPER(s.surname), ', ', s.firstname, ' ', COALESCE(s.middlename,'')) AS name,
        s.year_level AS year,
        s.block,
        f.message,
        f.is_read,
        f.created_at,
        f.reply_message,
        f.reply_is_read,
        f.reply_created_at
    FROM feedback f
    JOIN students s ON f.from_id = s.student_id
    WHERE f.to_type='ADMIN' 
      AND f.from_type='STUDENT'
      AND f.to_id=?
    ORDER BY f.created_at DESC
");
$stmtStudent->execute([$admin_id]);
$studentData = $stmtStudent->fetchAll(PDO::FETCH_ASSOC);

// GET LATEST DATE FOR STUDENT FEEDBACK (date only)
$student_date = '';
if (!empty($studentData)) {
    $student_date = date('Y-m-d', strtotime($studentData[0]['created_at']));
}

// ===================== FROM INSTRUCTOR =====================
$stmtInstructor = $pdo->prepare("
    SELECT 
        f.feedback_id,
        CASE 
            WHEN i.degree_designation IS NULL OR i.degree_designation='N/A'
            THEN CONCAT(i.firstname, ' ', COALESCE(i.middlename,''), ' ', i.surname)
            ELSE CONCAT(i.firstname, ' ', COALESCE(i.middlename,''), ' ', i.surname, ', ', i.degree_designation)
        END AS name,
        f.message,
        f.is_read,
        f.created_at,
        f.reply_message,
        f.reply_is_read,
        f.reply_created_at
    FROM feedback f
    JOIN instructor i ON f.from_id = i.instructor_id
    WHERE f.to_type='ADMIN'
      AND f.from_type='INSTRUCTOR'
      AND f.to_id=?
    ORDER BY f.created_at DESC
");
$stmtInstructor->execute([$admin_id]);
$instructorData = $stmtInstructor->fetchAll(PDO::FETCH_ASSOC);

// GET LATEST DATE FOR INSTRUCTOR FEEDBACK (date only)
$instructor_date = '';
if (!empty($instructorData)) {
    $instructor_date = date('Y-m-d', strtotime($instructorData[0]['created_at']));
}

// ===================== RETURN JSON =====================
echo json_encode([
    'student' => $studentData,
    'student_date' => $student_date,
    'instructor' => $instructorData,
    'instructor_date' => $instructor_date
]);