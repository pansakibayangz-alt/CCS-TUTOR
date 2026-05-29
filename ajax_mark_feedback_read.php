<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    http_response_code(403);
    exit('Unauthorized');
}

require_once 'db.php';

if(!isset($_POST['feedback_id'], $_POST['to_type'])){
    http_response_code(400);
    exit('Invalid request');
}

$feedback_id = intval($_POST['feedback_id']);
$to_type = $_POST['to_type'];

// Mark as read
$stmt = $pdo->prepare("
    UPDATE feedback 
    SET is_read = 1
    WHERE feedback_id = ? AND to_type = ?
");
$stmt->execute([$feedback_id, $to_type]);

echo json_encode(['success'=>true]);