<?php
// email_crypto.php
define('EMAIL_ENC_KEY', '16CharSecretKey'); // AES-128 requires 16 chars

function encryptEmail($email) {
    return openssl_encrypt($email, 'AES-128-ECB', EMAIL_ENC_KEY);
}

function decryptEmail($encryptedEmail) {
    return openssl_decrypt($encryptedEmail, 'AES-128-ECB', EMAIL_ENC_KEY);
}
?>
