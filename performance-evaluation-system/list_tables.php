<?php
require_once 'config/config.php';
$stmt = $pdo->query("SHOW FULL TABLES");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
