<?php
require_once "db.php";

$course_id = $_GET['course_id'] ?? '';
$year = $_GET['year_level'] ?? '';
$block = $_GET['block'] ?? '';

$stmt = $pdo->prepare("
    SELECT lesson_id, lesson_title, year_level, block
    FROM lessons
    WHERE course_id = ? AND year_level = ? AND block = ?
");
$stmt->execute([$course_id, $year, $block]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
