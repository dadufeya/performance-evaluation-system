<?php
require_once 'config/config.php';
$u = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$u->execute([546]);
$user = $u->fetch(PDO::FETCH_ASSOC);
echo "User 546: " . print_r($user, true);
?>
