<?php
session_start();
require_once 'db.php';

$course_id = $_GET['course_id'] ?? '';

$stmt = $pdo->prepare("SELECT lesson_id, lesson_title FROM lessons WHERE course_id=? AND instructor_id=? ORDER BY lesson_no");
$stmt->execute([$course_id, $_SESSION['instructor_id']]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<option value=''>Select Lesson</option>";
foreach($data as $l){
    echo "<option value='{$l['lesson_id']}'>{$l['lesson_title']}</option>";
}