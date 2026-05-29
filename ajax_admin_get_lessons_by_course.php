<?php
require_once 'db.php';

$course_id = $_GET['course_id'];

$stmt = $pdo->prepare("
SELECT lesson_id, lesson_title
FROM lessons
WHERE course_id=?
ORDER BY lesson_no ASC
");

$stmt->execute([$course_id]);

echo "<option value=''>Select Lesson</option>";

foreach($stmt as $l){
    echo "<option value='{$l['lesson_id']}'>{$l['lesson_title']}</option>";
}
?>