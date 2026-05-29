<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

$studentId = $_SESSION['student_id'];
$feedbackId = $_POST['feedback_id'] ?? null;
$replyMessage = $_POST['reply_message'] ?? '';
$replyFromType = $_POST['reply_from_type'] ?? 'STUDENT';

if (!$feedbackId || !$replyMessage) {
    echo json_encode(['success' => false, 'msg' => 'Invalid data']);
    exit;
}

// Update feedback table
$stmt = $pdo->prepare("
    UPDATE feedback 
    SET 
        reply_message = ?, 
        reply_from_type = ?, 
        reply_from_id = ?, 
        reply_created_at = NOW(),
        reply_is_read = 0   -- 🔥 THIS IS THE KEY
    WHERE feedback_id = ?
");
$success = $stmt->execute([$replyMessage, $replyFromType, $studentId, $feedbackId]);

echo json_encode(['success' => $success]);