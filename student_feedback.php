<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Fetch student info
$schoolId = $_SESSION['school_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id=?");
$stmt->execute([$schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

// Save student_id in session for feedback
$_SESSION['student_id'] = $student['student_id'];

// Fetch instructors relevant to student's year/block
$stmtInstructors = $pdo->prepare("
    SELECT DISTINCT i.*
    FROM instructor i
    JOIN courses c ON c.instructor_id = i.instructor_id
    JOIN lessons l ON l.course_id = c.course_id
    WHERE l.year_level=? AND l.block=?
");
$stmtInstructors->execute([$student['year_level'], $student['block']]);
$instructors = $stmtInstructors->fetchAll(PDO::FETCH_ASSOC);

// Admins
$admins = $pdo->query("SELECT * FROM admin")->fetchAll(PDO::FETCH_ASSOC);

// Courses for student's year/block
$stmtCourses = $pdo->prepare("
    SELECT DISTINCT c.*
    FROM courses c
    JOIN lessons l ON l.course_id = c.course_id
    WHERE l.year_level=? AND l.block=?
");
$stmtCourses->execute([$student['year_level'], $student['block']]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Lessons for student's year/block
$stmtLessons = $pdo->prepare("SELECT * FROM lessons WHERE year_level=? AND block=?");
$stmtLessons->execute([$student['year_level'], $student['block']]);
$lessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

// Fetch sent feedback
$studentId = $student['student_id'];
$stmtSent = $pdo->prepare("
    SELECT f.*,
        CASE f.to_type
            WHEN 'INSTRUCTOR' THEN CONCAT(i.surname, ', ', i.firstname)
            WHEN 'ADMIN' THEN CONCAT(a.surname, ', ', a.firstname)
        END AS to_name
    FROM feedback f
    LEFT JOIN instructor i ON f.to_type='INSTRUCTOR' AND f.to_id=i.instructor_id
    LEFT JOIN admin a ON f.to_type='ADMIN' AND f.to_id=a.admin_id
    WHERE f.from_type='STUDENT' AND f.from_id=?
    ORDER BY f.created_at DESC
");
$stmtSent->execute([$studentId]);
$sentFeedback = $stmtSent->fetchAll(PDO::FETCH_ASSOC);

// Fetch received feedback
$stmtReceived = $pdo->prepare("
    SELECT f.*,
        CASE f.from_type
            WHEN 'INSTRUCTOR' THEN CONCAT(i.surname, ', ', i.firstname)
            WHEN 'ADMIN' THEN CONCAT(a.surname, ', ', a.firstname)
        END AS from_name
    FROM feedback f
    LEFT JOIN instructor i ON f.from_type='INSTRUCTOR' AND f.from_id=i.instructor_id
    LEFT JOIN admin a ON f.from_type='ADMIN' AND f.from_id=a.admin_id
    WHERE f.to_type='STUDENT' AND f.to_id=?
    ORDER BY f.created_at DESC
");
$stmtReceived->execute([$studentId]);
$receivedFeedback = $stmtReceived->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Merriweather:wght@700&display=swap" rel="stylesheet">
<style>
:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy), var(--navy2));
    color: var(--white);
    min-height: 100vh;
    padding-bottom: 120px;
    margin: 0;
}
.navbar-custom {
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
.navbar-brand {
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    font-weight: 700;
}
.navbar-custom .nav-link {
    color: rgba(255,255,255,0.85) !important;
    font-weight: 600;
}
.navbar-custom .nav-link:hover, .navbar-custom .nav-link.active-link {
    color: var(--gold) !important;
    text-decoration: underline;
    text-underline-offset: 5px;
    font-weight: 700;
}
.card {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.4);
    border-radius: 14px;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.45);
    margin-bottom: 25px;
}
.card-header {
    background: rgba(255,215,0,0.15);
    border-bottom: 1px solid rgba(255,215,0,0.3);
    font-weight: 700;
    color: var(--gold);
}
textarea, select, input {
    background: rgba(12,32,54,0.75) !important;
    color: #fff !important;
}

/* para sure pati options white */
select option {
    color: #000; /* dropdown list readable (black para makita sa white bg sa dropdown) */
}

/* placeholders */
::placeholder {
    color: rgba(255,255,255,0.7) !important;
}

/* labels */
label {
    color: #fff !important;
}

.feedback-toggle-btn {
    transition: all 0.2s ease;
    color: #fff; /* default text color for outline */
}

.feedback-toggle-btn.active {
    background-color: #FFD700 !important; /* gold highlight */
    color: #071A2A !important;            /* dark text */
    border-color: #FFD700 !important;
}

.feedback-toggle-btn.active:hover {
    background-color: #FFD700 !important; /* keep gold on hover */
    color: #071A2A !important;
    border-color: #FFD700 !important;
}

.feedback-toggle-btn:hover:not(.active) {
    opacity: 0.85; /* slight hover for non-active */
}

.form-control, .form-select {
    color: #fff !important;
}

table {
    color: #fff;
}
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    color: #fff;
    padding: 10px;
    text-align: center;
    border-top: 1px solid rgba(255,215,0,0.2);
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">
    <a class="navbar-brand" href="student_dashboard.php">
        CSTUTORHUB — STUDENT
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="student_about.php">ABOUT</a></li>
        <li class="nav-item"><a class="nav-link" href="student_view_courses.php">COURSES</a></li>
        <li class="nav-item"><a class="nav-link" href="student_progress.php">MY PROGRESS</a></li>
        <li class="nav-item"><a class="nav-link active-link" href="#">FEEDBACK</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">LOGOUT</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4" style="max-width:1000px; margin-bottom:60px;">

<!-- Buttons -->
<div class="d-flex gap-3 mb-4">
    <button class="btn btn-outline-light feedback-toggle-btn" onclick="showSection('send')">FEEDBACK SEND</button>
<button class="btn btn-outline-light feedback-toggle-btn" onclick="showSection('sent')">FEEDBACK SENT</button>
<button class="btn btn-outline-light feedback-toggle-btn" onclick="showSection('received')">FEEDBACK RECEIVED</button>
</div>

<!-- ================= SEND ================= -->
<div id="section-send">

  <!-- STUDENT TO INSTRUCTOR -->
  <div class="card p-3 mb-3">
    <button class="btn btn-warning w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInstructor" aria-expanded="true">
      STUDENT TO INSTRUCTOR
    </button>
    <div class="collapse show mt-3" id="collapseInstructor">
      <form id="feedbackInstructorForm">
        <div class="row g-3">
          <div class="col-md-6">
            <label>To (Instructor)</label>
            <select name="to_instructor" id="instructor" class="form-select">
              <option value="">Select Instructor</option>
              <?php foreach($instructors as $i): ?>
                <option value="<?= $i['instructor_id'] ?>"><?= htmlspecialchars($i['surname'].', '.$i['firstname'].' '.$i['middlename']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label>Course</label>
            <select name="course_id" id="course" class="form-select">
              <option value="">Select Course</option>
              <?php foreach($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>" data-instructor="<?= $c['instructor_id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label>Lesson</label>
            <select name="lesson_id" id="lesson" class="form-select">
              <option value="">Select Lesson</option>
              <?php foreach($lessons as $l): ?>
                <option value="<?= $l['lesson_id'] ?>" data-course="<?= $l['course_id'] ?>"><?= htmlspecialchars($l['lesson_title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label>Feedback</label>
            <textarea name="message" class="form-control" placeholder="Write feedback..." required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-warning mt-2 px-4">Send</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- STUDENT TO ADMIN -->
  <div class="card p-3 mb-3">
    <button class="btn btn-warning w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="false">
      STUDENT TO ADMIN
    </button>
    <div class="collapse mt-3" id="collapseAdmin">
      <form id="feedbackAdminForm">
        <div class="row g-3">
          <div class="col-md-6">
            <label>To (Admin)</label>
            <select name="to_admin" id="admin" class="form-select">
              <option value="">Select Admin</option>
              <?php foreach($admins as $a): ?>
                <option value="<?= $a['admin_id'] ?>"><?= htmlspecialchars($a['surname'].', '.$a['firstname'].' '.$a['middlename'].' - '.$a['position']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label>Course</label>
            <select name="course_id_admin" id="courseAdmin" class="form-select">
              <option value="">Select Course</option>
              <?php foreach($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>" data-instructor="<?= $c['instructor_id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-12">
            <label>Instructor</label>
            <input type="text" id="instructorAdmin" class="form-control" readonly placeholder="Instructor will auto-fill based on course">
          </div>
          <div class="col-12">
            <label>Feedback</label>
            <textarea name="message" class="form-control" placeholder="Write feedback..." required></textarea>
          </div>
          <div class="col-12">
           <button type="submit" class="btn btn-warning mt-2 px-4">Send</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>

<!-- ================= SENT ================= -->
<div id="section-sent" style="display:none;">

  <!-- LIVE SEARCH -->
  <div class="mb-3">
    <input type="text" id="sentSearch" class="form-control" placeholder="Search feedback..." style="max-width:400px;">
  </div>

  <!-- STUDENT → INSTRUCTOR -->
  <h5 class="text-warning">STUDENT → INSTRUCTOR</h5>
<?php
// Group sent feedback to instructors
$instructorFeedbackGrouped = [];
foreach($sentFeedback as $f){
    if($f['to_type'] === 'INSTRUCTOR'){
        $key = ($f['course_id'] ?: '0') . '-' . ($f['lesson_id'] ?: '0');
        $instructorFeedbackGrouped[$key][] = $f;
    }
}
?>

<?php if(empty($instructorFeedbackGrouped)): ?>
    <div class="alert alert-warning">No feedback sent to instructors.</div>
<?php endif; ?>
  <?php foreach($instructorFeedbackGrouped as $key => $group): 
      $first = $group[0]; 
      $courseName = '';
      foreach($courses as $c) if($c['course_id']==$first['course_id']) $courseName=$c['course_name'];
      $lessonTitle = '';
      foreach($lessons as $l) if($l['lesson_id']==$first['lesson_id']) $lessonTitle=$l['lesson_title'];
  ?>
    <div class="mb-2">
      <strong>Course:</strong> <?= htmlspecialchars($courseName ?: '-') ?> 
      &nbsp;&nbsp; <strong>Lesson:</strong> <?= htmlspecialchars($lessonTitle ?: '-') ?>
    </div>
    <table class="table table-striped table-hover text-white feedback-table">
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
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($group as $f): 
          $studentName = htmlspecialchars($student['surname'].', '.$student['firstname']);
        ?>
        <tr>
          <td><?= $studentName ?></td>
          <td><?= htmlspecialchars($student['year_level']) ?></td>
          <td><?= htmlspecialchars($student['block']) ?></td>
          <td><?= htmlspecialchars($f['message']) ?></td>
          <td><?= $f['is_read'] ? 'Read' : 'Unread' ?></td>
          <td><?= $f['created_at'] ?></td>
         <td>
    <button class="btn btn-sm btn-info viewReplyBtn" 
        data-id="<?= $f['feedback_id'] ?>"
        <?= empty($f['reply_message']) ? 'disabled' : '' ?>
        data-reply-message="<?= htmlspecialchars($f['reply_message'] ?? '', ENT_QUOTES) ?>"
        data-reply-from="<?= htmlspecialchars($f['reply_from_type'] ?? '', ENT_QUOTES) ?>"
        data-reply-date="<?= htmlspecialchars($f['reply_created_at'] ?? '', ENT_QUOTES) ?>"
    >View</button>
</td>

</td>
          </td>
          <td><?= $f['reply_created_at'] ?? '-' ?></td>
          <td>
    <button class="btn btn-sm btn-warning editFeedbackBtn" data-id="<?= $f['feedback_id'] ?>" data-message="<?= htmlspecialchars($f['message'], ENT_QUOTES) ?>">Edit</button>
    <button class="btn btn-sm btn-danger deleteFeedbackBtn" data-id="<?= $f['feedback_id'] ?>">Delete</button>
  </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>

  <!-- STUDENT → ADMIN -->
  <h5 class="text-warning mt-4">STUDENT → ADMIN</h5>
  <?php
$adminFeedbackGrouped = [];
foreach($sentFeedback as $f){
    if($f['to_type'] === 'ADMIN'){
        $key = $f['course_id'] ?: '0';
        $adminFeedbackGrouped[$key][] = $f;
    }
}
?>

<?php if(empty($adminFeedbackGrouped)): ?>
    <div class="alert alert-warning">No feedback sent to admins.</div>
<?php endif; ?>

  <?php foreach($adminFeedbackGrouped as $key => $group): 
      $first = $group[0]; 
      $courseName = '';
      foreach($courses as $c) if($c['course_id']==$first['course_id']) $courseName=$c['course_name'];
  ?>
    <div class="mb-2">
      <strong>Course:</strong> <?= htmlspecialchars($courseName ?: '-') ?>
    </div>
    <table class="table table-striped table-hover text-white feedback-table">
      <thead>
        <tr>
          <th>Admin Name</th>
          <th>Position</th>
          <th>Feedback</th>
          <th>Status</th>
          <th>Date Sent</th>
          <th>Reply</th>
          <th>Reply Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($group as $f): 
          $adminName = '';
          $position = '';
          foreach($admins as $a){
              if($a['admin_id']==$f['to_id']){
                  $adminName = $a['surname'].', '.$a['firstname'];
                  $position = $a['position'];
              }
          }
        ?>
        <tr>
          <td><?= htmlspecialchars($adminName) ?></td>
          <td><?= htmlspecialchars($position) ?></td>
          <td><?= htmlspecialchars($f['message']) ?></td>
          <td><?= $f['is_read'] ? 'Read' : 'Unread' ?></td>
          <td><?= $f['created_at'] ?></td>
          <td>
            <button class="btn btn-sm btn-info viewReplyBtn" 
    data-id="<?= $f['feedback_id'] ?>"
    <?= empty($f['reply_message']) ? 'disabled' : '' ?>
    data-reply-message="<?= htmlspecialchars($f['reply_message'] ?? '', ENT_QUOTES) ?>"
    data-reply-from="<?= htmlspecialchars($f['reply_from_type'] ?? '', ENT_QUOTES) ?>"
    data-reply-date="<?= htmlspecialchars($f['reply_created_at'] ?? '', ENT_QUOTES) ?>"
>View</button>
          
</td>
          <td><?= $f['reply_created_at'] ?? '-' ?></td>
          <td>
    <button class="btn btn-sm btn-warning editFeedbackBtn" data-id="<?= $f['feedback_id'] ?>" data-message="<?= htmlspecialchars($f['message'], ENT_QUOTES) ?>">Edit</button>
    <button class="btn btn-sm btn-danger deleteFeedbackBtn" data-id="<?= $f['feedback_id'] ?>">Delete</button>
  </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>
</div>

<!-- ================= RECEIVED ================= -->
<div id="section-received" style="display:none;">

  <!-- LIVE SEARCH -->
  <div class="mb-3" style="max-width: 400px;">
    <input type="text" id="receivedSearch" class="form-control" placeholder="Search received feedback...">
  </div>

  <?php
  // Group received feedback by from_type and date only
  $receivedGrouped = [];
  foreach($receivedFeedback as $f){
      $dateOnly = date('Y-m-d', strtotime($f['created_at']));
      $receivedGrouped[$f['from_type']][$dateOnly][] = $f;
  }
  ?>

  <!-- ================= INSTRUCTOR ================= -->
  <?php if(isset($receivedGrouped['INSTRUCTOR'])): ?>
    <h5 class="text-warning mt-3">FROM INSTRUCTOR</h5>
    <?php foreach($receivedGrouped['INSTRUCTOR'] as $date => $feedbacks): ?>
      <h6>Date received: <?= $date ?></h6>
      <table class="table table-striped table-hover text-white feedback-table">
        <thead>
          <tr>
            <th>Instructor Name</th>
            <th>Course</th>
            <th>Lesson</th>
            <th>Feedback</th>
            <th>Status</th>
            <th>Date Sent</th>
            <th>Reply</th>
            <th>Date Reply Send</th>
            <th>Reply Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($feedbacks as $f):
          $instrName = '';
          foreach($instructors as $i){
            if($i['instructor_id']==$f['from_id']){
              $instrName = $i['surname'].', '.$i['firstname'].' '.$i['middlename'];
            }
          }
          $courseName = '';
          foreach($courses as $c) if($c['course_id']==$f['course_id']) $courseName=$c['course_name'];
          $lessonTitle = '';
          foreach($lessons as $l) if($l['lesson_id']==$f['lesson_id']) $lessonTitle=$l['lesson_title'];
        ?>
        <tr>
          <td><?= htmlspecialchars($instrName) ?></td>
          <td><?= htmlspecialchars($courseName ?: '-') ?></td>
          <td><?= htmlspecialchars($lessonTitle ?: '-') ?></td>
          <td>
            <button class="btn btn-sm btn-success readBtn" 
    data-feedback-id="<?= $f['feedback_id'] ?>"
    data-reply-message="<?= htmlspecialchars($f['message'], ENT_QUOTES) ?>"
>Read</button>
          </td>
          <td class="status-cell"><?= $f['is_read'] ? 'Read' : 'Unread' ?></td>
          <td><?= $f['created_at'] ?></td>
          <td>
            <button class="btn btn-sm btn-warning replyBtn" 
    data-feedback-id="<?= $f['feedback_id'] ?>"
    data-from="<?= htmlspecialchars($instrName) ?>"
    data-reply="<?= htmlspecialchars($f['reply_message'] ?? '', ENT_QUOTES) ?>"
>
<?= $f['reply_message'] ? 'Edit' : 'Reply' ?>
</button>
          </td>
          <td><?= $f['reply_created_at'] ?? '-' ?></td>
          <td><?= $f['reply_is_read'] ? 'Read' : 'Unread' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- ================= ADMIN ================= -->
  <?php if(isset($receivedGrouped['ADMIN'])): ?>
    <h5 class="text-warning mt-4">FROM ADMIN</h5>
    <?php foreach($receivedGrouped['ADMIN'] as $date => $feedbacks): ?>
      <h6>Date received: <?= $date ?></h6>
      <table class="table table-striped table-hover text-white feedback-table">
        <thead>
          <tr>
            <th>Admin Name</th>
            <th>Course</th>
            <th>Feedback</th>
            <th>Status</th>
            <th>Date Sent</th>
            <th>Reply</th>
            <th>Date Reply Send</th>
            <th>Reply Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($feedbacks as $f):
          $adminName = '';
          foreach($admins as $a){
            if($a['admin_id']==$f['from_id']){
              $adminName = $a['surname'].', '.$a['firstname'].' '.$a['middlename'];
            }
          }
          $courseName = '';
          foreach($courses as $c) if($c['course_id']==$f['course_id']) $courseName=$c['course_name'];
        ?>
        <tr>
          <td><?= htmlspecialchars($adminName) ?></td>
          <td><?= htmlspecialchars($courseName ?: '-') ?></td>
          <td>
            <button class="btn btn-sm btn-success readBtn" 
    data-feedback-id="<?= $f['feedback_id'] ?>"
    data-reply-message="<?= htmlspecialchars($f['message'], ENT_QUOTES) ?>"
>Read</button>
          </td>
          <td class="status-cell"><?= $f['is_read'] ? 'Read' : 'Unread' ?></td>
          <td><?= $f['created_at'] ?></td>
          <td>
            <button class="btn btn-sm btn-warning replyBtn" 
    data-feedback-id="<?= $f['feedback_id'] ?>"
    data-from="<?= htmlspecialchars($adminName) ?>"
    data-reply="<?= htmlspecialchars($f['reply_message'] ?? '', ENT_QUOTES) ?>"
>
<?= $f['reply_message'] ? 'Edit' : 'Reply' ?>
</button>
          </td>
          <td><?= $f['reply_created_at'] ?? '-' ?></td>
          <td><?= $f['reply_is_read'] ? 'Read' : 'Unread' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</div>

<!-- REPLY MODAL -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-bottom border-warning">
        <h5 class="modal-title" id="replyModalLabel">Reply Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Message:</strong></p>
        <p id="replyMessage" class="border p-2 rounded bg-secondary"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<footer class="text-center py-3">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle sections
function showSection(type){
    $('#section-send').toggle(type==='send');
    $('#section-sent').toggle(type==='sent');
    $('#section-received').toggle(type==='received');

    // Remove active from all buttons
    $('.feedback-toggle-btn').removeClass('active');

    // Add active to the clicked button
    if(type==='send') $('.feedback-toggle-btn:contains("FEEDBACK SEND")').addClass('active');
    if(type==='sent') $('.feedback-toggle-btn:contains("FEEDBACK SENT")').addClass('active');
    if(type==='received') $('.feedback-toggle-btn:contains("FEEDBACK RECEIVED")').addClass('active');
}

// Filter courses by instructor
$('#instructor').on('change', function(){
    var instId = $(this).val();
    $('#course option').each(function(){
        $(this).toggle($(this).data('instructor') == instId || $(this).val() === '');
    });
    $('#course').val('');
    $('#lesson').val('');
});

// Filter lessons by course
$('#course').on('change', function(){
    var courseId = $(this).val();
    $('#lesson option').each(function(){
        $(this).toggle($(this).data('course') == courseId || $(this).val() === '');
    });
    $('#lesson').val('');
});

// Auto-fill instructor in admin form
$('#courseAdmin').on('change', function(){
    var selected = $(this).find('option:selected');
    var instId = selected.data('instructor');
    var inst = <?= json_encode($instructors) ?>;
    var name = '';
    inst.forEach(i => { if(i.instructor_id == instId) name = i.surname + ', ' + i.firstname; });
    $('#instructorAdmin').val(name);
});

// AJAX send feedback
$('#feedbackInstructorForm').submit(function(e){
    e.preventDefault();
    if(!confirm('Send feedback to instructor?')) return;
    $.post('ajax_student_send_feedback.php', $(this).serialize()+'&to_type=INSTRUCTOR', ()=>{
        alert('Feedback sent!');
        this.reset();
    });
});
$('#feedbackAdminForm').submit(function(e){
    e.preventDefault();
    if(!confirm('Send feedback to admin?')) return;
    $.post('ajax_student_send_feedback.php', $(this).serialize()+'&to_type=ADMIN', ()=>{
        alert('Feedback sent!');
        this.reset();
    });
});

// LIVE SEARCH
$('#sentSearch').on('input', function(){
    var query = $(this).val().toLowerCase();
    $('.feedback-table tbody tr').each(function(){
        var text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(query) > -1);
    });
});
</script>

<script>
$('.viewReplyBtn').on('click', function(){

    var btn = $(this);
    var feedbackId = btn.data('id');

    var msg = btn.data('reply-message');
    var from = btn.data('reply-from');
    var date = btn.data('reply-date');

    // ✅ MARK REPLY AS READ
    $.post('ajax_mark_reply_read.php', { feedback_id: feedbackId });

    $('#replyFrom').text(from);
    $('#replyMessage').text(msg);
    $('#replyDate').text(date);

    var modal = new bootstrap.Modal(document.getElementById('replyModal'));
    modal.show();
});
</script>

<script>
// READ button for received feedback
$(document).on('click', '.readBtn', function(){
    var btn = $(this);
    var feedbackId = btn.data('feedback-id');
    var msg = btn.data('reply-message');

    // Mark as read via AJAX
    $.post('ajax_mark_read.php', { feedback_id: feedbackId }, function(res){
        if(res.success){
            // Update status in table immediately
            btn.closest('tr').find('.status-cell').text('Read');
        }
    }, 'json');

    // Show message in modal
    $('#replyMessage').text(msg);
    var modal = new bootstrap.Modal(document.getElementById('replyModal'));
    modal.show();
});

// REPLY functionality
$('.replyBtn').on('click', function(){
    var feedbackId = $(this).data('feedback-id');
    var from = $(this).data('from');
    var existingReply = $(this).data('reply') || '';

    var isEdit = existingReply.trim() !== '';

    var replyModalHtml = `
    <div class="modal fade" id="dynamicReplyModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
          <div class="modal-header border-bottom border-warning">
            <h5 class="modal-title">
                ${isEdit ? 'Edit Reply' : 'Reply to ' + from}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <textarea id="replyText" class="form-control">${existingReply}</textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-warning" id="sendReplyBtn">
                ${isEdit ? 'Update' : 'Send'}
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>`;

    $('body').append(replyModalHtml);
    var modalEl = document.getElementById('dynamicReplyModal');
    var modal = new bootstrap.Modal(modalEl);
    modal.show();

    $('#sendReplyBtn').on('click', function(){
        var replyMsg = $('#replyText').val().trim();
        if(!replyMsg){
            alert('Reply cannot be empty');
            return;
        }

        $.post('ajax_student_send_reply.php', { 
            feedback_id: feedbackId, 
            reply_message: replyMsg, 
            reply_from_type: 'STUDENT',
            reply_from_id: <?= $student['student_id'] ?>
        }, function(res){
            alert(isEdit ? 'Reply updated!' : 'Reply sent!');
            modal.hide();
            modalEl.remove();
            location.reload();
        });
    });

    $(modalEl).on('hidden.bs.modal', function(){
        modalEl.remove();
    });
});

// LIVE SEARCH
$('#receivedSearch').on('input', function(){
    var query = $(this).val().toLowerCase();
    $('.feedback-table tbody tr').each(function(){
        var text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(query) > -1);
    });
});

// ===================== EDIT FEEDBACK =====================
$('.editFeedbackBtn').on('click', function(){
    var feedbackId = $(this).data('id');
    var message = $(this).data('message');

    var editModalHtml = `
    <div class="modal fade" id="editFeedbackModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
          <div class="modal-header border-bottom border-warning">
            <h5 class="modal-title">Edit Feedback</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <textarea id="editFeedbackText" class="form-control">${message}</textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-warning" id="saveEditBtn">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>`;
    
    $('body').append(editModalHtml);
    var modalEl = document.getElementById('editFeedbackModal');
    var modal = new bootstrap.Modal(modalEl);
    modal.show();

    $('#saveEditBtn').on('click', function(){
        var newMsg = $('#editFeedbackText').val().trim();
        if(!newMsg) { alert('Message cannot be empty'); return; }

        $.post('ajax_edit_feedback.php', { feedback_id: feedbackId, message: newMsg }, function(res){
            if(res.success){
                alert('Feedback updated successfully!');
                modal.hide();
                modalEl.remove();
                location.reload();
            } else {
                alert('Error updating feedback.');
            }
        }, 'json');
    });

    $(modalEl).on('hidden.bs.modal', function(){ modalEl.remove(); });
});

// ===================== DELETE FEEDBACK =====================
$('.deleteFeedbackBtn').on('click', function(){
    var feedbackId = $(this).data('id');
    if(!confirm('Are you sure you want to delete this feedback?')) return;

    $.post('ajax_delete_feedback.php', { feedback_id: feedbackId }, function(res){
        if(res.success){
            alert('Feedback deleted successfully!');
            location.reload();
        } else {
            alert('Error deleting feedback.');
        }
    }, 'json');
});
</script>
</body>
</html>