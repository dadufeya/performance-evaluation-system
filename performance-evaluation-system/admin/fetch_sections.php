<?php
require_once '../config/config.php';

if (isset($_POST['year_id'])) {
    $year_id = (int)$_POST['year_id'];
    
    // Fetch only sections belonging to the manually created year
    $stmt = $pdo->prepare("SELECT section_id, section_number FROM sections WHERE year_id = ? ORDER BY section_number ASC");
    $stmt->execute([$year_id]);
    $sections = $stmt->fetchAll();

    if ($sections) {
        echo '<option value="">-- Select Section --</option>';
        foreach ($sections as $s) {
            echo '<option value="'.htmlspecialchars($s['section_id']).'">'.htmlspecialchars($s['section_number']).'</option>';
        }
    } else {
        echo '<option value="">No sections found for this year</option>';
    }
}
?>