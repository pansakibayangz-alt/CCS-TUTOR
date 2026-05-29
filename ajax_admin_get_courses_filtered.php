<?php
require_once 'db.php';

$student_id = $_GET['student_id'];
$year = $_GET['year'];
$block = $_GET['block'];

$stmt = $pdo->prepare("
SELECT DISTINCT c.course_id, c.course_name
FROM courses c
JOIN lessons l ON l.course_id = c.course_id
JOIN students s ON s.year_level = l.year_level AND s.block = l.block
WHERE s.student_id=?  
AND l.year_level=? 
AND l.block=?
");

$stmt->execute([$student_id, $year, $block]);

echo "<option value=''>Select Course</option>";

foreach($stmt as $c){
    echo "<option value='{$c['course_id']}'>{$c['course_name']}</option>";
}
?>