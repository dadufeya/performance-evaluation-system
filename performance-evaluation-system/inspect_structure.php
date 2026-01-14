<?php
require_once 'config/config.php';
echo "--- Academic Years ---\n";
$q = $pdo->query("SELECT * FROM academic_years");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Sections ---\n";
$q = $pdo->query("SELECT * FROM sections");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
