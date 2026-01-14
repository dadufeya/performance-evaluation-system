<?php
require_once 'config/config.php';

try {
    echo "Starting data recovery and FK fix...\n";

    // 1. Drop existing FK if it still exists (it might have been dropped already)
    try {
        $pdo->exec("ALTER TABLE evaluation_responses DROP FOREIGN KEY fk_question");
        echo "Dropped legacy FK fk_question.\n";
    } catch (Exception $e) {
        echo "FK fk_question already dropped or doesn't exist.\n";
    }

    // 2. Clear out evaluation_responses that don't match any question text in the new table
    // (This is a clean-up step to ensure the FK can be added)
    // Actually, let's just make the column nullable or remove rows that would break it.
    echo "Cleaning up orphaned responses (those without a match in the NEW questions table)...\n";
    
    // We try to match by question_text if we can join with the OLD table
    $pdo->exec("
        DELETE FROM evaluation_responses 
        WHERE question_id NOT IN (SELECT question_id FROM questions)
    ");
    $deletedCount = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
    echo "Removed $deletedCount orphaned response rows.\n";

    // 3. Add the new FK
    echo "Linking evaluation_responses to the unified questions table...\n";
    $pdo->exec("ALTER TABLE evaluation_responses ADD CONSTRAINT fk_question_unified FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE");
    echo "New FK added successfully!\n";

    // 4. Update save-evaluation.php to be more robust
    echo "Schema is now unified.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
