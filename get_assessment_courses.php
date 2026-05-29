<?php
require_once "db.php";

$year = $_GET['year_level'] ?? '';
$block = $_GET['block'] ?? '';

$stmt = $pdo->prepare("
    SELECT DISTINCT c.course_id, c.course_name
    FROM courses c
    INNER JOIN lessons l ON c.course_id = l.course_id
    WHERE l.year_level = ? AND l.block = ?
");
$stmt->execute([$year, $block]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
