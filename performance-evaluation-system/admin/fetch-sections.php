<?php
require_once '../config/config.php';

// We only need year_id because your 'sections' table only contains year_id
$year_id = $_GET['year_id'] ?? null;

if ($year_id) {
    try {
        // Query matching your exact columns: section_id, year_id, section_number
        $stmt = $pdo->prepare("SELECT section_id, section_number FROM sections WHERE year_id = ? ORDER BY section_number ASC");
        $stmt->execute([$year_id]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($sections);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}