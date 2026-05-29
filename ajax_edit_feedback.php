<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['student_id'])) exit(json_encode(['success'=>false]));

$studentId = $_SESSION['student_id'];
$feedbackId = $_POST['feedback_id'] ?? 0;
$message = $_POST['message'] ?? '';

if(!$feedbackId || !$message) exit(json_encode(['success'=>false]));

// Only allow editing own feedback
$stmt = $pdo->prepare("UPDATE feedback SET message=? WHERE feedback_id=? AND from_type='STUDENT' AND from_id=?");
$updated = $stmt->execute([$message, $feedbackId, $studentId]);

echo json_encode(['success' => $updated]);