<?php
require_once 'db.php';

$instructor_id = $_GET['instructor_id'];

$stmt = $pdo->prepare("
SELECT course_id, course_name
FROM courses
WHERE instructor_id=?
");

$stmt->execute([$instructor_id]);

echo "<option value=''>Select Course</option>";

foreach($stmt as $c){
    echo "<option value='{$c['course_id']}'>{$c['course_name']}</option>";
}
?>