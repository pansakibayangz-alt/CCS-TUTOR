<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT instructor_id FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die('Instructor not found');
$instructor_id = $instructor['instructor_id'];

$pretest_id = intval($_GET['id'] ?? 0);
if (!$pretest_id) die('Invalid Pretest ID');

// Optional: check if the pretest belongs to this instructor
$check = $pdo->prepare("SELECT pretest_id FROM pretests WHERE pretest_id=? AND instructor_id=?");
$check->execute([$pretest_id, $instructor_id]);
if (!$check->fetch()) die('Pretest not found or unauthorized');

// Delete pretest (items are automatically deleted via ON DELETE CASCADE)
$del = $pdo->prepare("DELETE FROM pretests WHERE pretest_id=?");
$del->execute([$pretest_id]);

header("Location: instructor_manage_pretest.php?msg=deleted");
exit;
