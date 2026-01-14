<?php
require_once 'config/config.php';
ob_start();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $q = $pdo->query("DESCRIBE `$table` ");
    $columns = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']} - {$col['Default']} - {$col['Extra']}\n";
    }
    echo "\n";
}
$output = ob_get_clean();
file_put_contents('schema_dump.txt', $output);
echo "Schema dumped to schema_dump.txt";
?>
