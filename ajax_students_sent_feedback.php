<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once 'db.php';

$studentId = $_SESSION['school_id'];
$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';

// ================= STUDENT → INSTRUCTOR =================
$stmt = $pdo->prepare("
    SELECT f.*, c.course_name, l.lesson_title,
           i.firstname AS instructor_firstname, i.middlename AS instructor_middlename, i.surname AS instructor_surname,
           s.firstname AS student_firstname, s.middlename AS student_middlename, s.surname AS student_surname,
           s.year_level, s.block
    FROM feedback f
    JOIN students s ON s.school_id = f.from_id
    LEFT JOIN courses c ON c.course_id = f.course_id
    LEFT JOIN lessons l ON l.lesson_id = f.lesson_id
    JOIN instructor i ON i.instructor_id = f.to_id
    WHERE f.from_type='STUDENT' AND f.to_type='INSTRUCTOR' AND f.from_id = ?
      AND (c.course_name LIKE ? OR l.lesson_title LIKE ? OR f.message LIKE ?)
    ORDER BY f.created_at DESC
");
$stmt->execute([$studentId, $search, $search, $search]);
$instructorFeedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= STUDENT → ADMIN =================
$stmt2 = $pdo->prepare("
    SELECT f.*, c.course_name,
           a.firstname AS admin_firstname, a.middlename AS admin_middlename, a.surname AS admin_surname, a.position,
           s.firstname AS student_firstname, s.middlename AS student_middlename, s.surname AS student_surname
    FROM feedback f
    JOIN students s ON s.school_id = f.from_id
    LEFT JOIN courses c ON c.course_id = f.course_id
    JOIN admin a ON a.admin_id = f.to_id
    WHERE f.from_type='STUDENT' AND f.to_type='ADMIN' AND f.from_id = ?
      AND (c.course_name LIKE ? OR f.message LIKE ?)
    ORDER BY f.created_at DESC
");
$stmt2->execute([$studentId, $search, $search]);
$adminFeedbacks = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ================= BUILD TABLES =================
$instructorTable = '';
if (count($instructorFeedbacks) > 0) {
    $currentCourseLesson = '';
    foreach ($instructorFeedbacks as $f) {
        $courseLesson = ($f['course_name'] ?? 'No Course') . " | " . ($f['lesson_title'] ?? 'No Lesson');
        if ($courseLesson !== $currentCourseLesson) {
            if ($currentCourseLesson !== '') $instructorTable .= "</tbody></table><br>";
            $instructorTable .= "<strong>Course: {$f['course_name']}</strong> &nbsp;&nbsp; Lesson: {$f['lesson_title']}</strong>";
            $instructorTable .= "<table class='table table-sm table-bordered text-white mt-2'>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Year</th>
                        <th>Block</th>
                        <th>Feedback</th>
                        <th>Status</th>
                        <th>Date Sent</th>
                        <th>Reply</th>
                        <th>Reply Date</th>
                    </tr>
                </thead>
                <tbody>";
            $currentCourseLesson = $courseLesson;
        }

        $studentName = htmlspecialchars($f['surname'].', '.$f['firstname'].' '.$f['middlename']);
        $status = $f['is_read'] ? 'Read' : 'Unread';
        $replyBtn = $f['reply_message'] ? "<button class='btn btn-sm btn-info'>View</button>" : "<button class='btn btn-sm btn-secondary' disabled>View</button>";
        $replyDate = $f['reply_created_at'] ?? '';

        $instructorTable .= "<tr>
            <td>{$studentName}</td>
            <td>{$f['year_level']}</td>
            <td>{$f['block']}</td>
            <td>".htmlspecialchars($f['message'])."</td>
            <td>{$status}</td>
            <td>{$f['created_at']}</td>
            <td>{$replyBtn}</td>
            <td>{$replyDate}</td>
        </tr>";
    }
    $instructorTable .= "</tbody></table>";
} else {
    $instructorTable = "<p>No feedback sent.</p>";
}

// ================= ADMIN TABLE =================
$adminTable = '';
if (count($adminFeedbacks) > 0) {
    $currentCourse = '';
    foreach ($adminFeedbacks as $f) {
        $course = $f['course_name'] ?? 'No Course';
        if ($course !== $currentCourse) {
            if ($currentCourse !== '') $adminTable .= "</tbody></table><br>";
            $adminTable .= "<strong>Course: {$course}</strong>";
            $adminTable .= "<table class='table table-sm table-bordered text-white mt-2'>
                <thead>
                    <tr>
                        <th>Admin Name</th>
                        <th>Position</th>
                        <th>Feedback</th>
                        <th>Status</th>
                        <th>Date Sent</th>
                        <th>Reply</th>
                        <th>Reply Date</th>
                    </tr>
                </thead>
                <tbody>";
            $currentCourse = $course;
        }

        $adminName = htmlspecialchars($f['surname'].', '.$f['firstname'].' '.$f['middlename']);
        $status = $f['is_read'] ? 'Read' : 'Unread';
        $replyBtn = $f['reply_message'] ? "<button class='btn btn-sm btn-info'>View</button>" : "<button class='btn btn-sm btn-secondary' disabled>View</button>";
        $replyDate = $f['reply_created_at'] ?? '';

        $adminTable .= "<tr>
            <td>{$adminName}</td>
            <td>{$f['position']}</td>
            <td>".htmlspecialchars($f['message'])."</td>
            <td>{$status}</td>
            <td>{$f['created_at']}</td>
            <td>{$replyBtn}</td>
            <td>{$replyDate}</td>
        </tr>";
    }
    $adminTable .= "</tbody></table>";
} else {
    $adminTable = "<p>No feedback sent.</p>";
}

// ================= RETURN JSON =================
echo json_encode([
    'instructorTable' => $instructorTable,
    'adminTable' => $adminTable
]);