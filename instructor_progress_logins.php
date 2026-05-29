<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Fetch instructor info
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die("Instructor not found.");

// Fetch today's logins, join students table
$sql = "
    SELECT l.*, s.surname, s.firstname, s.year_level, s.block, s.school_id
    FROM student_logins l
    JOIN students s ON l.student_id = s.school_id
    WHERE DATE(l.login_time) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ORDER BY s.year_level, s.block, s.surname, s.firstname, l.login_time
";
$stmtLogins = $pdo->query($sql);
$logins = $stmtLogins->fetchAll(PDO::FETCH_ASSOC);

// Group logins by year_level, block, and student
$groupedLogins = [];
foreach ($logins as $login) {
    $groupKey = $login['year_level'] . '-' . $login['block'];
    $studentKey = $login['school_id'];
    if (!isset($groupedLogins[$groupKey])) $groupedLogins[$groupKey] = [];
    if (!isset($groupedLogins[$groupKey][$studentKey])) $groupedLogins[$groupKey][$studentKey] = [];
    $groupedLogins[$groupKey][$studentKey][] = $login;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Student Log-ins</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

/* =========================
   THEME MATCH (NAVY + GOLD)
========================= */

:root {
    --navy: #071A2A;
    --navy2: #0B2540;
    --gold: #FFD700;
    --white: #ffffff;
    --glass: rgba(255,255,255,0.08);
}

/* BODY */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(180deg, var(--navy), var(--navy2));
    min-height: 100vh;
    color: var(--white);
    margin: 0;
    padding-bottom: 80px;
}

/* NAVBAR */
.navbar-custom {
    background: linear-gradient(90deg, rgba(7,27,42,0.95), rgba(8,48,79,0.95));
    border-bottom: 1px solid rgba(255,215,0,0.06);
    box-shadow: 0 8px 24px rgba(2,12,27,0.45);
}

.navbar-custom .navbar-brand {
    color: var(--gold);
    font-weight: 700;
}

/* CONTAINER */
.container-box {
    margin-top: 50px;
}

/* BOX STYLE */
.table-responsive {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.3);
    border-radius: 14px;
    padding: 15px;
    backdrop-filter: blur(8px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.4);
}

/* TABLE */
.table {
    color: var(--white);
}

.table thead {
    background: rgba(255,215,0,0.15);
    color: var(--gold);
}

.table tbody tr {
    background: rgba(255,255,255,0.05);
}

.table tbody tr:nth-child(even) {
    background: rgba(255,255,255,0.08);
}

/* BUTTONS */
.btn-back {
    background: var(--gold);
    color: #000;
    font-weight: 700;
    border: none;
}

.btn-back:hover {
    background: #e6c200;
}

.btn-warning {
    background: var(--gold);
    color: #000;
    font-weight: 700;
    border: none;
}

.btn-warning:hover {
    background: #e6c200;
}

/* SEARCH */
.search-input {
    max-width: 300px;
    margin-left: auto;
    border-radius: 10px;
}

/* GROUP TITLE */
.group-title {
    margin-top: 30px;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--gold);
}

/* LINK BUTTON */
.total-sessions-btn {
    text-decoration: underline;
    color: var(--gold);
    cursor: pointer;
}

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

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand">📅 Daily Student Log-ins</a>
  </div>
</nav>

<div class="container container-box">
    <div class="d-flex mb-3 align-items-center">
        <a href="instructor_view_progress.php" class="btn btn-back me-3">← Back</a>

        <a href="instructor_daily_logins.php" class="btn btn-warning me-3">
            📚 View History
        </a>

        <input type="text" class="form-control search-input" id="searchInput" placeholder="Search students...">
    </div>

    <?php if ($groupedLogins): ?>
    <?php foreach ($groupedLogins as $group => $students): 
        list($year, $block) = explode('-', $group);
    ?>
        <p class="group-title">Year Level: <strong><?= $year ?></strong> | Block: <strong><?= $block ?></strong></p>

        <div class="table-responsive">
        <table class="table table-bordered table-striped group-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>School ID</th>
                    <th>Total Sessions</th>
                </tr>
            </thead>
            <tbody>
                <?php $rowNumber = 1; ?>
                <?php foreach ($students as $studentId => $sessions): 
                    $totalSessions = count($sessions);
                    $firstSession = $sessions[0];
                ?>
                    <tr>
                        <td><?= $rowNumber++ ?></td>
                        <td><?= htmlspecialchars($firstSession['surname'] . ', ' . $firstSession['firstname']) ?></td>
                        <td><?= htmlspecialchars($firstSession['school_id']) ?></td>
                        <td>
                            <span class="total-sessions-btn" data-sessions='<?= json_encode($sessions) ?>' data-bs-toggle="modal" data-bs-target="#sessionsModal">
                                <?= $totalSessions ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-warning">No log-ins recorded today.</p>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div class="modal fade" id="sessionsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">Student Sessions</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul id="sessionList" class="list-group list-group-flush"></ul>
      </div>
    </div>
  </div>
</div>

<footer>
    Developed by <strong>Limetares Group</strong> — S.Y. <strong>2025–2026</strong>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.group-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});

var sessionsModal = document.getElementById('sessionsModal');
sessionsModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var sessions = JSON.parse(button.getAttribute('data-sessions'));
    var listEl = document.getElementById('sessionList');
    listEl.innerHTML = '';
    sessions.forEach((s, i) => {
        listEl.innerHTML += `<li class="list-group-item bg-secondary text-white">
            Session ${i+1}: Login - ${s.login_time}, Logout - ${s.logout_time ?? '-'}
        </li>`;
    });
});
</script>

</body>
</html>