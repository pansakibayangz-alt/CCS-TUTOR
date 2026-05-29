<?php
require_once 'db.php';

$year = $_GET['year'];
$block = $_GET['block'];

$stmt = $pdo->prepare("
SELECT DISTINCT s.*
FROM students s
JOIN lessons l ON l.year_level = s.year_level AND l.block = s.block
JOIN courses c ON c.course_id = l.course_id
WHERE s.year_level=? AND s.block=? AND s.instructor_id IS NOT NULL
ORDER BY s.surname ASC
");

$stmt->execute([$year, $block]);

echo "<option value=''>Select Student</option>";

foreach($stmt as $s){
echo "<option value='{$s['student_id']}'>
{$s['surname']}, {$s['firstname']} {$s['middlename']}
</option>";
}
?>