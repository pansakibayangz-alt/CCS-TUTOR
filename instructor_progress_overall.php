<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Get logged-in instructor info
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
$instructor_id = $instructor['instructor_id'];

// Get courses assigned to this instructor
$stmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$stmt->execute([$instructor_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : ($courses[0]['course_id'] ?? null);

if (!$course_id) {
    die("No courses assigned to this instructor.");
}

// Get course name
$stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id=?");
$stmt->execute([$course_id]);
$course_name = $stmt->fetchColumn();

// Get students assigned to this instructor
$stmt = $pdo->prepare("SELECT * FROM students WHERE instructor_id=?");
$stmt->execute([$instructor_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare student performance array
$student_performance = [];

foreach ($students as $student) {
    $student_id = $student['school_id'];

    // Total lessons in this course
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id=?");
    $stmt->execute([$course_id]);
    $total_lessons = $stmt->fetchColumn();

    // Lessons completed by student
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_lesson_completion slc
        JOIN lessons l ON slc.lesson_id = l.lesson_id
        WHERE slc.student_id=? AND l.course_id=?");
    $stmt->execute([$student_id, $course_id]);
    $completed_lessons = $stmt->fetchColumn();

    $lesson_percent = $total_lessons ? ($completed_lessons / $total_lessons) * 100 : 0;

    // Sum of student scores for this course
$stmt = $pdo->prepare("
    SELECT SUM(spa.score) as total_score
    FROM student_pretest_attempts spa
    JOIN pretests p ON spa.pretest_id = p.pretest_id
    WHERE spa.student_id=? AND p.course_id=?
");
$stmt->execute([$student_id, $course_id]);
$total_student_pretest_score = $stmt->fetchColumn() ?? 0;

// Total possible points (sum of pretest items)
$stmt = $pdo->prepare("
    SELECT COUNT(pi.item_id) as total_items
    FROM pretests p
    JOIN pretest_items pi ON pi.pretest_id = p.pretest_id
    WHERE p.course_id=?
");
$stmt->execute([$course_id]);
$total_pretest_items = $stmt->fetchColumn() ?? 0;

$pretest_percent = $total_pretest_items ? ($total_student_pretest_score / $total_pretest_items) * 100 : 0;

    // Sum of student scores for assessments
$stmt = $pdo->prepare("
    SELECT SUM(saa.score) as total_score
    FROM student_assessment_attempts saa
    JOIN assessments a ON saa.assessment_id = a.assessment_id
    WHERE saa.student_id=? AND a.course_id=?
");
$stmt->execute([$student_id, $course_id]);
$total_student_assessment_score = $stmt->fetchColumn() ?? 0;

// Total possible assessment items
$stmt = $pdo->prepare("
    SELECT COUNT(ai.item_id) as total_items
    FROM assessments a
    JOIN assessment_items ai ON ai.assessment_id = a.assessment_id
    WHERE a.course_id=?
");
$stmt->execute([$course_id]);
$total_assessment_items = $stmt->fetchColumn() ?? 0;

$assessment_percent = $total_assessment_items ? ($total_student_assessment_score / $total_assessment_items) * 100 : 0;

    // Overall performance (average of 3)
    $overall_percent = ($lesson_percent + $pretest_percent + $assessment_percent) / 3;

    $student_performance[] = [
    'name' => strtoupper($student['surname']) . ' ' . strtoupper(substr($student['firstname'],0,1)) . '.',
    'fullname' => strtoupper($student['firstname'].' '.$student['middlename'].' '.$student['surname']),
    'percent' => round($overall_percent,2)
];
}

// Sort by descending performance
usort($student_performance, fn($a,$b)=> $b['percent'] <=> $a['percent']);

// Prepare login data
$login_data = [];

foreach ($students as $student) {
    $student_id = $student['school_id'];

    // Count total logins
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM student_logins
        WHERE student_id=?
    ");
    $stmt->execute([$student_id]);
    $login_count = $stmt->fetchColumn() ?? 0;

    // Get last login
    $stmt = $pdo->prepare("
        SELECT login_time FROM student_logins
        WHERE student_id=?
        ORDER BY login_time DESC LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $last_login = $stmt->fetchColumn() ?? 'Never';

    $login_data[] = [
    'name' => strtoupper($student['surname']) . ' ' . strtoupper(substr($student['firstname'],0,1)) . '.',
    'fullname' => strtoupper($student['firstname'].' '.$student['middlename'].' '.$student['surname']),
    'count' => $login_count,
    'last_login' => $last_login
];
}

// Find max logins for scaling to 100%
$max_login = max(array_column($login_data, 'count')) ?: 1;

// Calculate login percentage
foreach ($login_data as &$ldata) {
    $ldata['percent'] = ($ldata['count'] / $max_login) * 100;
}
// Sort login data descending
usort($login_data, fn($a,$b) => $b['percent'] <=> $a['percent']);
unset($ldata);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Overall Performance — <?php echo htmlspecialchars($course_name); ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#071A2A;color:#fff;font-family:Poppins,sans-serif;padding:20px;}
h3{color:#FFD700;}
.card{background: rgba(12,32,54,0.7); border:2px solid #FFD700; border-radius:12px; padding:20px;}
/* FOOTER */
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(7,27,42,0.85);
    backdrop-filter: blur(8px);
    color: #fff;
    text-align: center;
    padding: 10px;
    font-weight: 600;
    border-top: 1px solid rgba(255,215,0,0.12);
}
</style>
</head>
<body>

<!-- BACK BUTTON -->
<a href="instructor_view_progress.php" class="btn btn-warning mb-3">← Back</a>

<div class="container">
    <h3>Course: <?php echo htmlspecialchars($course_name); ?></h3>

    <div class="row mt-4">
        
        <!-- LEFT: PERFORMANCE -->
        <div class="col-md-6">
            <div class="card">
                <h5 class="text-center mb-3">Overall Performance</h5>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- RIGHT: LOGIN -->
        <div class="col-md-6">
            <div class="card">
                <h5 class="text-center mb-3">Login Activity</h5>
                <canvas id="loginChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
const ctx = document.getElementById('performanceChart').getContext('2d');

const labels = <?php echo json_encode(array_column($student_performance,'name')); ?>;
const data = <?php echo json_encode(array_column($student_performance,'percent')); ?>;
const fullNames = <?php echo json_encode(array_column($student_performance,'fullname')); ?>;

const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Performance %',
            data: data,
            backgroundColor: 'rgba(255, 215, 0, 0.7)',
            borderColor: 'rgba(255, 215, 0, 1)',
            borderWidth: 1
        }]
    },
    options: {
    responsive:true,

    onClick: function(evt, elements) {
        if(elements.length > 0){
            let index = elements[0].index;
            let studentName = fullNames[index];

            fetch('get_student_details.php?name=' + encodeURIComponent(studentName) + '&course_id=<?php echo $course_id; ?>')
            .then(res => res.text())
            .then(data => {
                document.getElementById('studentDetailsContent').innerHTML = data;
                new bootstrap.Modal(document.getElementById('studentModal')).show();
            });
        }
    },

    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let idx = context.dataIndex;
                    return fullNames[idx] + ' — ' + context.parsed.y + '%';
                }
            }
        }
    },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: { display: true, text: 'Performance (%)' }
            },
            x: {
                ticks: { color:'#fff', font:{size:12} },
                title: { display: true, text: 'Students', color:'#FFD700' }
            }
        }
    }
});
</script>
<script>
const ctxLogin = document.getElementById('loginChart').getContext('2d');

const loginLabels = <?php echo json_encode(array_column($login_data,'name')); ?>;
const loginPercents = <?php echo json_encode(array_column($login_data,'percent')); ?>;
const loginLast = <?php echo json_encode(array_column($login_data,'last_login')); ?>;
const loginFullNames = <?php echo json_encode(array_column($login_data,'fullname')); ?>;

const loginChart = new Chart(ctxLogin, {
    type: 'bar',
    data: {
        labels: loginLabels,
        datasets: [{
            label: 'Login % (relative)',
            data: loginPercents,
            backgroundColor: 'rgba(0, 123, 255, 0.7)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1
        }]
    },
    options: {
    responsive:true,

    onClick: function(evt, elements) {
        if(elements.length > 0){
            let index = elements[0].index;
            let studentName = loginFullNames[index];

            fetch('get_login_details.php?name=' + encodeURIComponent(studentName))
            .then(res => res.text())
            .then(data => {
                document.getElementById('studentDetailsContent').innerHTML = data;
                new bootstrap.Modal(document.getElementById('studentModal')).show();
            });
        }
    },

    plugins: {
            legend: { display: false },
            tooltip: {
    callbacks: {
        label: function(context) {
            let idx = context.dataIndex;
            return loginFullNames[idx] + 
                   ' | Login %: ' + context.parsed.y.toFixed(0) + 
                   '% | Last login: ' + loginLast[idx];
        }
    }
}
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: { display: true, text: 'Login Percentage' }
            },
            x: {
                ticks: { color:'#fff', font:{size:12} },
                title: { display: true, text: 'Students', color:'#FFD700' }
            }
        }
    }
});
</script>

<!-- STUDENT DETAILS MODAL -->
<div class="modal fade" id="studentModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content bg-dark text-white">
      
      <div class="modal-header">
        <h5 class="modal-title">Student Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="studentDetailsContent">Loading...</div>
      </div>

    </div>
  </div>
</div>
<footer>
    Developed by <strong>Limetares Group</strong> — S.Y. 2025–2026
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>