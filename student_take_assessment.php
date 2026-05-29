<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

/* =====================================================
   ADD THE FORMAT FUNCTION (YOUR REQUEST #1)
===================================================== */
function formatNameWithDegree($firstname, $surname, $degree) {
    if (!$firstname && !$surname) return "";

    if (empty($degree) || strtoupper($degree) === "N/A") {
        return htmlspecialchars($firstname . " " . $surname);
    }

    return htmlspecialchars($firstname . " " . $surname . ", " . $degree);
}

$schoolId = $_SESSION['school_id'];
$successMessage = "";

// Fetch student info
$stmtStudent = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
$stmtStudent->execute([$schoolId]);
$student = $stmtStudent->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

// Determine assessment_id or lesson_id
$assessmentId = isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : 0;
$lessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
if (!$assessmentId && !$lessonId) die("Invalid assessment or lesson ID.");

// Fetch assessment
if (!$assessmentId && $lessonId) {
    $stmtFind = $pdo->prepare("SELECT * FROM assessments WHERE lesson_id=? AND year_level=? AND block=? LIMIT 1");
    $stmtFind->execute([$lessonId, $student['year_level'], $student['block']]);
    $assessment = $stmtFind->fetch();
    if (!$assessment) die("Assessment not found.");
    $assessmentId = $assessment['assessment_id'];
} else {
    $stmtAssessment = $pdo->prepare("SELECT * FROM assessments WHERE assessment_id=? AND year_level=? AND block=?");
    $stmtAssessment->execute([$assessmentId, $student['year_level'], $student['block']]);
    $assessment = $stmtAssessment->fetch();
    if (!$assessment) die("Assessment not found.");
    $lessonId = $assessment['lesson_id'];
}

// Check lesson completion
$stmtCheckLesson = $pdo->prepare("SELECT * FROM student_lesson_completion WHERE student_id=? AND lesson_id=?");
$stmtCheckLesson->execute([$schoolId, $lessonId]);
if (!$stmtCheckLesson->fetch()) die("You must complete the lesson first.");

// Fetch assessment items
$stmtItems = $pdo->prepare("SELECT * FROM assessment_items WHERE assessment_id=? ORDER BY item_no ASC");
$stmtItems->execute([$assessmentId]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
$totalItems = count($items);

// Check last attempt
$stmtCheck = $pdo->prepare("SELECT * FROM student_assessment_attempts WHERE student_id=? AND assessment_id=? ORDER BY attempt_no DESC LIMIT 1");
$stmtCheck->execute([$schoolId, $assessmentId]);
$lastAttempt = $stmtCheck->fetch();

$lastScore = 0;
$alreadyPerfect = false;
$displayAttemptNo = 0;
$nextAttemptNo = 1;

if ($lastAttempt) {
    $lastScore = $lastAttempt['score'];
    $displayAttemptNo = $lastAttempt['attempt_no'];
    $nextAttemptNo = $displayAttemptNo + 1;
    if ($lastScore == $totalItems) $alreadyPerfect = true;
}

/* =====================================================
   SUBMISSION LOGIC
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyPerfect) {
    $score = 0;
    foreach ($items as $item) {
        $answer = trim($_POST['q_'.$item['item_id']] ?? '');
        $correct = trim($item['answer']);
        $is_correct = false;

        if ($item['options']) {
            $is_correct = ($answer === $correct);
        } else {
            $is_correct = (strcasecmp($answer, $correct) === 0);
        }

        if ($is_correct) $score++;

        $analysis = $item['item_analysis'] ? json_decode($item['item_analysis'], true) : ['correct'=>0,'wrong'=>0];
        if ($is_correct) $analysis['correct']++; else $analysis['wrong']++;
        $stmtUpdateItem = $pdo->prepare("UPDATE assessment_items SET item_analysis=? WHERE item_id=?");
        $stmtUpdateItem->execute([json_encode($analysis, JSON_UNESCAPED_UNICODE), $item['item_id']]);
    }

    $stmtSave = $pdo->prepare("INSERT INTO student_assessment_attempts (student_id, assessment_id, score, attempt_no) VALUES (?,?,?,?)");
    $stmtSave->execute([$schoolId, $assessmentId, $score, $nextAttemptNo]);

    $lastScore = $score;
    $displayAttemptNo = $nextAttemptNo;
    $remarks = ($score == $totalItems) ? "Perfect Score! ✅" : "Keep Trying ❌";
    $successMessage = "Attempt #$displayAttemptNo submitted. Score: $score / $totalItems. Remarks: $remarks";

    $alreadyPerfect = ($score == $totalItems);
}

/* =====================================================
   CERTIFICATE CHECK
===================================================== */
$certificate = null;
if ($alreadyPerfect) {
    $stmtCert = $pdo->prepare("SELECT * FROM certificates WHERE student_id=? AND assessment_id=? LIMIT 1");
    $stmtCert->execute([$student['student_id'], $assessmentId]);
    $certificate = $stmtCert->fetch();

    if (!$certificate) {
        $certNumber = 'CERT-' . strtoupper(uniqid()) . '-' . $student['student_id'];
        $stmtInsertCert = $pdo->prepare("INSERT INTO certificates (certificate_number, student_id, assessment_id) VALUES (?,?,?)");
        $stmtInsertCert->execute([$certNumber, $student['student_id'], $assessmentId]);

        $stmtCert->execute([$student['student_id'], $assessmentId]);
        $certificate = $stmtCert->fetch();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Assessment - <?= htmlspecialchars($assessment['instructions']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg,#6a0dad,#c39bd3); color:#fff; min-height:100vh; padding-bottom:80px; }
.navbar-custom { background: linear-gradient(135deg,#4B0082,#6a0dad); }
.navbar-custom .nav-link, .navbar-brand { color:#fff; font-weight:600; }
.navbar-custom .nav-link:hover { color:#FFD700; }
.box { border:2px solid rgba(255,255,255,0.7); padding:25px; border-radius:15px; background: rgba(0,0,0,0.25); box-shadow:0 0 12px rgba(255,255,255,0.3); margin:30px 0; }
footer { position:fixed; bottom:0; width:100%; background:rgba(0,0,0,0.55); color:#fff; text-align:center; padding:10px; font-weight:600; }
.progress-box { background: rgba(255,255,255,0.1); padding:15px; border-radius:10px; margin-bottom:20px; }
/* --- STUDENT NAME FONT UPDATE --- */
.student-name {
    font-family: 'Rockwell', serif; /* Informal Roman font */
    font-weight: bold; /* bold text */
    color: #6a0dad;
}
</style>

</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand">Assessment: <?= htmlspecialchars($assessment['instructions']); ?></a>
  </div>
</nav>

<div class="container mt-5">
    <a href="student_view_lesson_content.php?lesson_id=<?= $lessonId ?>" class="btn btn-warning fw-bold mb-4">← Back</a>
    <div class="box">

        <?php if ($successMessage): ?>
            <div class="alert alert-info"><?= $successMessage ?></div>
        <?php endif; ?>

        <div class="progress-box">
			<p><strong>Attempt:</strong> <?= $displayAttemptNo ?> </p>
			<p><strong>Last Score:</strong> <?= $lastScore ?> / <?= $totalItems ?></p>
			<p><strong>Total Items:</strong> <?= $totalItems ?></p>
		</div>

        <?php if (!$alreadyPerfect && !empty($items)): ?>
        
        <!-- QUESTIONS -->
        <form method="POST">
            <?php foreach ($items as $item): ?>
                <div class="mb-4">
                    <p><strong><?= $item['item_no'] ?>. <?= htmlspecialchars($item['question']); ?></strong></p>

                    <?php if (!empty($item['options'])):
                        $options = json_decode($item['options'], true);
                        foreach ($options as $key=>$val): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q_<?= $item['item_id'] ?>" value="<?= htmlspecialchars($key) ?>" required>
                            <label class="form-check-label"><?= htmlspecialchars($key) ?>. <?= htmlspecialchars($val) ?></label>
                        </div>
                    <?php endforeach; else: ?>
                        <input type="text" name="q_<?= $item['item_id'] ?>" class="form-control" required>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success fw-bold">Submit Assessment</button>
        </form>

        <?php elseif ($alreadyPerfect): ?>

            <div class="alert alert-success">
                You have achieved a perfect score! ✅ You can now proceed to the next lesson.
            </div>

<?php if ($certificate): ?>

<?php
$stmtCourse = $pdo->prepare("SELECT course_name FROM courses WHERE course_id=? LIMIT 1");
$stmtCourse->execute([$assessment['course_id']]);
$course = $stmtCourse->fetch(PDO::FETCH_ASSOC);

$stmtLesson = $pdo->prepare("SELECT lesson_title FROM lessons WHERE lesson_id=? LIMIT 1");
$stmtLesson->execute([$assessment['lesson_id']]);
$lesson = $stmtLesson->fetch(PDO::FETCH_ASSOC);

$instructor = null;
if (!empty($student['instructor_id'])) {
    $stmtInstructor = $pdo->prepare("SELECT * FROM instructor WHERE instructor_id = ?");
    $stmtInstructor->execute([$student['instructor_id']]);
    $instructor = $stmtInstructor->fetch(PDO::FETCH_ASSOC);
}

$stmtChair = $pdo->prepare("SELECT * FROM admin WHERE position = 'Program chair' LIMIT 1");
$stmtChair->execute();
$programChair = $stmtChair->fetch(PDO::FETCH_ASSOC);

$stmtDean = $pdo->prepare("SELECT * FROM admin WHERE position = 'College Dean' LIMIT 1");
$stmtDean->execute();
$collegeDean = $stmtDean->fetch(PDO::FETCH_ASSOC);
?>

<!-- CERTIFICATE SECTION -->
<div class="certificate-container mt-4">
<div id="certificate" class="certificate">

    <!-- HEADER -->
    <div class="certificate-header">

        <!-- ✔ UPDATED: INLINE BLOCK LOGO SYSTEM -->
        <img src="jrmsu.png" class="cert-logo left-logo">

        <div class="cert-header-text">
            <p class="header-line">Republic of the Philippines</p>
            <p class="header-line bold">JOSE RIZAL MEMORIAL STATE UNIVERSITY</p>
            <p class="header-line italic">The Premier University in Zamboanga del Norte</p>
            <p class="header-line">Manaol, Siocon Zamboanga del Norte</p>
        </div>

        <img src="ccs.png" class="cert-logo right-logo">

    </div>

    <div class="certificate-body">

		<h1 class="certificate-title">Certificate of Achievement</h1>

		<p class="presented-text">is proudly presented to</p>

		<h2 class="student-name" style="font-size: 40px; font-style: italic">
			<?= htmlspecialchars($student['firstname'] . ' ' . strtoupper(substr($student['middlename'],0,1)) . '. ' . $student['surname']); ?>
		</h2>

		<p class="achievement-text">
			For actively participating in the lessons, completing the assessment with diligence, and showing dedication in learning. Your effort, perseverance, and commitment to excellence are highly appreciated.
		</p>

		<p class="cert-details">
			Given this <?= date('F d, Y'); ?> with Limetares Group.<br>
			Certificate Number: <strong><?= htmlspecialchars($certificate['certificate_number']); ?></strong>
		</p>

	</div>

    <div class="certificate-signatures">

    <div class="signature-block">
        <?php if ($instructor && file_exists('signatures/instructor.png')): ?>
            <img src="signatures/instructor.png" alt="Instructor Signature" class="signature-img">
        <?php endif; ?>
        <p class="sig-name">
            <?= $instructor 
                ? formatNameWithDegree($instructor['firstname'], $instructor['surname'], $instructor['degree_designation'])
                : 'Instructor Name'; ?>
        </p>
        <p class="sig-title">Instructor, CCS</p>
    </div>

    <div class="signature-block">
        <?php if ($programChair && file_exists('signatures/program_chair.png')): ?>
            <img src="signatures/program_chair.png" alt="Program Chair Signature" class="signature-img">
        <?php endif; ?>
        <p class="sig-name">
            <?= $programChair
                ? formatNameWithDegree($programChair['firstname'], $programChair['surname'], $programChair['degree_designation'])
                : 'Program Chair Name'; ?>
        </p>
        <p class="sig-title">Program Chair, CCS</p>
    </div>

    <div class="signature-block">
        <?php if ($collegeDean && file_exists('signatures/dean.png')): ?>
            <img src="signatures/dean.png" alt="College Dean Signature" class="signature-img">
        <?php endif; ?>
        <p class="sig-name">
            <?= $collegeDean
                ? formatNameWithDegree($collegeDean['firstname'], $collegeDean['surname'], $collegeDean['degree_designation'])
                : 'Dean Name'; ?>
        </p>
        <p class="sig-title">College Dean, CCS</p>
    </div>

</div>

</div>
</div>

<div class="d-flex gap-3 mt-3">
    <button id="downloadCertificate" class="btn btn-success fw-bold">Download Certificate 📄</button>
</div>
<!-- UPDATED CSS -->
<style>
.certificate-container { display:flex; justify-content:center; margin-top:30px; }

.certificate {
    width: 900px;
    padding: 40px;
    border-radius: 20px;
    background: #ffffff;
    color: #000;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
    font-family: 'Georgia', serif;
    border: 10px solid #d4af37;
    position: relative;
    overflow: hidden;
}

.certificate::before {
    content: "";
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: url('jrmsu_watermark.png') no-repeat center center;
    background-size: 500px;
    opacity: 0.07;
    width: 600px;
    height: 600px;
    pointer-events: none;
}

/* LOGO FIX – AS REQUESTED */
.cert-logo { width: 110px; height: 110px; object-fit: contain; }

.certificate-header {
    text-align: center;
    position: relative;
}

.left-logo, .right-logo {
    display: inline-block;
    vertical-align: middle;
    position: absolute;
    top: 20px;
}

.left-logo { left: 40px; }
.right-logo { right: 40px; }

.cert-header-text {
    margin-top: 10px; /* ✔ FIXED FROM -20px */
    text-align: center;
}

.header-line { margin: 0; font-size: 18px; }
.bold { font-weight: bold; font-size: 20px; }
.italic { font-style: italic; }

.presented-text { font-size: 18px; margin-top: 30px; }
.student-name { font-size: 32px; color:#6a0dad; margin: 15px 0; }

.certificate-signatures {
    display: flex;
    justify-content: space-between;
    align-items: flex-end; /* ensures all signature blocks align at bottom */
    margin-top: 60px;
    text-align: center;
}

.signature-block {
    display: flex;
    flex-direction: column;
    align-items: center; /* centers image + name + title */
    width: 30%;
}

.signature-img {
    max-width: 100%;
    height: 80px; /* adjust as needed */
    display: block;
    margin-bottom: 1px;
}

.sig-name {
    font-weight: bold;
    padding-top: 5px;
    font-size: 18px;
    margin: 0; /* spacing above/below */
}

.sig-title {
    margin: 0;
    font-size: 16px;
}

.certificate-body {
    text-align: center;
    margin-top: 30px;
}

.certificate-title {
    font-size: 36px;
    color: #4B0082;
    text-transform: uppercase;
    font-weight: bold;
    margin-bottom: 20px;
}

.presented-text {
    font-size: 20px;
    margin-bottom: 15px;
}

.student-name {
    font-size: 28px;
    color: #6a0dad;
    margin-bottom: 15px;
}

.achievement-text {
    font-size: 18px;
    margin: 15px 0 20px 0;
}

.cert-details {
    font-size: 16px;
    margin-top: 20px;
}

/* CERTIFICATE WATERMARK - DIAGONAL */
.certificate::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg); /* diagonal tilt */
    background: url('jrmsu.png') no-repeat center center; /* your JRMSU logo file */
    background-size: 450px; /* adjust the size of the watermark */
    opacity: 0.08; /* subtle visibility */
    width: 500px;
    height: 500px;
    pointer-events: none; /* allows clicks through */
    z-index: 0; /* stays behind certificate content */
}

/* Ensure certificate text and signatures appear above the watermark */
.certificate-body,
.certificate-signatures {
    position: relative;
    z-index: 1;
}

</style>

<?php endif; ?>
<?php endif; ?>

    </div>
</div>

<footer class="text-center py-3" style="
    position: fixed;
    bottom: 0;
    width: 100%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(8px);
    color:#fff;
    border-top:1px solid rgba(255,255,255,0.3);
    font-weight:600;
">
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// --- DOWNLOAD CERTIFICATE AS IMAGE (JPEG) ---
document.getElementById("downloadCertificate").addEventListener("click", function() {
    const certificate = document.getElementById("certificate");

    html2canvas(certificate, { scale: 2, useCORS: true }).then(canvas => {
        // Convert canvas to JPEG
        const imgData = canvas.toDataURL("image/jpeg", 0.95); // 95% quality
        const link = document.createElement("a");
        link.href = imgData;
        link.download = "Certificate_<?= htmlspecialchars($student['firstname'].'_'.$student['surname']); ?>.jpg";
        link.click();
    });
});
</script>

</body>
</html>
