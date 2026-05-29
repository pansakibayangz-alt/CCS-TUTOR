<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit;
}

require_once 'db.php'; // make sure $pdo is defined here

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM instructor WHERE username = ?");
$stmt->execute([$username]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die("Instructor not found.");

$selectedDate = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : null;

if ($selectedDate) {
    $sql = "
        SELECT l.*, s.surname, s.firstname, s.year_level, s.block, s.school_id
        FROM student_logins l
        JOIN students s ON l.student_id = s.school_id
        WHERE DATE(l.login_time) = :selected_date
        ORDER BY l.login_time DESC, s.year_level, s.block, s.surname, s.firstname
    ";
    $stmtLogins = $pdo->prepare($sql);
    $stmtLogins->execute(['selected_date' => $selectedDate]);
} else {
    $sql = "
        SELECT l.*, s.surname, s.firstname, s.year_level, s.block, s.school_id
        FROM student_logins l
        JOIN students s ON l.student_id = s.school_id
        ORDER BY DATE(l.login_time) DESC, l.login_time DESC, s.year_level, s.block, s.surname, s.firstname
    ";
    $stmtLogins = $pdo->query($sql);
}

$logins = $stmtLogins->fetchAll(PDO::FETCH_ASSOC);

$groupedByDate = [];
foreach ($logins as $login) {
    $date = date('Y-m-d', strtotime($login['login_time']));
    $groupKey = ($login['year_level'] ?? 'N/A') . '-' . ($login['block'] ?? 'N/A');
    $studentKey = $login['school_id'];

    if (!isset($groupedByDate[$date])) $groupedByDate[$date] = [];
    if (!isset($groupedByDate[$date][$groupKey])) $groupedByDate[$date][$groupKey] = [];
    if (!isset($groupedByDate[$date][$groupKey][$studentKey])) $groupedByDate[$date][$groupKey][$studentKey] = [];

    $groupedByDate[$date][$groupKey][$studentKey][] = $login;
}

krsort($groupedByDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Student Log-ins History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

/* =======================
   LMS NAVY + GOLD THEME
======================= */

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
    margin-top: 30px;
    margin-bottom: 90px;
}

/* GLASS TABLE */
.table-responsive {
    background: var(--glass);
    border: 2px solid rgba(255,215,0,0.25);
    border-radius: 14px;
    padding: 15px;
    backdrop-filter: blur(8px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.4);
}

/* TABLE */
.table {
    color: #fff;
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
    margin-top: 20px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--gold);
}

/* MODAL */
.list-group-item.bg-secondary {
    background: rgba(0,0,0,0.45) !important;
    color: #fff;
    border: none;
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
    <a class="navbar-brand">📅 Student Log-ins History</a>
  </div>
</nav>

<div class="container container-box">

    <div class="d-flex mb-3 align-items-center">
        <a href="instructor_progress_logins.php" class="btn btn-back me-3">← Back</a>

        <form method="get" class="d-flex gap-2">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate ?? '') ?>">
            <button class="btn btn-warning">Apply</button>
            <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-secondary">Clear</a>
        </form>

        <input type="text" class="form-control search-input ms-auto" id="searchInput" placeholder="Search students...">
    </div>

    <?php if (empty($groupedByDate)): ?>
        <p class="text-center text-warning">No log-ins found.</p>
    <?php else: ?>

        <div class="accordion" id="datesAccordion">

        <?php $i = 0; foreach ($groupedByDate as $date => $groups): ?>
        <?php
            $collapseId = "c$i";
            $friendly = date('F j, Y', strtotime($date));
        ?>

        <div class="accordion-item mb-3" style="background:transparent; border:none;">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                    <span style="color:#FFD700; font-weight:700;"><?= $friendly ?></span>
                </button>
            </h2>

            <div id="<?= $collapseId ?>" class="accordion-collapse collapse">
                <div class="accordion-body">

                <?php foreach ($groups as $groupKey => $students): ?>
                    <?php list($year, $block) = explode('-', $groupKey); ?>

                    <p class="group-title">Year <?= $year ?> | Block <?= $block ?></p>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Sessions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $r=1; foreach ($students as $sid => $sessions): ?>
                                <tr>
                                    <td><?= $r++ ?></td>
                                    <td><?= $sessions[0]['surname'] . ', ' . $sessions[0]['firstname'] ?></td>
                                    <td><?= $sessions[0]['school_id'] ?></td>
                                    <td><?= count($sessions) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endforeach; ?>

                </div>
            </div>
        </div>

        <?php $i++; endforeach; ?>

        </div>
    <?php endif; ?>

</div>

<footer>
    Developed by <strong>Limetares Group</strong> — S.Y. 2025–2026
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
    let f = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none';
    });
});
</script>

</body>
</html>