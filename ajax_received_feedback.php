<?php
require_once 'db.php';

$id = $_GET['instructor_id'];
$search = "%".$_GET['search']."%";

$stmt = $pdo->prepare("
SELECT f.*, 
CASE 
WHEN f.from_type='STUDENT' THEN CONCAT(s.surname,', ',s.firstname)
WHEN f.from_type='ADMIN' THEN CONCAT(a.surname,', ',a.firstname)
END as name
FROM feedback f
LEFT JOIN students s ON f.from_id=s.student_id
LEFT JOIN admin a ON f.from_id=a.admin_id
WHERE f.to_id=? AND f.to_type='INSTRUCTOR'
AND f.message LIKE ?
ORDER BY f.created_at DESC
");
$stmt->execute([$id,$search]);

foreach($stmt as $r){

echo "<div class='card-box'>
<b>{$r['name']}</b><br>

<button class='btn btn-info btn-sm'
onclick=\"viewReply('{$r['message']}','{$r['created_at']}')\">
View
</button>

<br>Status: ".($r['is_read']?'Read':'Unread')."

<br><textarea id='reply_{$r['feedback_id']}' class='form-control mt-2'></textarea>

<button class='btn btn-warning mt-2'
onclick='sendReply({$r['feedback_id']})'>
Reply
</button>

</div>";
}