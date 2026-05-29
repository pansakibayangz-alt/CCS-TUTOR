<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$lesson_id = intval($_POST['lesson_id'] ?? 0);
if ($lesson_id <= 0) die("Invalid ID");

$lesson_no = intval($_POST['lesson_no']);
$lesson_title = trim($_POST['lesson_title']);

// Fetch old lesson details
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) die("Lesson not found");

// Default = keep old paths
$newLessonFile = $lesson['lesson_file'];
$newSyllabusFile = $lesson['syllabus_file'];

// Allowed file types
$allowed = ['pdf', 'ppt', 'pptx', 'doc', 'docx'];

// Base directory
$uploadDir = __DIR__ . '/uploads/lessons/' . $lesson['instructor_id'];
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* --------------------------------------------------
   HANDLE LESSON FILE UPLOAD
-------------------------------------------------- */
if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === UPLOAD_ERR_OK) {

    $ext = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) die("Invalid lesson file type.");

    $safeName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . '/' . $safeName;

    if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $target)) {

        // Delete old file
        if ($lesson['lesson_file'] && file_exists(__DIR__ . '/' . $lesson['lesson_file'])) {
            unlink(__DIR__ . '/' . $lesson['lesson_file']);
        }

        $newLessonFile = 'uploads/lessons/' . $lesson['instructor_id'] . '/' . $safeName;
    }
}

/* --------------------------------------------------
   HANDLE SYLLABUS FILE UPLOAD (NEW)
-------------------------------------------------- */
if (isset($_FILES['syllabus_file']) && $_FILES['syllabus_file']['error'] === UPLOAD_ERR_OK) {

    $ext = strtolower(pathinfo($_FILES['syllabus_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) die("Invalid syllabus file type.");

    $safeName = 'syllabus_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . '/' . $safeName;

    if (move_uploaded_file($_FILES['syllabus_file']['tmp_name'], $target)) {

        // Delete old syllabus file
        if ($lesson['syllabus_file'] && file_exists(__DIR__ . '/' . $lesson['syllabus_file'])) {
            unlink(__DIR__ . '/' . $lesson['syllabus_file']);
        }

        $newSyllabusFile = 'uploads/lessons/' . $lesson['instructor_id'] . '/' . $safeName;
    }
}

/* --------------------------------------------------
   UPDATE LESSON WITH BOTH FILES
-------------------------------------------------- */
$update = $pdo->prepare("
    UPDATE lessons 
    SET 
        lesson_no = ?, 
        lesson_title = ?, 
        lesson_file = ?,
        syllabus_file = ?
    WHERE lesson_id = ?
");

$update->execute([
    $lesson_no,
    $lesson_title,
    $newLessonFile,
    $newSyllabusFile,
    $lesson_id
]);

header("Location: instructor_manage_lessons.php?msg=updated");
exit;
?>
