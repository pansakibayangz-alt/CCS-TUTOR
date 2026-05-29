<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// GET LOGGED ADMIN
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM admin WHERE username=?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_id = $admin['admin_id'];

$_SESSION['admin_id'] = $admin_id;

// FETCH INSTRUCTORS
$instructors = $pdo->query("SELECT * FROM instructor ORDER BY surname ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background: linear-gradient(180deg,#071A2A,#0B2540);
    color:#fff;
    font-family:Poppins;
}

.card-box{
    background: rgba(255,255,255,0.08);
    padding:20px;
    border-radius:15px;
    margin-bottom:60px;
}

/* LIVE TIME BAR */
#liveDateTimeBar{
    width:100%;
    background:rgba(0,0,0,0.35);
    backdrop-filter:blur(6px);
    padding:10px 0;
    text-align:center;
    color:var(--gold);
    font-weight:700;
    border-bottom:1px solid rgba(255,215,0,0.25);
}

/* Footer */
footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(10px);
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
    border-top: 1px solid rgba(255,255,255,0.3);
}
/* NAVBAR */
.navbar-custom{
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}
.navbar-brand{
    font-family: 'Merriweather', serif;
    font-size: 1.25rem;
    color: var(--gold) !important;
    letter-spacing: 0.6px;
    font-weight:700;
}
h3{
    font-family:'Merriweather', serif;
    color: var(--gold);
    letter-spacing:0.4px;
}
.page-title-shift {
    margin-left: 25px;  /* adjust this number if you want more/less */
}

.navbar-custom .nav-link{
    color: rgba(255,255,255,0.9);
    font-weight:600;
    text-transform:uppercase;
    font-size:0.83rem;
}
.navbar-custom .nav-link:hover{
    color: var(--gold);
    text-decoration:underline;
}
/* MAKE ACTIVE NAV LINK YELLOW */
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
}

.link-gold { color: var(--gold); font-weight:700; }
.link-gold:hover{ text-decoration:underline; color:#ffea85; }

button{font-weight:bold;}

footer { position: fixed; bottom:0; width:100%; background: rgba(0,0,0,0.55); backdrop-filter: blur(10px); color:white; text-align:center; font-weight:600; border-top:1px solid rgba(255,255,255,0.3); padding:10px;}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
<div class="container-fluid" style="max-width:1200px; margin:0 auto;">
    <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px;">
        <img src="ccs.png" alt="CCS Logo" style="height:36px;">
        <span>CSTUTORHUB — ADMIN</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
        <svg width="28" height="28" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            <li class="nav-item"><a class="nav-link" href="admin_about.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_manage_instructors.php">Instructors</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Students</a></li>
            <li class="nav-item"><a class="nav-link active" href="admin_feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link link-gold" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container mt-4">

<!-- BUTTONS -->
<div class="mb-3">
<button class="btn btn-warning" onclick="showSection('send')">FEEDBACK SEND</button>
<button class="btn btn-info" onclick="showSection('sent')">FEEDBACK SENT</button>
<button class="btn btn-light" onclick="showSection('received')">FEEDBACK RECEIVED</button>
</div>

<!-- ================= SEND ================= -->
<div id="section-send">

<!-- ================= ADMIN → STUDENT ================= -->
<div class="card-box">

<h5>
<button class="btn btn-outline-warning w-100 text-start" data-bs-toggle="collapse" data-bs-target="#adminStudentCollapse">
ADMIN → STUDENT
</button>
</h5>

<div id="adminStudentCollapse" class="collapse show">

<form id="adminToStudent">

<div class="row">

<div class="col-md-3">
<label>Year Level</label>
<select id="year" class="form-control">
<option value="">Select Year</option>
</select>
</div>

<div class="col-md-3">
<label>Block</label>
<select id="block" class="form-control">
<option>Select Block</option>
</select>
</div>

<div class="col-md-6">
<label>To</label>
<select id="student" name="to_id" class="form-control">
<option>Select Student</option>
</select>
</div>

<div class="col-md-6 mt-2">
<label>Course</label>
<select id="course" name="course_id" class="form-control">
<option>Select Course</option>
</select>
</div>

<div class="col-md-6 mt-2">
<label>Lesson</label>
<select id="lesson" name="lesson_id" class="form-control">
<option>Select Lesson</option>
</select>
</div>

<div class="col-12 mt-2">
<label>Feedback</label>
<textarea name="message" class="form-control"></textarea>
</div>

<div class="col-12 mt-2">
<button class="btn btn-warning">SEND</button>
</div>

</div>
</form>
</div>
</div>

<!-- ================= ADMIN → INSTRUCTOR ================= -->
<div class="card-box">

<h5>
<button class="btn btn-outline-info w-100 text-start" data-bs-toggle="collapse" data-bs-target="#adminInstructorCollapse">
ADMIN → INSTRUCTOR
</button>
</h5>

<div id="adminInstructorCollapse" class="collapse">

<form id="adminToInstructor">

<label>To (Instructor)</label>
<select name="to_id" id="instructor" class="form-control mb-2">
<option>Select Instructor</option>
<?php foreach($instructors as $i): ?>
<option value="<?= $i['instructor_id'] ?>">
<?= $i['surname'] ?>, <?= $i['firstname'] ?> <?= $i['middlename'] ?>
</option>
<?php endforeach; ?>
</select>

<label>Course</label>
<select name="course_id" id="courseInstructor" class="form-control mb-2">
<option>Select Course</option>
</select>

<label>Lesson</label>
<select name="lesson_id" id="lessonInstructor" class="form-control mb-2">
<option>Select Lesson</option>
</select>

<label>Feedback</label>
<textarea name="message" class="form-control mb-2"></textarea>

<button class="btn btn-warning">SEND</button>

</form>
</div>
</div>

</div>

<!-- ================= SENT ================= -->
<div id="section-sent" style="display:none;">

<!-- 🔍 SEARCH BAR -->
<div class="mb-3">
<input type="text" id="searchSent" class="form-control" placeholder="Search feedback..." style="max-width:350px;">
</div>

<!-- ================= TO STUDENT ================= -->
<div class="card-box">

<h6 class="text-warning">TO STUDENT</h6>

<div class="d-flex justify-content-between mb-2">
<div><strong>Course:</strong> <span id="studentCourse">-</span></div>
<div><strong>Lesson:</strong> <span id="studentLesson">-</span></div>
</div>

<div class="table-responsive">
<table class="table table-dark table-bordered table-sm">
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
<tbody id="studentSentTable"></tbody>
</table>
</div>

</div>

<!-- ================= TO INSTRUCTOR ================= -->
<div class="card-box">

<h6 class="text-info">TO INSTRUCTOR</h6>

<div class="d-flex justify-content-between mb-2">
<div><strong>Course:</strong> <span id="instructorCourse">-</span></div>
<div><strong>Lesson:</strong> <span id="instructorLesson">-</span></div>
</div>

<div class="table-responsive">
<table class="table table-dark table-bordered table-sm">
<thead>
<tr>
<th>Instructor Name</th>
<th>Feedback</th>
<th>Status</th>
<th>Date Sent</th>
<th>Reply</th>
<th>Reply Date</th>
</tr>
</thead>
<tbody id="instructorSentTable"></tbody>
</table>
</div>

</div>

</div>

<!-- ================= RECEIVED ================= -->
<div id="section-received" style="display:none;">

<!-- 🔍 SEARCH BAR -->
<div class="mb-3">
<input type="text" id="searchReceived" class="form-control" placeholder="Search received..." style="max-width:350px;">
</div>

<!-- ================= FROM STUDENT ================= -->
<div class="card-box">

<h6 class="text-warning">FROM STUDENT</h6>

<div><strong>Date Received:</strong> <span id="receivedStudentDate">-</span></div>

<div class="table-responsive mt-2">
<table class="table table-dark table-bordered table-sm">
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
<th>Reply Status</th>
</tr>
</thead>
<tbody id="receivedStudentTable"></tbody>
</table>
</div>

</div>

<!-- ================= FROM INSTRUCTOR ================= -->
<div class="card-box">

<h6 class="text-info">FROM INSTRUCTOR</h6>

<div><strong>Date Received:</strong> <span id="receivedInstructorDate">-</span></div>

<div class="table-responsive mt-2">
<table class="table table-dark table-bordered table-sm">
<thead>
<tr>
<th>Instructor Name</th>
<th>Feedback</th>
<th>Status</th>
<th>Date Sent</th>
<th>Reply</th>
<th>Reply Date</th>
<th>Reply Status</th>
</tr>
</thead>
<tbody id="receivedInstructorTable"></tbody>
</table>
</div>

</div>
</div>

</div>

<!-- REPLY MODAL -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="replyModalLabel">Send Reply</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <textarea id="replyModalText" class="form-control" rows="4" placeholder="Type your reply here..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="sendReplyModalBtn">Send Reply</button>
      </div>
    </div>
  </div>
</div>

<footer>Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong></footer>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

// ================= TOGGLE =================
function showSection(type){
    $('#section-send').toggle(type==='send');
    $('#section-sent').toggle(type==='sent');
    $('#section-received').toggle(type==='received');
}

// ================= LOAD YEAR =================
$(document).ready(function(){
    $.get('ajax_admin_get_years.php', function(res){
        $('#year').html(res);
    });
});

// ================= YEAR → BLOCK =================
$('#year').change(function(){
    $.get('ajax_admin_get_blocks.php',{year:$(this).val()},function(res){
        $('#block').html(res);
    });
});

// ================= BLOCK → STUDENTS =================
$('#block').change(function(){
    $.get('ajax_admin_get_students.php',{
        year: $('#year').val(),
        block: $(this).val()
    },function(res){
        $('#student').html(res);
    });
});

// ================= STUDENT → COURSE =================
$('#student').change(function(){
    $.get('ajax_admin_get_courses_filtered.php',{
        student_id: $(this).val(),
        year: $('#year').val(),
        block: $('#block').val()
    },function(res){
        $('#course').html(res);
    });
});

// ================= COURSE → LESSON =================
$('#course').change(function(){
    $.get('ajax_admin_get_lessons_filtered.php',{
        course_id: $(this).val(),
        year: $('#year').val(),
        block: $('#block').val()
    },function(res){
        $('#lesson').html(res);
    });
});

// ================= SEND ADMIN → STUDENT =================
$('#adminToStudent').submit(function(e){
    e.preventDefault();

    if(!confirm('Are you sure you want to send this feedback?')){
        this.reset(); // reset form if cancel
        return;
    }

    $.post('ajax_admin_send_feedback.php',
        $(this).serialize()+'&from_type=ADMIN&to_type=STUDENT',
        function(res){

            alert('Sent successfully!');

            $('#adminToStudent')[0].reset(); // reset after send
        }
    );
});

// ================= INSTRUCTOR COURSE =================
$('#instructor').change(function(){
    $.get('ajax_admin_get_courses_by_instructor.php',{
        instructor_id: $(this).val()
    },function(res){
        $('#courseInstructor').html(res);
    });
});

// ================= COURSE → LESSON =================
$('#courseInstructor').change(function(){
    $.get('ajax_admin_get_lessons_by_course.php',{
        course_id: $(this).val()
    },function(res){
        $('#lessonInstructor').html(res);
    });
});

// ================= SEND ADMIN → INSTRUCTOR =================
$('#adminToInstructor').submit(function(e){
    e.preventDefault();

    if(!confirm('Are you sure you want to send this feedback?')){
        this.reset(); // reset form if cancel
        return;
    }

    $.post('ajax_admin_send_feedback.php',
        $(this).serialize()+'&from_type=ADMIN&to_type=INSTRUCTOR',
        function(res){

            alert('Sent successfully!');

            $('#adminToInstructor')[0].reset();
        }
    );
});

// ================= LOAD SENT FEEDBACK =================
function loadSentFeedback(){
    $.get('ajax_admin_get_sent_feedback.php', function(res){
        let data = JSON.parse(res);

        // STUDENT TABLE
        let studentHTML = '';
        data.student.forEach(f => {

            let replyBtn = `<button class="btn btn-info btn-sm view-reply-btn" 
        data-id="${f.feedback_id}" 
        ${(!f.reply_message || f.reply_is_read == 1) ? 'disabled' : ''}>
        VIEW
    </button>`;

            studentHTML += `
            <tr>
                <td>${f.name}</td>
                <td>${f.year}</td>
                <td>${f.block}</td>
                <td>${f.message}</td>
                <td>${f.is_read == 1 ? 'Read' : 'Unread'}</td>
                <td>${f.created_at}</td>
                <td>${replyBtn}</td>
                <td>${f.reply_created_at ?? '-'}</td>
            </tr>`;
        });

        $('#studentSentTable').html(studentHTML);

        // INSTRUCTOR TABLE
        let instructorHTML = '';
        data.instructor.forEach(f => {

            let replyBtn = `<button class="btn btn-info btn-sm view-reply-btn" 
        data-id="${f.feedback_id}" 
        ${(!f.reply_message || f.reply_is_read == 1) ? 'disabled' : ''}>
        VIEW
    </button>`;

            instructorHTML += `
            <tr>
                <td>${f.name}</td>
                <td>${f.message}</td>
                <td>${f.is_read == 1 ? 'Read' : 'Unread'}</td>
                <td>${f.created_at}</td>
                <td>${replyBtn}</td>
                <td>${f.reply_created_at ?? '-'}</td>
            </tr>`;
        });

        $('#instructorSentTable').html(instructorHTML);

        // HEADER INFO
        $('#studentCourse').text(data.student_course ?? '-');
        $('#studentLesson').text(data.student_lesson ?? '-');

        $('#instructorCourse').text(data.instructor_course ?? '-');
        $('#instructorLesson').text(data.instructor_lesson ?? '-');
    });
}

// AUTO LOAD WHEN CLICK SENT
$('button:contains("FEEDBACK SENT")').click(function(){
    loadSentFeedback();
});

// ================= VIEW REPLY BUTTON =================
$(document).on('click', '.view-reply-btn', function() {
    let feedbackId = $(this).data('id');
    let button = $(this);

    // AJAX to get the reply message and mark as read
    $.post('ajax_admin_get_reply.php', { feedback_id: feedbackId }, function(res){
        let data = JSON.parse(res);

        if(data.status === 'success'){
            alert(data.reply_message);

            // Mark the button as disabled after viewing
            button.prop('disabled', true);

            // Reload the sent table to update reply_is_read status
            loadSentFeedback();
        } else {
            alert('Failed to retrieve reply.');
        }
    });
});

// ================= SEARCH =================
$('#searchSent').on('keyup', function(){
    let value = $(this).val().toLowerCase();

    $('#studentSentTable tr, #instructorSentTable tr').filter(function(){
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

// ================= LOAD RECEIVED =================
function loadReceivedFeedback(){
    $.get('ajax_admin_get_received_feedback.php', function(res){
        let data = JSON.parse(res);

        // ================= STUDENT =================
        let studentHTML = '';

        data.student.forEach(f => {

            let readBtn = `<button class="btn btn-info btn-sm" onclick="viewMessage('${f.message}', ${f.feedback_id})">Read</button>`;

            let replyBtn = `
                <button class="btn btn-warning btn-sm" onclick="showReplyBox(${f.feedback_id})">Edit</button>
                <div id="replyBox${f.feedback_id}" style="display:none;margin-top:5px;">
                    <textarea id="replyText${f.feedback_id}" class="form-control mb-1"></textarea>
                    <button class="btn btn-success btn-sm" onclick="sendReply(${f.feedback_id})">REPLY</button>
                    <button class="btn btn-secondary btn-sm" onclick="hideReplyBox(${f.feedback_id})">CANCEL</button>
                </div>
            `;

            studentHTML += `
            <tr>
                <td>${f.name}</td>
                <td>${f.year}</td>
                <td>${f.block}</td>
                <td>${readBtn}</td>
                <td>${f.is_read == 1 ? 'Read' : 'Unread'}</td>
                <td>${f.created_at.split(' ')[0]}</td>
                <td>${replyBtn}</td>
                <td>${f.reply_created_at ?? '-'}</td>
                <td>${f.reply_is_read == 1 ? 'Read' : 'Unread'}</td>
            </tr>`;
        });

        $('#receivedStudentTable').html(studentHTML);
        $('#receivedStudentDate').text(data.student_date ?? '-');


        // ================= INSTRUCTOR =================
        let instructorHTML = '';

        data.instructor.forEach(f => {

            let readBtn = `<button class="btn btn-info btn-sm" onclick="viewMessage('${f.message}', ${f.feedback_id})">Read</button>`;

            let replyBtn = `<button class="btn btn-warning btn-sm" onclick="showReplyBox(${f.feedback_id})">Reply</button>`;

            instructorHTML += `
            <tr>
                <td>${f.name}</td>
                <td>${readBtn}</td>
                <td>${f.is_read == 1 ? 'Read' : 'Unread'}</td>
                <td>${f.created_at.split(' ')[0]}</td>
                <td>${replyBtn}</td>
                <td>${f.reply_created_at ?? '-'}</td>
                <td>${f.reply_is_read == 1 ? 'Read' : 'Unread'}</td>
            </tr>`;
        });

        $('#receivedInstructorTable').html(instructorHTML);
        $('#receivedInstructorDate').text(data.instructor_date ?? '-');
    });
}

// AUTO LOAD
$('button:contains("FEEDBACK RECEIVED")').click(function(){
    loadReceivedFeedback();
});

// ================= VIEW MESSAGE =================
function viewMessage(message, id){
    alert(message);

    // mark as read
    $.post('ajax_admin_mark_read.php',{feedback_id:id}, function(res){
        let data = JSON.parse(res);
        if(data.status === 'success'){
            loadReceivedFeedback(); // reload table to show updated status
        } else {
            alert('Failed to mark as read');
        }
    });
}

// ================= REPLY =================
let currentFeedbackId = null;

// SHOW MODAL
function showReplyBox(id){
    currentFeedbackId = id;
    $('#replyModalText').val(''); // clear previous text
    var myModal = new bootstrap.Modal(document.getElementById('replyModal'));
    myModal.show();
}

// SEND REPLY
$('#sendReplyModalBtn').click(function(){
    let msg = $('#replyModalText').val();

    if(msg.trim() === ''){
        alert('Reply cannot be empty');
        return;
    }

    $.post('ajax_admin_send_reply.php', {
        feedback_id: currentFeedbackId,
        message: msg
    }, function(){
        alert('Reply sent!');
        loadReceivedFeedback();

        // Hide modal
        var modalEl = document.getElementById('replyModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    });
});


// ================= SEARCH RECEIVED =================
$('#searchReceived').on('keyup', function(){
    let value = $(this).val().toLowerCase();

    $('#receivedStudentTable tr, #receivedInstructorTable tr').filter(function(){
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});
</script>
<script>
// LIVE DATE & TIME
function updateDateTime(){
    document.getElementById("liveDateTimeBar").innerText =
        new Date().toLocaleString("en-PH", { 
            timeZone: "Asia/Manila",
            dateStyle: "full",
            timeStyle: "medium"
        });
}
setInterval(updateDateTime,1000);
updateDateTime();
</script>

</body>
</html>