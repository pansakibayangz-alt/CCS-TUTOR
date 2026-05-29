<?php
require_once 'db.php';

$course_id = $_GET['course_id'];
$year = $_GET['year'];
$block = $_GET['block'];

$stmt = $pdo->prepare("
SELECT lesson_id, lesson_title
FROM lessons
WHERE course_id = ?
AND year_level = ?
AND block = ?
ORDER BY lesson_no
");

$stmt->execute([$course_id, $year, $block]);

echo "<option>Select Lesson</option>";
foreach($stmt as $l){
    echo "<option value='{$l['lesson_id']}'>{$l['lesson_title']}</option>";
}