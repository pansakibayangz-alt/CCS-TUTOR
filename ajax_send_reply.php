<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    http_response_code(403);
    exit('Unauthorized');
}

require_once 'db.php';

if(!isset($_POST['feedback_id'], $_POST['reply_message'])){
    http_response_code(400);
    exit('Invalid request');
}

$feedback_id = intval($_POST['feedback_id']);
$reply_message = trim($_POST['reply_message']);

// FIXED: always the logged-in instructor
$reply_from_type = 'INSTRUCTOR';
$reply_from_id = $_SESSION['instructor_id'];
$reply_created_at = date('Y-m-d H:i:s');

// Update feedback record with reply
$stmt = $pdo->prepare("
    UPDATE feedback 
    SET 
        reply_message = ?, 
        reply_from_type = ?, 
        reply_from_id = ?, 
        reply_created_at = NOW(),
        reply_is_read = 0
    WHERE feedback_id = ?
");
$stmt->execute([$reply_message, $reply_from_type, $reply_from_id, $feedback_id]);

echo json_encode(['success'=>true, 'reply_created_at'=>$reply_created_at]);