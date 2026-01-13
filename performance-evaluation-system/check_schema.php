<?php
require_once 'config/config.php';
echo "<h2>Teachers Table</h2>";
$q = $pdo->query("DESCRIBE teachers");
echo "<pre>"; print_r($q->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";

echo "<h2>Users Table</h2>";
$q = $pdo->query("DESCRIBE users");
echo "<pre>"; print_r($q->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";
?>
