<?php
require_once 'config/config.php';
$tables = ['questionnaires', 'questions', 'evaluation_questions', 'evaluation_assignments'];
$output = "";
foreach($tables as $t) {
    $output .= "\n--- $t ---\n";
    try {
        $res = $pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
        foreach($res as $r) {
            $output .= "{$r['Field']} ({$r['Type']})\n";
        }
    } catch (Exception $e) {
        $output .= "Error: " . $e->getMessage() . "\n";
    }
}
file_put_contents('schema_dump_v4.txt', $output);
echo "Dumped to schema_dump_v4.txt";
