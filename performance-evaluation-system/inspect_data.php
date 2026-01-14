<?php
require_once 'config/config.php';
echo "--- Teachers Data (Top 5) ---\n";
$q = $pdo->query("SELECT * FROM teachers LIMIT 5");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Evaluation Responses Data (Top 5) ---\n";
$q = $pdo->query("SELECT * FROM evaluation_responses LIMIT 5");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Evaluations Data (Top 5) ---\n";
$q = $pdo->query("SELECT * FROM evaluations LIMIT 5");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Users Data (Top 5 Teachers) ---\n";
$q = $pdo->query("SELECT u.* FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'teacher' LIMIT 5");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
