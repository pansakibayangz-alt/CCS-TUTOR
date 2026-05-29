<?php
require_once 'db.php';

$id = $_GET['instructor_id'];
$search = "%".$_GET['search']."%";

$stmt = $pdo->prepare("
SELECT * FROM feedback
WHERE from_id=? AND message LIKE ?
ORDER BY created_at DESC
");
$stmt->execute([$id,$search]);

foreach($stmt as $row){
echo "<div class='card-box'>
{$row['message']} <br>
<small>{$row['created_at']}</small>
</div>";
}