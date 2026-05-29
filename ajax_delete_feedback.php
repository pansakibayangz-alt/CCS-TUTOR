<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['student_id'])) exit(json_encode(['success'=>false]));

$studentId = $_SESSION['student_id'];
$feedbackId = $_POST['feedback_id'] ?? 0;

if(!$feedbackId) exit(json_encode(['success'=>false]));

// Only allow deleting own feedback
$stmt = $pdo->prepare("DELETE FROM feedback WHERE feedback_id=? AND from_type='STUDENT' AND from_id=?");
$deleted = $stmt->execute([$feedbackId, $studentId]);

echo json_encode(['success' => $deleted]);