<?php
session_start();
require_once 'db.php';

$id = $_POST['feedback_id'];
$msg = $_POST['message'];

$stmt = $pdo->prepare("
UPDATE feedback SET
reply_message=?,
reply_from_type='INSTRUCTOR',
reply_from_id=?,
reply_created_at=NOW(),
reply_is_read=0
WHERE feedback_id=?
");

$stmt->execute([
    $msg,
    $_SESSION['instructor_id'],
    $id
]);

echo "replied";