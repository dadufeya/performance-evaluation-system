<?php
require_once 'config/config.php';
function desc($table) {
    global $pdo;
    echo "\n--- $table ---\n";
    try {
        $q = $pdo->query("DESCRIBE $table");
        $cols = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "{$c['Field']} | {$c['Type']} | {$c['Null']} | {$c['Key']} | {$c['Default']} | {$c['Extra']}\n";
        }
    } catch (Exception $e) { echo "Error: " . $e->getMessage(); }
}
desc('students');
desc('courses');
desc('teachers');
desc('sections');
?>
