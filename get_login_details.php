<?php
require_once 'db.php';

$name = $_GET['name'] ?? '';

if(!$name){
    echo "No student selected";
    exit;
}

// Split fullname
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

// GET LOGIN RECORDS
$stmt = $pdo->prepare("
    SELECT login_time, logout_time
    FROM student_logins
    WHERE student_id=?
    ORDER BY login_time DESC
");
$stmt->execute([$student_id]);
$logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TABLE
echo "<table class='table table-dark table-bordered'>";
echo "<thead>
<tr>
<th>No. of Login/s</th>
<th>Login Time</th>
<th>Logout Time</th>
</tr>
</thead><tbody>";

$count = 1;

foreach($logins as $row){

    echo "<tr>
        <td>{$count}</td>
        <td>{$row['login_time']}</td>
        <td>".($row['logout_time'] ?? 'N/A')."</td>
    </tr>";

    $count++;
}

echo "</tbody></table>";