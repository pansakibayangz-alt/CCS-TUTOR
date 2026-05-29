<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$feedbackId = $_POST['feedback_id'] ?? null;
if (!$feedbackId) {
    echo json_encode(['success' => false, 'message' => 'Missing feedback ID']);
    exit;
}

// Update the feedback status to read
$stmt = $pdo->prepare("UPDATE feedback SET is_read = 1 WHERE feedback_id = ?");
$res = $stmt->execute([$feedbackId]);

echo json_encode(['success' => $res]);