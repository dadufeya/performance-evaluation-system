<?php
require_once '../config/config.php';

$student_id = $_GET['student_id'] ?? null;

if ($student_id) {
    try {
        $stmt = $pdo->prepare("SELECT course_id FROM student_courses WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        header('Content-Type: application/json');
        echo json_encode($courses);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
