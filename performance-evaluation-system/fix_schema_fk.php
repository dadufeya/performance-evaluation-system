<?php
require_once 'config/config.php';

try {
    echo "Starting schema fix...\n";

    // 1. Drop existing foreign key on evaluation_responses
    // The error message said: CONSTRAINT `fk_question` FOREIGN KEY (`question_id`) REFERENCES `evaluation_questions`
    echo "Dropping legacy foreign key fk_question...\n";
    $pdo->exec("ALTER TABLE evaluation_responses DROP FOREIGN KEY fk_question");
    echo "Dropped successfully.\n";

    // 2. Add new foreign key pointing to the unified 'questions' table
    echo "Adding new foreign key pointing to 'questions' table...\n";
    $pdo->exec("ALTER TABLE evaluation_responses ADD CONSTRAINT fk_question_unified FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE");
    echo "Added successfully.\n";

    // 3. Optional: Sync evaluation_responses table structure if needed (ensure it has questionnaire_id)
    // Looking at my previous findings, evaluate-teacher.php sends questionnaire_id but save-evaluation.php might need to store it.
    // Let's check if evaluation_responses has questionnaire_id
    $stmt = $pdo->query("SHOW COLUMNS FROM evaluation_responses LIKE 'questionnaire_id'");
    if (!$stmt->fetch()) {
        echo "Adding questionnaire_id column to evaluation_responses for better tracking...\n";
        $pdo->exec("ALTER TABLE evaluation_responses ADD COLUMN questionnaire_id INT(11) AFTER course_id");
        $pdo->exec("ALTER TABLE evaluation_responses ADD CONSTRAINT fk_resp_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(questionnaire_id) ON DELETE CASCADE");
        echo "Column and FK added.\n";
    }

    echo "Schema fix completed successfully!\n";
} catch (Exception $e) {
    echo "Fix failed: " . $e->getMessage() . "\n";
    echo "If 'Dropped successfully' appeared, the second step might have failed because of data inconsistency.\n";
}
