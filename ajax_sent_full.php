<?php
require_once 'db.php';

$id = $_GET['instructor_id'];
$search = "%".$_GET['search']."%";

/* ================= STUDENTS ================= */
$s = $pdo->prepare("
SELECT f.*, 
CONCAT(s.surname,', ',s.firstname,' ',s.middlename) as name,
s.year_level,s.block,
c.course_name,
l.lesson_title
FROM feedback f
JOIN students s ON f.to_id = s.student_id
LEFT JOIN courses c ON f.course_id=c.course_id
LEFT JOIN lessons l ON f.lesson_id=l.lesson_id
WHERE f.from_id=? AND f.to_type='STUDENT'
AND (c.course_name LIKE ? OR l.lesson_title LIKE ? OR s.surname LIKE ?)
ORDER BY f.created_at DESC
");
$s->execute([$id,$search,$search,$search]);

/* ================= ADMINS ================= */
$a = $pdo->prepare("
SELECT f.*, 
CONCAT(a.surname,', ',a.firstname,' ',a.middlename) as name,
a.position,
c.course_name
FROM feedback f
JOIN admin a ON f.to_id = a.admin_id
LEFT JOIN courses c ON f.course_id=c.course_id
WHERE f.from_id=? AND f.to_type='ADMIN'
AND (c.course_name LIKE ? OR a.surname LIKE ?)
ORDER BY f.created_at DESC
");
$a->execute([$id,$search,$search]);

echo json_encode([
"students"=>array_map(function($r){
return [
"name"=>$r['name'],
"year"=>$r['year_level'],
"block"=>$r['block'],
"course"=>$r['course_name'],
"lesson"=>$r['lesson_title'],
"message"=>$r['message'],
"status"=>$r['is_read']?'Read':'Unread',
"date"=>$r['created_at'],
"reply"=>$r['reply_message'],
"reply_date"=>$r['reply_created_at']
];
},$s->fetchAll(PDO::FETCH_ASSOC)),

"admins"=>array_map(function($r){
return [
"name"=>$r['name'],
"position"=>$r['position'],
"course"=>$r['course_name'],
"message"=>$r['message'],
"status"=>$r['is_read']?'Read':'Unread',
"date"=>$r['created_at'],
"reply"=>$r['reply_message'],
"reply_date"=>$r['reply_created_at']
];
},$a->fetchAll(PDO::FETCH_ASSOC))
]);