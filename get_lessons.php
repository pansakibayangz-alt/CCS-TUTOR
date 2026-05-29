<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    exit(json_encode([]));
}

require_once 'db.php';

$instructor_id = $pdo->query("SELECT instructor_id FROM instructor WHERE username = ". $pdo->quote($_SESSION['username']))->fetchColumn();
$course_id = $_GET['course_id'] ?? null;
$year_level = $_GET['year_level'] ?? null;
$block = $_GET['block'] ?? null;

if (!$course_id || !$instructor_id) exit(json_encode([]));

$sql = "SELECT lesson_id, lesson_title, year_level, block 
        FROM lessons 
        WHERE instructor_id = ? AND course_id = ?";
$params = [$instructor_id, $course_id];

if ($year_level) {
    $sql .= " AND year_level = ?";
    $params[] = $year_level;
}

if ($block) {
    $sql .= " AND block = ?";
    $params[] = $block;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($lessons);
