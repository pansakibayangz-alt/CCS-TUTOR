<?php
session_start();
require_once 'db.php';

// Only INSTRUCTOR can view
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

$assessment_id = $_GET['assessment_id'] ?? null;
if (!$assessment_id) die("Invalid assessment ID.");

// Fetch certificate content from DB
$stmt = $pdo->prepare("SELECT certificate_text FROM assessments WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cert || empty($cert['certificate_text'])) {
    die("No certificate uploaded for this assessment.");
}

// Output certificate HTML
echo $cert['certificate_text'];
?>
