<?php
require_once 'db.php';
session_start();

$year = $_GET['year'];
$instructor_id = $_SESSION['instructor_id'];

$stmt = $pdo->prepare("
SELECT DISTINCT s.block
FROM students s
JOIN lessons l 
    ON l.year_level = s.year_level 
    AND l.block = s.block
JOIN courses c 
    ON c.course_id = l.course_id 
    AND c.instructor_id = ?
WHERE s.year_level = ?
AND s.instructor_id = ?
ORDER BY s.block
");

$stmt->execute([$instructor_id, $year, $instructor_id]);

echo "<option>Select Block</option>";
foreach($stmt as $row){
    echo "<option value='{$row['block']}'>{$row['block']}</option>";
}