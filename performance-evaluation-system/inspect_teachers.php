<?php
require_once 'config/config.php';
$q = $pdo->query("SELECT full_name, year, section FROM teachers LIMIT 10");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
