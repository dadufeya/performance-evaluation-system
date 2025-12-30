<?php
// Start output buffering to prevent random whitespace from breaking JSON
ob_start();

require_once '../config/config.php';
require_once '../includes/auth.php';

// Disable error display for the final JSON output, but log them for debugging
error_reporting(0);
ini_set('display_errors', 0);

checkAccess('admin'); 

header('Content-Type: application/json; charset=utf-8');

try {
    $dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;
    $teachers = [];

    if ($dept_id > 0) {
        // Query matching your database: table 'teachers' with 'department_id'
        $stmt = $pdo->prepare("SELECT teacher_id, full_name FROM teachers WHERE department_id = ? ORDER BY full_name ASC");
        $stmt->execute([$dept_id]);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Clear any accidental output and send clean JSON
    ob_clean();
    echo json_encode($teachers);

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["error" => "Database failure"]);
}
exit();