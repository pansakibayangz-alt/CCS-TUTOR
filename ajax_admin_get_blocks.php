<?php
require_once 'db.php';

$year = $_GET['year'] ?? '';

$stmt = $pdo->prepare("
SELECT DISTINCT l.block
FROM lessons l
JOIN courses c ON c.course_id = l.course_id
JOIN students s ON s.year_level = l.year_level AND s.block = l.block
WHERE l.year_level = ? AND s.instructor_id IS NOT NULL
ORDER BY l.block
");

$stmt->execute([$year]);

echo "<option value=''>Select Block</option>";

foreach($stmt as $row){
    echo "<option value='{$row['block']}'>{$row['block']}</option>";
}
?>