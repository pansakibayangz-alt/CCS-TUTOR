<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$role = $_POST['role'] ?? '';

if ($role === 'ADMIN') {
    $username = $_POST['admin_username'] ?? '';
    $password = $_POST['admin_password'] ?? '';

    if (empty($username) || empty($password)) {
        die("Please fill in all fields.");
    }

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        die("Invalid username or password for Admin.");
    }

    // Set session and redirect
    $_SESSION['role'] = 'ADMIN';
    $_SESSION['username'] = $user['username'];
    header("Location: admin_dashboard.php");
    exit;

} elseif ($role === 'INSTRUCTOR') {
    $username = $_POST['instructor_username'] ?? '';
    $password = $_POST['instructor_password'] ?? '';

    if (empty($username) || empty($password)) {
        die("Please fill in all fields.");
    }

    $stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        die("Invalid username or password for Instructor.");
    }

    $_SESSION['role'] = 'INSTRUCTOR';
    $_SESSION['username'] = $user['username'];
    header("Location: instructor_dashboard.php");
    exit;

} elseif ($role === 'STUDENT') {
    $school_id = trim($_POST['school_id'] ?? '');
    $password = $_POST['student_password'] ?? '';

    if (empty($school_id) || empty($password)) {
        die("Please fill in all fields.");
    }

    $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !password_verify($password, $student['password'])) {
        die("Invalid School ID or password for Student.");
    }

    $_SESSION['role'] = 'STUDENT';
    $_SESSION['school_id'] = $student['school_id'];
    header("Location: student_dashboard.php");
    exit;

} else {
    die("Invalid role selected.");
}
?>
