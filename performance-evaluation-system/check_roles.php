<?php
require_once 'config/config.php';
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
echo "Roles:\n";
print_r($roles);
?>
