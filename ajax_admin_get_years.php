<?php
require_once 'db.php';

$stmt = $pdo->query("
SELECT DISTINCT l.year_level
FROM lessons l
JOIN courses c ON c.course_id = l.course_id
JOIN students s ON s.year_level = l.year_level AND s.block = l.block
WHERE s.instructor_id IS NOT NULL
ORDER BY l.year_level
");

echo "<option value=''>Select Year</option>";

foreach($stmt as $row){
    echo "<option value='{$row['year_level']}'>{$row['year_level']}</option>";
}
?>