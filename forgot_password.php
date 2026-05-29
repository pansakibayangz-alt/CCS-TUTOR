<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $unique_key = trim($_POST['unique_key']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {

        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // CHECK ADMIN
        $stmt = $pdo->prepare("UPDATE admin SET password=? WHERE unique_key=?");
        $stmt->execute([$hashedPassword, $unique_key]);

        if ($stmt->rowCount() > 0) {
            $message = "Password updated (ADMIN).";
        } else {

            // CHECK INSTRUCTOR
            $stmt = $pdo->prepare("UPDATE instructor SET password=? WHERE unique_key=?");
            $stmt->execute([$hashedPassword, $unique_key]);

            if ($stmt->rowCount() > 0) {
                $message = "Password updated (INSTRUCTOR).";
            } else {

                // CHECK STUDENT
                $stmt = $pdo->prepare("UPDATE students SET password=? WHERE unique_key=?");
                $stmt->execute([$hashedPassword, $unique_key]);

                if ($stmt->rowCount() > 0) {
                    $message = "Password updated (STUDENT).";
                } else {
                    $message = "Invalid Unique Key.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password (Unique Key)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f2e6ff;">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 shadow">
                <h3 class="text-center text-primary">Reset Password</h3>

                <?php if(!empty($message)): ?>
                    <div class="alert alert-info text-center"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label>Unique Key</label>
                        <input type="text" name="unique_key" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    <a href="login.php" class="btn btn-secondary w-100 mt-2">Back to Login</a>
                </form>

            </div>
        </div>
    </div>
</div>
</body>
</html>