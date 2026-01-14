<?php
require_once 'config/config.php';
echo "--- Questionnaires ---\n";
$q = $pdo->query("SELECT * FROM questionnaires");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Questions ---\n";
$q = $pdo->query("SELECT * FROM questions LIMIT 10");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Evaluation Questions ---\n";
$q = $pdo->query("SELECT * FROM evaluation_questions LIMIT 10");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
