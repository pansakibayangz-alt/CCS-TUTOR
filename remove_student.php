<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Validate GET parameter
if(!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid student ID.");
}

$studentId = $_GET['id'];

// Check if the student exists
$stmtCheck = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmtCheck->execute([$studentId]);
$student = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    die("Student not found.");
}

// Delete student
$stmtDelete = $pdo->prepare("DELETE FROM students WHERE school_id = ?");
if($stmtDelete->execute([$studentId])) {
    // Redirect back to students page
    header("Location: admin_manage_students.php?msg=Student+removed+successfully");
    exit;
} else {
    die("Failed to remove student.");
}