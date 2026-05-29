<?php

// --- Sanitize input safely ---
function clean($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// --- Check if admin/instructor username exists ---
function getAdminAccount($pdo, $username) {
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    return ($stmt->rowCount() === 1) ? $stmt->fetch() : false;
}

// --- Check if student exists by school_id ---
function getStudentAccount($pdo, $schoolId) {
    $sql = "SELECT * FROM students WHERE school_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schoolId]);
    return ($stmt->rowCount() === 1) ? $stmt->fetch() : false;
}

// --- Create password hash ---
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// --- Verify password ---
function verifyPassword($password, $hashed) {
    return password_verify($password, $hashed);
}

// --- Redirect helper ---
function go($page) {
    header("Location: $page");
    exit();
}
?>
