<?php
require_once 'db.php'; // PDO connection

// ✅ ADD THIS FUNCTION
function generateUniqueKey($prefix = 'KEY') {
    return strtoupper($prefix . '-' . bin2hex(random_bytes(4)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';

    // COMMON FIELDS
    $surname = trim($_POST['surname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($role) || empty($surname) || empty($firstname) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields.";
        $status = "error";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            if ($role === 'ADMIN') {
                $username = trim($_POST['admin_username'] ?? '');
                $position = $_POST['position'] ?? '';
                $degree_designation = trim($_POST['degree_designation'] ?? 'N/A');

                if (empty($username) || empty($position)) {
                    $message = "Please fill in all admin required fields.";
                    $status = "error";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = "Username or Email already exists.";
                        $status = "error";
                    } else {

                        // ✅ ADD UNIQUE KEY
                        $unique_key = generateUniqueKey('ADMIN');

                        $insert = $pdo->prepare("
                            INSERT INTO admin (username, password, email, unique_key, surname, firstname, middlename, degree_designation, position)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert->execute([$username, $password_hash, $email, $unique_key, $surname, $firstname, $middlename, $degree_designation, $position]);

                        $message = "Admin registered successfully! Unique Key: " . $unique_key;
                        $status = "success";
                    }
                }

            } elseif ($role === 'INSTRUCTOR') {
                $username = trim($_POST['instructor_username'] ?? '');
                $degree_designation = trim($_POST['degree_designation_instructor'] ?? 'N/A');

                if (empty($username)) {
                    $message = "Please fill in all instructor required fields.";
                    $status = "error";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM instructor WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = "Username or Email already exists.";
                        $status = "error";
                    } else {

                        // ✅ ADD UNIQUE KEY
                        $unique_key = generateUniqueKey('INST');

                        $insert = $pdo->prepare("
                            INSERT INTO instructor (username, password, email, unique_key, surname, firstname, middlename, degree_designation)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert->execute([$username, $password_hash, $email, $unique_key, $surname, $firstname, $middlename, $degree_designation]);

                        $message = "Instructor registered successfully! Unique Key: " . $unique_key;
                        $status = "success";
                    }
                }

            } elseif ($role === 'STUDENT') {
                $school_id = trim($_POST['school_id'] ?? '');
                $facebook_name = trim($_POST['facebook_name'] ?? '');
                $phone_number = trim($_POST['phone_number'] ?? '');
                $year_level = $_POST['year_level'] ?? '';
                $block = $_POST['block'] ?? '';

                if (empty($school_id) || empty($facebook_name) || empty($year_level) || empty($block)) {
                    $message = "Please fill in all student required fields.";
                    $status = "error";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ? OR facebook_name = ?");
                    $stmt->execute([$school_id, $facebook_name]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = "School ID or Facebook Name already exists.";
                        $status = "error";
                    } else {

                        // ✅ ADD UNIQUE KEY
                        $unique_key = generateUniqueKey('STU');

                        $insert = $pdo->prepare("
                            INSERT INTO students (school_id, password, unique_key, facebook_name, surname, firstname, middlename, year_level, block, phone_number)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert->execute([$school_id, $password_hash, $unique_key, $facebook_name, $surname, $firstname, $middlename, $year_level, $block, $phone_number]);

                        $message = "Student registered successfully! Unique Key: " . $unique_key;
                        $status = "success";
                    }
                }
            } else {
                $message = "Invalid role selected.";
                $status = "error";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $status = "error";
        }
    }
} else {
    $message = "Invalid request method.";
    $status = "error";
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registration Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg,#6a0dad,#c39bd3);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .card {
    border-radius: 20px;
    padding: 2rem;
    background: rgba(255,255,255,0.95);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    text-align: center;
  }
  .btn-primary, .btn-secondary {
    border-radius: 12px;
    font-weight: 600;
  }
  .btn-primary { background: linear-gradient(135deg,#6a0dad,#c39bd3); border:none; color:#fff; }
  .btn-primary:hover { background: linear-gradient(135deg,#4b0082,#a569bd); }
  .btn-secondary { background:#ccc; border:none; color:#333; }
  .alert-success { background: #d4edda; color: #155724; border-radius: 12px; }
  .alert-error { background: #f8d7da; color: #721c24; border-radius: 12px; }
</style>
</head>
<body>
<div class="card">
    <h3>Registration Status</h3>
    <div class="my-3 alert-<?php echo $status === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <div class="d-flex justify-content-center gap-2">
        <a href="register.php" class="btn btn-primary">Register Another User</a>
        <a href="login.php" class="btn btn-secondary">Back to Login</a>
    </div>
</div>
</body>
</html>
