<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['certificate_file'])) {
    $assessment_id = $_POST['assessment_id'];
    $file = $_FILES['certificate_file'];

    if ($file['error'] === 0) {
        $content = file_get_contents($file['tmp_name']); // read file content

        // 1️⃣ Save certificate template in assessments table
        $stmt = $pdo->prepare("UPDATE assessments SET certificate_text = ? WHERE assessment_id = ?");
        $stmt->execute([$content, $assessment_id]);

        // 2️⃣ Optionally: Insert certificate record for students
        // Note: You need to define which student(s) this certificate is for.
        // Here is an example inserting for all students in the assessment's group:
        $studentsStmt = $pdo->prepare("SELECT student_id FROM students 
                                       WHERE year_level = (SELECT year_level FROM assessments WHERE assessment_id = ?)
                                         AND block = (SELECT block FROM assessments WHERE assessment_id = ?)");
        $studentsStmt->execute([$assessment_id, $assessment_id]);
        $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as $student) {
            // Generate a unique certificate number
            $certificate_number = 'CERT-' . strtoupper(uniqid());

            $insertCert = $pdo->prepare("INSERT INTO certificates 
                (certificate_number, student_id, assessment_id) VALUES (?, ?, ?)");
            $insertCert->execute([$certificate_number, $student['student_id'], $assessment_id]);
        }

        header("Location: instructor_manage_assessment.php?msg=certificate_saved");
        exit;
    } else {
        die("File upload error.");
    }
}

die("Invalid request.");
?>
