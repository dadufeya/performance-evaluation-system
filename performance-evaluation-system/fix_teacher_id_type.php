<?php
require_once 'config/config.php';

echo "Starting advanced migration...\n";
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Add 'id' column as the new Primary Key
    echo "Adding 'id' column and making it PK...\n";
    // First, drop the old PK (teacher_id)
    // We already dropped FKs in the previous script.
    
    try {
        $pdo->exec("ALTER TABLE teachers DROP PRIMARY KEY");
    } catch (Exception $e) { echo "Note: PK already dropped or not found.\n"; }

    // Add new auto-increment PK
    $pdo->exec("ALTER TABLE teachers ADD id INT AUTO_INCREMENT PRIMARY KEY FIRST");
    
    // Ensure teacher_id is VARCHAR(50) and not unique
    $pdo->exec("ALTER TABLE teachers MODIFY teacher_id VARCHAR(50) NOT NULL");
    
    // 2. Add Index to teacher_id for performance
    $pdo->exec("CREATE INDEX idx_teacher_id ON teachers(teacher_id)");

    echo "SUCCESS: Teachers table refactored to support multiple assignments.\n";
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
?>
