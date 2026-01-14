<?php
require_once 'config/config.php';
$tables = ['evaluations', 'evaluation_assignments', 'student_courses', 'teachers', 'courses', 'sections', 'students', 'evaluation_dispatches'];
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
file_put_contents('full_schema_dump.txt', $output);
echo "Dumped to full_schema_dump.txt";
