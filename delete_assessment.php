<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

if (!isset($_GET['id'])) {
    header("Location: instructor_manage_assessment.php?msg=invalid");
    exit;
}

$assessment_id = $_GET['id'];

// Delete assessment
$stmt = $pdo->prepare("DELETE FROM assessments WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);

header("Location: instructor_manage_assessment.php?msg=deleted");
exit;
?>
