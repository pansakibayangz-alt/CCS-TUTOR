<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit("Unauthorized");
}

require_once 'db.php';

$admin_id = $_SESSION['admin_id'] ?? 0;
$feedback_id = $_POST['feedback_id'] ?? null;

if (!$feedback_id) {
    http_response_code(400);
    exit("Invalid data");
}

// MARK FEEDBACK AS READ
$stmt = $pdo->prepare("
    UPDATE feedback
    SET is_read = 1
    WHERE feedback_id = ? AND to_type = 'ADMIN'
");
$success = $stmt->execute([$feedback_id]);

if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'Marked as read']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to mark as read']);
}