<?php
session_start();
require_once 'db.php';

// Only instructors allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') exit;

$username = $_SESSION['username'] ?? '';
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) exit;

$instructor_id = $instructor['instructor_id'];
$year_level = $_GET['year_level'] ?? '';
$block = $_GET['block'] ?? '';

if (!$year_level || !$block) exit;

// Fetch courses that have lessons for this year/block
$stmt = $pdo->prepare("
    SELECT DISTINCT c.course_id, c.course_name
    FROM courses c
    INNER JOIN lessons l ON c.course_id = l.course_id
    WHERE c.instructor_id = ? AND l.year_level = ? AND l.block = ?
    ORDER BY c.category, c.course_name
");
$stmt->execute([$instructor_id, $year_level, $block]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($courses);
