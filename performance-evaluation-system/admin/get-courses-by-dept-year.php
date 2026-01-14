<?php
require_once '../config/config.php';

$dept_id = $_GET['dept_id'] ?? null;
$year_id = $_GET['year_id'] ?? null;

if ($dept_id && $year_id) {
    try {
        $stmt = $pdo->prepare("SELECT course_id, course_name FROM courses WHERE department_id = ? AND year_id = ? ORDER BY course_name ASC");
        $stmt->execute([$dept_id, $year_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($courses);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
