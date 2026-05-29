<?php
session_start();
require_once 'db.php';

// Check if student login session exists
if (isset($_SESSION['role']) && $_SESSION['role'] === 'STUDENT' && isset($_SESSION['login_id'])) {
    $loginId = $_SESSION['login_id'];
    
    // Update logout_time for this login
    $stmt = $pdo->prepare("UPDATE student_logins SET logout_time = NOW() WHERE login_id = ?");
    $stmt->execute([$loginId]);
}

// Destroy session for all users (admin, instructor, student)
session_destroy();

// Redirect back to login page
header("Location: admin_login.php");
exit;
