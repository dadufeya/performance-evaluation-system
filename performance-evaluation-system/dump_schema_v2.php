<?php
require_once 'config/config.php';
$tables = ['evaluations', 'evaluation_assignments', 'student_courses', 'teachers', 'courses', 'sections', 'students'];
foreach($tables as $t) {
    echo "\n--- $t ---\n";
    $res = $pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach($res as $r) {
        echo "{$r['Field']} ({$r['Type']})\n";
    }
}
