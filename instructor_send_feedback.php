<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
$instructor_id = $instructor['instructor_id'];

$_SESSION['instructor_id'] = $instructor_id;

// students
$students = $pdo->prepare("
SELECT DISTINCT s.*
FROM students s
JOIN lessons l 
    ON l.year_level = s.year_level 
    AND l.block = s.block
JOIN courses c 
    ON c.course_id = l.course_id 
    AND c.instructor_id = ?
WHERE s.instructor_id = ?
");
$students->execute([$instructor_id, $instructor_id]);
$studentsList = $students->fetchAll(PDO::FETCH_ASSOC);

// admins
$admins = $pdo->query("SELECT * FROM admin")->fetchAll(PDO::FETCH_ASSOC);

// courses
$courses = $pdo->prepare("SELECT * FROM courses WHERE instructor_id=?");
$courses->execute([$instructor_id]);
$coursesList = $courses->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Instructor Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

/* VARIABLES */
:root{
    --navy: #0b2b4a;
    --navy-2: #08304f;
    --gold: #FFD700;
    --muted: rgba(255,255,255,0.9);
}

/* BODY */
body{
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, #071A2A 0%, #0B2540 100%);
    color: var(--muted);
    margin:0;
    min-height:100vh;
    overflow-x:hidden;
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
.navbar-custom .nav-link.active {
    color: var(--gold) !important;
    font-weight: 700;
    text-decoration: underline;
}
.link-gold { color: var(--gold); font-weight:700; }
.link-gold:hover{ text-decoration:underline; color:#ffea85; }

/* CARD STYLE */
.card-progress {
    border: 2px solid rgba(255, 215, 0, 0.9);
    border-radius: 16px;
    color: #fff;
    font-weight: 600;
    transition: 0.3s ease;
    background: rgba(12, 32, 54, 0.70);
    backdrop-filter: blur(6px);
    box-shadow: 0 0 22px rgba(255, 215, 0, 0.45);
}
.card-progress:hover {
    transform: translateY(-6px) scale(1.03);
    box-shadow: 0 0 32px rgba(255, 215, 0, 0.75);
    border-color: rgba(255, 215, 0, 1);
}
.card-progress h5 {
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--gold);
    text-shadow: 0 0 6px rgba(255, 215, 0, 0.7);
}
.card-progress p { font-size: 0.95rem; opacity: 0.95; }

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



/* FORMS */
textarea, select{background: rgba(12,32,54,0.75); color:#fff;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid" style="max-width:1200px; margin:0 auto;">
    <a class="navbar-brand d-flex align-items-center gap-2" href="instructor_dashboard.php">
        <img src="jrmsu.png" alt="JRMSU Logo" style="height:36px;">
        <img src="ccs.png" alt="CCS Logo" style="height:36px;">
        <span>CSTUTORHUB — INSTRUCTOR</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="instructor_about.php">ABOUT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_students.php">STUDENTS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_lessons.php">LESSONS</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_pretest.php">PRE-TEST</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_manage_assessment.php">ASSESSMENT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_view_progress.php">STUDENT PROGRESS</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">FEEDBACK</a></li>
        <li class="nav-item"><a class="nav-link link-gold" href="logout.php">LOGOUT</a></li>
        <li class="nav-item"><a class="nav-link" href="instructor_dashboard.php" style="font-size:25px;">🏠</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- LIVE DATE & TIME -->
<div id="liveDateTimeBar">Loading date & time...</div>

<div class="container py-4" style="max-width:1000px; margin-bottom:60px;">

<div id="successMsg" class="alert alert-success text-center" style="display:none;">
    FEEDBACK SENT SUCCESSFULLY!
</div>

<!-- BUTTONS -->
<div class="d-flex gap-3 mb-4">
    <button class="btn btn-warning" onclick="showSection('send')">FEEDBACK SEND</button>
    <button class="btn btn-info text-dark" onclick="showSection('sent')">FEEDBACK SENT</button>
    <button class="btn btn-outline-light" onclick="showSection('received')">FEEDBACK RECEIVED</button>
</div>

<!-- ================= SEND ================= -->
<div id="section-send">

<hr>

<!-- COLLAPSIBLE -->
<div class="accordion" id="sendAccordion">

<!-- STUDENT -->
<div class="accordion-item">
<h2 class="accordion-header">
<button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#studentCollapse">
Instructor → Student
</button>
</h2>

<div id="studentCollapse" class="accordion-collapse collapse show">
<div class="accordion-body">

<form id="feedbackStudentForm">
<div class="row g-3">

<div class="col-md-3">
<label>Year Level</label>
<select id="year" class="form-select">
<option value="">Select Year</option>
<?php foreach(array_unique(array_column($studentsList,'year_level')) as $y) echo "<option>$y</option>"; ?>
</select>
</div>

<div class="col-md-3">
<label>Block</label>
<select id="block" class="form-select">
<option>Select Block</option>
</select>
</div>

<div class="col-md-6">
<label>To (Student)</label>
<select name="to_student" id="student" class="form-select">
<option>Select Student</option>
</select>
</div>

<div class="col-md-6">
<label>Course</label>
<select name="course_id" id="course" class="form-select">
<option value="">Select Course</option>
</select>
</div>

<div class="col-md-6">
<label>Lesson</label>
<select name="lesson_id" id="lesson" class="form-select">
<option>Select Lesson</option>
</select>
</div>

<div class="col-12">
<label>Feedback</label>
<textarea name="message" class="form-control" placeholder="Write feedback..."></textarea>
</div>

<div class="col-12">
    <button id="studentSendBtn" class="btn btn-warning mt-2">Send</button>
</div>

</div>
</form>

</div>
</div>
</div>

<!-- ADMIN -->
<div class="accordion-item">
<h2 class="accordion-header">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#adminCollapse">
Instructor → Admin
</button>
</h2>

<div id="adminCollapse" class="accordion-collapse collapse">
<div class="accordion-body">

<form id="feedbackAdminForm">

<label>To (Admin)</label>
<select name="to_admin" class="form-select mb-2">
<option>Select Admin</option>
<?php 
foreach($admins as $a){
echo "<option value='{$a['admin_id']}'>
{$a['surname']}, {$a['firstname']} {$a['middlename']} - {$a['position']}
</option>";
}
?>
</select>

<label>Course</label>
<select name="course_id" class="form-select mb-2">
<option>Select Course</option>
<?php foreach($coursesList as $c) echo "<option value='{$c['course_id']}'>{$c['course_name']}</option>"; ?>
</select>

<label>Feedback</label>
<textarea name="message" class="form-control mb-2" placeholder="Write feedback..."></textarea>

<button class="btn btn-warning">Send</button>
</form>

</div>
</div>
</div>

</div>

</div>

<!-- ================= SENT ================= -->
<div id="section-sent" style="display:none;">
<input type="text" id="searchSentFull" class="form-control mb-3 w-50" placeholder="Search sent feedback...">

<h5 class="text-warning">TO STUDENT/S</h5>
<div id="sentStudentTable"></div> <!-- JS will generate tables -->

<hr>

<h5 class="text-warning">TO ADMIN/S</h5>
<div id="sentAdminTable"></div> <!-- JS will generate tables -->
</div>

<!-- RECEIVED FEEDBACK SECTION (replace your existing #section-received content) -->
<div id="section-received" style="display:none;">
    <input type="text" id="searchReceived" class="form-control mb-3 w-50" placeholder="Search received feedback...">

    <h5 class="text-warning">FROM STUDENT/S</h5>
    <div id="receivedStudentTable"></div>

    <hr>

    <h5 class="text-warning">FROM ADMIN/S</h5>
    <div id="receivedAdminTable"></div>
</div>

<!-- REPLY MODAL (inline version) -->
<div id="reply-modal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#071A2A; color:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.5); z-index:1050;">
    <h3>Reply</h3>
    <textarea id="reply-message" rows="4" style="width:100%; background:#0B2540; color:#fff;"></textarea>
    <input type="hidden" id="reply-feedback-id">
    <input type="hidden" id="reply-to-type">
    <div class="mt-2 d-flex gap-2">
        <button id="send-reply-btn" class="btn btn-success">Send Reply</button>
        <button class="btn btn-secondary" onclick="closeReplyModal()">Cancel</button>
    </div>
</div>

<!-- FOOTER -->
<footer class="text-center py-3">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const instructor_id = <?= $instructor_id ?>;

// ================= TOGGLE =================
function showSection(type){
    $('#section-send').toggle(type==='send');
    $('#section-sent').toggle(type==='sent');
    $('#section-received').toggle(type==='received');

    if(type==='sent') loadSentFull();
}


// ================= HELPERS =================
function disableAllExceptYear(){
    $('#block, #student, #course, #lesson, textarea[name="message"], #studentSendBtn')
        .prop('disabled', true);

    $('#year').prop('disabled', false);
}

function resetStudentForm(){
    $('#year').val('');
    $('#block').html('<option>Select Block</option>');
    $('#student').html('<option>Select Student</option>');
    $('#course').html('<option>Select Course</option>');
    $('#lesson').html('<option>Select Lesson</option>');
    $('textarea[name="message"]').val('');

    disableAllExceptYear();
}


// ================= INITIAL =================
$(document).ready(function(){
    resetStudentForm();
    $('#year').prop('disabled', false);

    // ADMIN SEND INIT
    $('#feedbackAdminForm button').prop('disabled', true);
});


// ================= STEP 1: YEAR =================
$('#year').change(function(){
    let year = $(this).val();

    disableAllExceptYear();
    $('#year').val(year);

    if(year){
        $('#block').prop('disabled', false);

        $.get('ajax_get_blocks.php',{year},function(res){
            $('#block').html(res);
        });
    }
});


// ================= STEP 2: BLOCK =================
$('#block').change(function(){
    let year = $('#year').val();
    let block = $(this).val();

    $('#student, #course, #lesson, textarea[name="message"]')
        .prop('disabled', true);

    $('#student').html('<option>Select Student</option>');

    if(block){
        $('#student').prop('disabled', false);

        $.get('ajax_get_students.php',{year,block},function(res){
            $('#student').html(res);
        });
    }
});


// ================= STEP 3: STUDENT =================
$('#student').change(function(){
    let student_id = $(this).val();

    $('#course').html('<option>Select Course</option>').prop('disabled', true);
    $('#lesson').html('<option>Select Lesson</option>').prop('disabled', true);
    $('textarea[name="message"]').prop('disabled', true);

    if(student_id){
        $('#course').prop('disabled', false);

        $.get('ajax_get_courses_filtered.php',{
            student_id,
            year: $('#year').val(),
            block: $('#block').val()
        },function(res){

            if(res.trim() === '<option>Select Course</option>' || res.trim()===''){
                alert('No available course for this selection.');
                $('#course').prop('disabled', true);
                return;
            }

            $('#course').html(res);
        });
    }
});


// ================= STEP 4: COURSE =================
$('#course').change(function(){
    let course_id = $(this).val();

    $('#lesson').html('<option>Select Lesson</option>').prop('disabled', true);
    $('textarea[name="message"]').prop('disabled', true);

    if(course_id){
        $('#lesson').prop('disabled', false);

        $.get('ajax_get_lessons_filtered.php',{
            course_id,
            year: $('#year').val(),
            block: $('#block').val()
        },function(res){

            if(res.trim() === '<option>Select Lesson</option>' || res.trim()===''){
                alert('No available lesson for this selection.');
                $('#lesson').prop('disabled', true);
                return;
            }

            $('#lesson').html(res);
        });
    }
});


// ================= STEP 5: LESSON =================
$('#lesson').change(function(){
    if($(this).val()){
        $('textarea[name="message"]').prop('disabled', false);
    }
});


// ================= STEP 6: MESSAGE =================
$('textarea[name="message"]').on('input',function(){
    let filled =
        $('#year').val() &&
        $('#block').val() &&
        $('#student').val() &&
        $('#course').val() &&
        $('#lesson').val() &&
        $(this).val().trim() !== '';

    $('#studentSendBtn').prop('disabled', !filled);
});


// ================= SEND STUDENT =================
$('#feedbackStudentForm').submit(function(e){
    e.preventDefault();

    if(!confirm('Are you sure you want to send this feedback?')){
        resetStudentForm();
        return;
    }

    $.post('ajax_send_feedback.php',
        $(this).serialize()+'&from_type=INSTRUCTOR&to_type=STUDENT',
        function(){

            $('#successMsg').fadeIn();

            setTimeout(()=>{
                $('#successMsg').fadeOut();
            },3000);

            resetStudentForm();
        }
    );
});


// ================= ADMIN VALIDATION =================
$('#feedbackAdminForm select, #feedbackAdminForm textarea').on('change input', function(){

    let admin = $('#feedbackAdminForm select[name="to_admin"]').val();
    let course = $('#feedbackAdminForm select[name="course_id"]').val();
    let message = $('#feedbackAdminForm textarea[name="message"]').val().trim();

    let filled = admin && course && message !== '';

    $('#feedbackAdminForm button').prop('disabled', !filled);
});


// ================= SEND ADMIN =================
$('#feedbackAdminForm').submit(function(e){
    e.preventDefault();

    if(!confirm('Are you sure you want to send this feedback?')){
        return; // STOP if cancel
    }

    $.post('ajax_send_feedback.php',
        $(this).serialize()+'&from_type=INSTRUCTOR&to_type=ADMIN',
        ()=>{

            $('#successMsg')
                .text('FEEDBACK SENT SUCCESSFULLY!')
                .fadeIn();

            setTimeout(()=>{
                $('#successMsg').fadeOut();
            },3000);

            this.reset();

            // reset states
            $('select[name="course_id"]').prop('disabled', true);
            $('textarea[name="message"]').prop('disabled', true);
            $('#feedbackAdminForm button').prop('disabled', true);
        }
    );
});

// DISABLE COURSE INIT
$('select[name="course_id"]').prop('disabled', true);
$('textarea[name="message"]').prop('disabled', true);


// ENABLE COURSE ONLY IF ADMIN SELECTED
$('select[name="to_admin"]').change(function(){

    let adminSelected = $(this).val();

    if(adminSelected){
        $('select[name="course_id"]').prop('disabled', false);
    } else {
        $('select[name="course_id"]').prop('disabled', true);
        $('textarea[name="message"]').prop('disabled', true);
    }
});


// ENABLE MESSAGE ONLY IF COURSE SELECTED
$('select[name="course_id"]').change(function(){

    if($(this).val()){
        $('textarea[name="message"]').prop('disabled', false);
    } else {
        $('textarea[name="message"]').prop('disabled', true);
    }
});

// ================= LOAD SENT FEEDBACK =================
function loadSentFull(){
    $.getJSON('ajax_load_sent_feedback.php', function(data){

        // ================= STUDENTS =================
        let studentHtml = '';
        let studentGrouped = {};

        // Group by course + lesson
        data.students.forEach(f=>{
            let key = `${f.course_name || 'No Course'} | ${f.lesson_title || 'No Lesson'}`;
            if(!studentGrouped[key]) studentGrouped[key] = [];
            studentGrouped[key].push(f);
        });

        // Loop through grouped data
        for(let group in studentGrouped){
            let [course, lesson] = group.split('|');

            studentHtml += `
<div class="mb-3">
<strong>Course:</strong> ${course.trim()} &nbsp;&nbsp; <strong>Lesson:</strong> ${lesson.trim()}
<table class="table table-dark table-bordered mt-2">
<thead>
<tr>
<th>Student Name</th><th>Year</th><th>Block</th><th>Feedback</th>
<th>Status</th><th>Date Sent</th><th>Reply</th><th>Reply Date</th>
</tr>
</thead>
<tbody>
`;

            studentGrouped[group].forEach(f=>{
                studentHtml += `
<tr>
<td>${f.surname}, ${f.firstname} ${f.middlename||''}</td>
<td>${f.year_level}</td>
<td>${f.block}</td>
<td>${f.message}</td>
<td>${f.is_read==1 ? 'Read' : 'Unread'}</td>
<td>${f.created_at}</td>
<td>
<button class="btn btn-sm btn-info view-reply" 
        data-id="${f.feedback_id}"
        data-message="${f.reply_message||''}" 
        data-date="${f.reply_created_at||''}"  
        ${f.reply_message ? '' : 'disabled'}>
Read
</button>
</td>
<td>${f.reply_created_at||''}</td>
</tr>
`;
            });

            studentHtml += `</tbody></table></div>`;
        }

        $('#sentStudentTable').html(studentHtml);


        // ================= ADMINS =================
        let adminHtml = '';
        let adminGrouped = {};

        data.admins.forEach(f=>{
            let key = f.course_name || 'No Course';
            if(!adminGrouped[key]) adminGrouped[key] = [];
            adminGrouped[key].push(f);
        });

        for(let course in adminGrouped){
            adminHtml += `
<div class="mb-3">
<strong>Course:</strong> ${course.trim()}
<table class="table table-dark table-bordered mt-2">
<thead>
<tr>
<th>Admin Name</th><th>Position</th><th>Feedback</th>
<th>Status</th><th>Date Sent</th><th>Reply</th><th>Reply Date</th>
</tr>
</thead>
<tbody>
`;

            adminGrouped[course].forEach(f=>{
                adminHtml += `
<tr>
<td>${f.surname}, ${f.firstname} ${f.middlename||''}</td>
<td>${f.position}</td>
<td>${f.message}</td>
<td>${f.is_read==1 ? 'Read' : 'Unread'}</td>
<td>${f.created_at}</td>
<td>
<button class="btn btn-sm btn-info view-reply" 
        data-id="${f.feedback_id}"
        data-message="${f.reply_message||''}" 
        data-date="${f.reply_created_at||''}"  
        ${f.reply_message ? '' : 'disabled'}>
Read
</button>
</td>
</td>
<td>${f.reply_created_at||''}</td>
</tr>
`;
            });

            adminHtml += `</tbody></table></div>`;
        }

        $('#sentAdminTable').html(adminHtml);

        // ================= VIEW REPLY MODAL =================
        $(document).on('click', '.view-reply', function(){

    let btn = $(this);
    let feedbackId = btn.data('id');

    let msg = btn.data('message') || 'No reply yet';
    let date = btn.data('date') || '-';

    // 👉 UPDATE reply_is_read = 1
    $.post('ajax_mark_reply_read.php', { feedback_id: feedbackId });

    // 👉 SHOW MESSAGE
    alert(`Reply Date: ${date}\n\nMessage:\n${msg}`);
});
    });
}

// ================= LIVE SEARCH =================
$('#searchSentFull').on('input', function(){
    let val = $(this).val().toLowerCase();

    // STUDENTS
    $('#sentStudentTable table').each(function(){
        let showTable = false;
        $(this).find('tbody tr').each(function(){
            let match = $(this).text().toLowerCase().includes(val);
            $(this).toggle(match);
            if(match) showTable = true;
        });
        $(this).toggle(showTable);
    });

    // ADMINS
    $('#sentAdminTable table').each(function(){
        let showTable = false;
        $(this).find('tbody tr').each(function(){
            let match = $(this).text().toLowerCase().includes(val);
            $(this).toggle(match);
            if(match) showTable = true;
        });
        $(this).toggle(showTable);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadReceivedFeedback();

    // Send reply button
    document.getElementById('send-reply-btn').addEventListener('click', function(){
        let feedbackId = document.getElementById('reply-feedback-id').value;
        let replyMessage = document.getElementById('reply-message').value;
        let replyToType = document.getElementById('reply-to-type').value;

        if(replyMessage.trim() === '') return alert('Reply cannot be empty.');

        fetch('ajax_send_reply.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `feedback_id=${feedbackId}&reply_message=${encodeURIComponent(replyMessage)}&reply_from_type=${replyToType}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                alert('Reply sent!');
                closeReplyModal();
                loadReceivedFeedback(); // reload list
            }
        });
    });
});

// ================= LOAD RECEIVED FEEDBACK =================
function loadReceivedFeedback(){
    fetch('ajax_load_received_feedback.php')
    .then(res => res.json())
    .then(data => {

        // ======== STUDENT → INSTRUCTOR ========
        let studentHtml = '';

        if(data.students.length === 0){
            studentHtml = '<p class="text-muted">No feedback received from students.</p>';
        } else {
            // Group by date
            let studentGrouped = {};
            data.students.forEach(f=>{
                let date = f.created_at.split(' ')[0]; // only date
                if(!studentGrouped[date]) studentGrouped[date] = [];
                studentGrouped[date].push(f);
            });

            for(let date in studentGrouped){
                studentHtml += `
                <h6 class="text-warning mt-3">Date Received: ${date}</h6>
                <table class="table table-dark table-bordered">
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
                <tbody>
                `;

                studentGrouped[date].forEach(f=>{
                    studentHtml += `
                    <tr>
                        <td>${f.surname}, ${f.firstname} ${f.middlename||''}</td>
                        <td>${f.year_level}</td>
                        <td>${f.block}</td>
                        <td>
                            <button class="btn btn-sm btn-info"
                                onclick="viewFeedback(${f.feedback_id}, 'STUDENT', \`${f.message}\`)">
                                View
                            </button>
                        </td>
                        <td>${f.is_read==1 ? 'Read' : 'Unread'}</td>
                        <td>${f.created_at}</td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                onclick="openReply(${f.feedback_id}, 'STUDENT')"
                                ${f.reply_message ? '' : ''}>
                                ${f.reply_message ? 'Edit' : 'Reply'}
                            </button>
                        </td>
                        <td>${f.reply_created_at || ''}</td>
                        <td>${f.reply_is_read==1 ? 'Read' : 'Unread'}</td>
                    </tr>
                    `;
                });

                studentHtml += '</tbody></table>';
            }
        }

        $('#receivedStudentTable').html(studentHtml);

        // ======== ADMIN → INSTRUCTOR ========
        let adminHtml = '';

        if(data.admins.length === 0){
            adminHtml = '<p class="text-muted">No feedback received from admins.</p>';
        } else {
            let adminGrouped = {};
            data.admins.forEach(f=>{
                let date = f.created_at.split(' ')[0];
                if(!adminGrouped[date]) adminGrouped[date] = [];
                adminGrouped[date].push(f);
            });

            for(let date in adminGrouped){
                adminHtml += `
                <h6 class="text-warning mt-3">Date Received: ${date}</h6>
                <table class="table table-dark table-bordered">
                <thead>
                <tr>
                    <th>Admin Name</th>
                    <th>Position</th>
                    <th>Feedback</th>
                    <th>Status</th>
                    <th>Date Sent</th>
                    <th>Reply</th>
                    <th>Reply Date</th>
                    <th>Reply Status</th>
                </tr>
                </thead>
                <tbody>
                `;

                adminGrouped[date].forEach(f=>{
                    adminHtml += `
                    <tr>
                        <td>${f.surname}, ${f.firstname} ${f.middlename||''}</td>
                        <td>${f.position}</td>
                        <td>
                            <button class="btn btn-sm btn-info"
                                onclick="viewFeedback(${f.feedback_id}, 'ADMIN', \`${f.message}\`)">
                                View
                            </button>
                        </td>
                        <td>${f.is_read==1 ? 'Read' : 'Unread'}</td>
                        <td>${f.created_at}</td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                onclick="openReply(${f.feedback_id}, 'ADMIN')"
                                ${f.reply_message ? '' : ''}>
                                ${f.reply_message ? 'Edit' : 'Reply'}
                            </button>
                        </td>
                        <td>${f.reply_created_at || ''}</td>
                        <td>${f.reply_is_read==1 ? 'Read' : 'Unread'}</td>
                    </tr>
                    `;
                });

                adminHtml += '</tbody></table>';
            }
        }

        $('#receivedAdminTable').html(adminHtml);
    })
    .catch(err => {
        console.error('Error loading feedback:', err);
        $('#receivedStudentTable').html('<p class="text-danger">Failed to load student feedback.</p>');
        $('#receivedAdminTable').html('<p class="text-danger">Failed to load admin feedback.</p>');
    });
}

// View feedback
function viewFeedback(id, type, message){

    alert(message); // simple popup

    fetch('ajax_mark_feedback_read.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`feedback_id=${id}&to_type=INSTRUCTOR`
    }).then(()=> loadReceivedFeedback());
}

    // Open reply modal
    function openReply(id, type){
    document.getElementById('reply-feedback-id').value = id;
    document.getElementById('reply-to-type').value = type;
    document.getElementById('reply-message').value = '';
    document.getElementById('reply-modal').style.display = 'block';
}

function closeReplyModal(){
    document.getElementById('reply-modal').style.display = 'none';
}


// INITIAL LOAD
$(document).ready(function(){
    loadReceivedFeedback();

    $('#searchReceived').on('input', function(){
        let val = $(this).val().toLowerCase();
        $('#receivedStudentTable, #receivedAdminTable').find('tbody tr').each(function(){
            let match = $(this).text().toLowerCase().includes(val);
            $(this).toggle(match);
        });
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
