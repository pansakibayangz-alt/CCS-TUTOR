<?php
require_once "db.php";

$stmt = $pdo->prepare("SELECT * FROM email_settings LIMIT 1");
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($config) {
    $smtpHost  = $config['smtp_host'];
    $smtpUser  = $config['smtp_user'];
    $smtpPass  = $config['smtp_pass'];
    $smtpPort  = $config['smtp_port'];
    $fromEmail = $config['from_email'];
    $fromName  = $config['from_name'];
} else {
    die("No email settings found in database.");
}
?>
