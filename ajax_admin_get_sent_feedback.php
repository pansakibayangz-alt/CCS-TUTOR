<?php
session_start();
require_once 'db.php';

$admin_id = $_SESSION['admin_id'];

// ================= STUDENT =================
$stmt1 = $pdo->prepare("
SELECT f.*, 
CONCAT(s.surname, ', ', s.firstname, ' ', s.middlename) AS name,
s.year_level, s.block,
c.course_name, l.lesson_title
FROM feedback f
LEFT JOIN students s ON s.student_id = f.to_id
LEFT JOIN courses c ON c.course_id = f.course_id
LEFT JOIN lessons l ON l.lesson_id = f.lesson_id
WHERE f.from_type='ADMIN' AND f.to_type='STUDENT' AND f.from_id=?
ORDER BY f.created_at DESC
");
$stmt1->execute([$admin_id]);

$student = [];
$student_course = '';
$student_lesson = '';

foreach($stmt1 as $row){
    $student[] = [
        'feedback_id' => $row['feedback_id'],  // ADD THIS
        'name' => $row['name'],
        'year' => $row['year_level'],
        'block' => $row['block'],
        'message' => $row['message'],
        'is_read' => $row['is_read'],
        'reply_message' => $row['reply_message'],
        'reply_is_read' => $row['reply_is_read'], // ADD THIS
        'created_at' => $row['created_at'],
        'reply_created_at' => $row['reply_created_at']
    ];

    $student_course = $row['course_name'];
    $student_lesson = $row['lesson_title'];
}

// ================= INSTRUCTOR =================
$stmt2 = $pdo->prepare("
SELECT f.*, 
CASE 
WHEN i.degree_designation IS NULL OR i.degree_designation='N/A'
THEN CONCAT(i.firstname,' ',i.middlename,' ',i.surname)
ELSE CONCAT(i.firstname,' ',i.middlename,' ',i.surname,', ',i.degree_designation)
END AS name,
c.course_name, l.lesson_title
FROM feedback f
LEFT JOIN instructor i ON i.instructor_id = f.to_id
LEFT JOIN courses c ON c.course_id = f.course_id
LEFT JOIN lessons l ON l.lesson_id = f.lesson_id
WHERE f.from_type='ADMIN' AND f.to_type='INSTRUCTOR' AND f.from_id=?
ORDER BY f.created_at DESC
");
$stmt2->execute([$admin_id]);

$instructor = [];
$instructor_course = '';
$instructor_lesson = '';

foreach($stmt2 as $row){
    $instructor[] = [
        'feedback_id' => $row['feedback_id'],  // ADD THIS
        'name' => strtoupper($row['name']),
        'message' => $row['message'],
        'is_read' => $row['is_read'],
        'reply_message' => $row['reply_message'],
        'reply_is_read' => $row['reply_is_read'], // ADD THIS
        'created_at' => $row['created_at'],
        'reply_created_at' => $row['reply_created_at']
    ];

    $instructor_course = $row['course_name'];
    $instructor_lesson = $row['lesson_title'];
}

// OUTPUT
echo json_encode([
    'student'=>$student,
    'instructor'=>$instructor,
    'student_course'=>$student_course,
    'student_lesson'=>$student_lesson,
    'instructor_course'=>$instructor_course,
    'instructor_lesson'=>$instructor_lesson
]);