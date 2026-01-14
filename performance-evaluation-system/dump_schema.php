<?php
require_once 'config/config.php';
$output = "";
function desc($table) {
    global $pdo, $output;
    $output .= "\n--- $table ---\n";
    try {
        $q = $pdo->query("DESCRIBE $table");
        $cols = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            $output .= "{$c['Field']} | {$c['Type']} | {$c['Null']} | {$c['Key']} | {$c['Default']} | {$c['Extra']}\n";
        }
    } catch (Exception $e) { $output .= "Error: " . $e->getMessage(); }
}
desc('teachers');
desc('evaluation_assignments');
desc('evaluation_responses');
desc('evaluations');
file_put_contents('schema_output.txt', $output);
echo "Dumped to schema_output.txt";
?>
