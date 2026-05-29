<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Validate instructor ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_manage_instructors.php?status=invalid");
    exit;
}

$instructor_id = intval($_GET['id']);

// Check if instructor exists
$stmtCheck = $pdo->prepare("SELECT * FROM instructor WHERE instructor_id=?");
$stmtCheck->execute([$instructor_id]);
$instructor = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$instructor) {
    header("Location: admin_manage_instructors.php?status=notfound");
    exit;
}

// Optional: You may want to check if the instructor has assigned students first
$stmtStudent = $pdo->prepare("SELECT COUNT(*) FROM students WHERE instructor_id=?");
$stmtStudent->execute([$instructor_id]);
$studentCount = $stmtStudent->fetchColumn();

if($studentCount > 0) {
    // Prevent deletion if students are assigned
    header("Location: admin_manage_instructors.php?status=hasstudents");
    exit;
}

// Delete instructor
$stmtDelete = $pdo->prepare("DELETE FROM instructor WHERE instructor_id=?");
if ($stmtDelete->execute([$instructor_id])) {
    header("Location: admin_manage_instructors.php?status=success");
} else {
    header("Location: admin_manage_instructors.php?status=error");
}
exit;