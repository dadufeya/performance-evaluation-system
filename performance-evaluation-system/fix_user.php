<?php
require_once 'config/config.php';
$new_pass = password_hash('123456', PASSWORD_DEFAULT);
// This updates the user 'abebe' to ensure the password and role are correct
$sql = "UPDATE users SET password = ?, role_id = 2 WHERE username = 'abebe'";
$pdo->prepare($sql)->execute([$new_pass]);
echo "User 'abebe' updated. Password is now: 123456";
?>