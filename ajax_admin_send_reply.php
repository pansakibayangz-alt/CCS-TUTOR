<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit("Unauthorized");
}

require_once 'db.php';

$admin_id = $_SESSION['admin_id'] ?? 0;

// GET POST DATA
$feedback_id = $_POST['feedback_id'] ?? null;
$reply_message = $_POST['message'] ?? ''; // <-- Changed from 'reply_message' to 'message'

// Validate
if (!$feedback_id || !$reply_message) {
    http_response_code(400);
    exit("Invalid data");
}

// UPDATE FEEDBACK WITH REPLY
$stmt = $pdo->prepare("
    UPDATE feedback
    SET reply_message = ?, 
        reply_from_type = 'ADMIN',
        reply_from_id = ?,
        reply_is_read = 0,
        reply_created_at = NOW()
    WHERE feedback_id = ? AND to_type = 'ADMIN'
");

$success = $stmt->execute([$reply_message, $admin_id, $feedback_id]);

if ($success) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Reply sent successfully',
        'reply_created_at' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send reply'
    ]);
}