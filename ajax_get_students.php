<?php
require_once 'db.php';
session_start();

$year = $_GET['year'];
$block = $_GET['block'];
$instructor_id = $_SESSION['instructor_id'];

$stmt = $pdo->prepare("
SELECT DISTINCT s.student_id, s.surname, s.firstname, s.middlename
FROM students s
JOIN lessons l 
    ON l.year_level = s.year_level 
    AND l.block = s.block
JOIN courses c 
    ON c.course_id = l.course_id 
    AND c.instructor_id = ?
WHERE s.year_level = ?
AND s.block = ?
AND s.instructor_id = ?
ORDER BY s.surname
");

$stmt->execute([$instructor_id, $year, $block, $instructor_id]);

echo "<option>Select Student</option>";

foreach($stmt as $s){

    $middle = !empty($s['middlename']) ? ' '.$s['middlename'] : '';

    echo "<option value='{$s['student_id']}'>
        " . strtoupper($s['surname']) . ", " . strtoupper($s['firstname'].$middle) . "
    </option>";
}
?>