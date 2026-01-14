<?php
require_once 'config/config.php';
$q = $pdo->query("SELECT COUNT(*) FROM questionnaires");
echo "Count: " . $q->fetchColumn();
?>
