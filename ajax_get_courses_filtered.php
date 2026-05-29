<?php
require_once 'db.php';
session_start();

$student_id = $_GET['student_id'];
$year = $_GET['year'];
$block = $_GET['block'];
$instructor_id = $_SESSION['instructor_id'];

$stmt = $pdo->prepare("
SELECT DISTINCT c.course_id, c.course_name
FROM courses c
JOIN lessons l 
    ON l.course_id = c.course_id
WHERE l.year_level = ?
AND l.block = ?
AND c.instructor_id = ?
");

$stmt->execute([$year, $block, $instructor_id]);

echo "<option>Select Course</option>";
foreach($stmt as $c){
    echo "<option value='{$c['course_id']}'>{$c['course_name']}</option>";
}