<?php
require_once "db.php";
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $email = trim($_POST['email']);
    $code  = trim($_POST['code']);

    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email=? AND code=? AND expires_at > NOW()");
    $stmt->execute([$email, $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit;
    } else {
        $message = "Invalid or expired code.";
    }
}
?>

<!-- HTML -->
<form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="code" placeholder="Verification Code" required>
    <button type="submit">Verify</button>
</form>
