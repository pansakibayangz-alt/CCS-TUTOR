<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN'){
    exit(json_encode(['status'=>'error','message'=>'Unauthorized']));
}

require_once 'db.php';

$feedback_id = $_POST['feedback_id'] ?? 0;
if(!$feedback_id) exit(json_encode(['status'=>'error','message'=>'No feedback id']));

// Fetch reply info
$stmt = $pdo->prepare("SELECT reply_message, reply_is_read FROM feedback WHERE feedback_id=?");
$stmt->execute([$feedback_id]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$feedback || !$feedback['reply_message']){
    exit(json_encode(['status'=>'error','message'=>'No reply yet']));
}

// Mark as read regardless
if(!$feedback['reply_is_read']){
    $update = $pdo->prepare("UPDATE feedback SET reply_is_read=1 WHERE feedback_id=?");
    $update->execute([$feedback_id]);
}

// Always return reply message AND current read status
echo json_encode([
    'status' => 'success',
    'reply_message' => $feedback['reply_message'],
    'reply_is_read' => 1
]);