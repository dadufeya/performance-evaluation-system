<?php
require_once 'config/config.php';
$q = $pdo->query("DESCRIBE evaluation_responses");
echo "<pre>"; print_r($q->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";
?>
