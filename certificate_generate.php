<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Verify login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

// Required parameters:
if (!isset($_GET['student_id']) || !isset($_GET['assessment_id'])) {
    die("Missing parameters.");
}

$student_id = $_GET['student_id'];
$assessment_id = $_GET['assessment_id'];

/* ------------------------------------
   1. LOAD STUDENT INFO
------------------------------------ */
$stmt = $pdo->prepare("SELECT firstname, surname FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) die("Student not found");

$student_name = $student['firstname'] . " " . $student['surname'];

/* ------------------------------------
   2. LOAD ASSESSMENT & COURSE INFO
------------------------------------ */
$stmt = $pdo->prepare("
    SELECT a.assessment_title, a.certificate_text, c.course_name, l.lesson_title
    FROM assessments a
    JOIN lessons l ON l.lesson_id = a.lesson_id
    JOIN courses c ON c.course_id = l.course_id
    WHERE a.assessment_id = ?
");
$stmt->execute([$assessment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Assessment not found");

$course_name = $data['course_name'];
$lesson_title = $data['lesson_title'];
$assessment_title = $data['assessment_title'];
$template = $data['certificate_text'];

/* ------------------------------------
   3. GENERATE UNIQUE CERTIFICATE NUMBER
------------------------------------ */
$certificate_number = "CERT-" . strtoupper(substr(md5(time() . $student_id), 0, 10));

/* ------------------------------------
   4. REPLACE PLACEHOLDERS
------------------------------------ */
$replacements = [
    "{student_name}"       => $student_name,
    "{course_name}"        => $course_name,
    "{lesson_title}"       => $lesson_title,
    "{assessment_title}"   => $assessment_title,
    "{date}"               => date("F d, Y"),
    "{certificate_number}" => $certificate_number,
];

$html = str_replace(array_keys($replacements), array_values($replacements), $template);

/* ------------------------------------
   5. CONVERT TO PDF USING DOMPDF
------------------------------------ */
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // You can change to portrait
$dompdf->render();

// Download as PDF
$dompdf->stream("Certificate_{$student_id}.pdf", ["Attachment" => true]);
exit;
?>
