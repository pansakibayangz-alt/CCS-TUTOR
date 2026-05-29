<?php
require_once 'db.php';

$name = $_GET['name'] ?? '';
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if(!$name){
    echo "No student selected";
    exit;
}

if(!$course_id){
    echo "No course selected";
    exit;
}

// Split fullname (FIRST MIDDLE LAST)
$parts = explode(' ', $name);
$firstname = $parts[0] ?? '';
$middlename = $parts[1] ?? '';
$surname = $parts[2] ?? '';

// Get student_id
$stmt = $pdo->prepare("SELECT school_id FROM students 
    WHERE firstname=? AND middlename=? AND surname=?");
$stmt->execute([$firstname,$middlename,$surname]);
$student_id = $stmt->fetchColumn();

if(!$student_id){
    echo "Student not found";
    exit;
}

// GET LESSONS
$stmt = $pdo->prepare("SELECT lesson_id, lesson_title FROM lessons WHERE course_id=?");
$stmt->execute([$course_id]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// START TABLE
echo "<table class='table table-dark table-bordered'>";
echo "<thead>
<tr>
<th>Lesson Title</th>
<th>Lesson Completed</th>
<th>Pretest Score</th>
<th>Pretest Completed</th>
<th>Assessment Score</th>
<th>Assessment Completed</th>
</tr>
</thead><tbody>";

foreach($lessons as $lesson){

    // LESSON COMPLETION
    $stmt = $pdo->prepare("SELECT completed_at FROM student_lesson_completion 
        WHERE student_id=? AND lesson_id=?");
    $stmt->execute([$student_id,$lesson['lesson_id']]);
    $completed = $stmt->fetchColumn();

    $completed_display = $completed ?: "NOT COMPLETED";

    // Get latest pretest attempt per course
$stmt = $pdo->prepare("
    SELECT spa.score, spa.completed_at, p.pretest_id
    FROM student_pretest_attempts spa
    JOIN pretests p ON spa.pretest_id = p.pretest_id
    WHERE spa.student_id=? AND p.course_id=?
    ORDER BY spa.attempt_no DESC LIMIT 1
");
$stmt->execute([$student_id, $course_id]);
$pre = $stmt->fetch(PDO::FETCH_ASSOC);

if($pre) {
    // Count total items for that pretest
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pretest_items WHERE pretest_id=?");
    $stmt->execute([$pre['pretest_id']]);
    $total_items = $stmt->fetchColumn() ?: 0;

    $pretest_display = $pre['score'] . '/' . $total_items;
    $pretest_completed = $pre['completed_at'];
} else {
    $pretest_display = 'N/A';
    $pretest_completed = 'N/A';
}

    // Get latest assessment attempt per course
$stmt = $pdo->prepare("
    SELECT saa.score, saa.taken_at, a.assessment_id
    FROM student_assessment_attempts saa
    JOIN assessments a ON saa.assessment_id = a.assessment_id
    WHERE saa.student_id=? AND a.course_id=?
    ORDER BY saa.attempt_no DESC LIMIT 1
");
$stmt->execute([$student_id, $course_id]);
$ass = $stmt->fetch(PDO::FETCH_ASSOC);

if($ass) {
    // Count total items for that assessment
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assessment_items WHERE assessment_id=?");
    $stmt->execute([$ass['assessment_id']]);
    $total_items = $stmt->fetchColumn() ?: 0;

    $assessment_display = $ass['score'] . '/' . $total_items;
    $assessment_completed = $ass['taken_at'];
} else {
    $assessment_display = 'N/A';
    $assessment_completed = 'N/A';
}

    echo "<tr>
    <td>{$lesson['lesson_title']}</td>
    <td>{$completed_display}</td>
    <td>{$pretest_display}</td>
    <td>{$pretest_completed}</td>
    <td>{$assessment_display}</td>
    <td>{$assessment_completed}</td>
</tr>";
}

echo "</tbody></table>";