<?php
require_once "db.php";

$message = "";
$showForm = false;

// Check if token exists in URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verify token in database
    $query = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
    $query->execute([$token]);
    $resetData = $query->fetch(PDO::FETCH_ASSOC);

    if ($resetData) {
        // Check expiration
        if (strtotime($resetData['expires_at']) > time()) {
            $email = $resetData['email'];
            $showForm = true; // Show reset form
        } else {
            $message = "Your reset link has expired.";
        }
    } else {
        $message = "Invalid or used token.";
    }
}

// Handle password update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'];
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $message = "Password does not match.";
    } else {
        // Get the reset record again
        $query = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
        $query->execute([$token]);
        $resetData = $query->fetch(PDO::FETCH_ASSOC);

        if ($resetData) {
            $email = $resetData['email'];

            // Hash the new password
            $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);

            // Check which table contains the user
            $tables = ["admin", "instructor", "students"];
            $updated = false;

            foreach ($tables as $tbl) {
                $check = $pdo->prepare("SELECT email FROM $tbl WHERE email = ?");
                $check->execute([$email]);

                if ($check->fetch()) {
                    // Update password
                    $update = $pdo->prepare("UPDATE $tbl SET password = ? WHERE email = ?");
                    $update->execute([$hashedPass, $email]);
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                // Delete token after successful reset
                $delete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $delete->execute([$email]);

                $message = "Your password has been successfully updated!";
                $showForm = false;
            } else {
                $message = "Error: Email not found in any account table.";
            }
        } else {
            $message = "Invalid token.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#eef2ff;">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 shadow">
                <h3 class="text-center text-primary mb-3">Reset Password</h3>

                <?php if(!empty($message)): ?>
                    <div class="alert alert-info text-center"><?= $message ?></div>
                <?php endif; ?>

                <?php if($showForm): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
                <?php endif; ?>

                <a href="login.php" class="btn btn-secondary w-100 mt-3">Back to Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
