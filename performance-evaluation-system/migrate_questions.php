<?php
require_once 'config/config.php';

try {
    $pdo->beginTransaction();

    // 1. Get unique categories
    $categories = $pdo->query("SELECT DISTINCT category FROM evaluation_questions")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($categories as $cat) {
        // 2. Check if questionnaire exists
        $stmt = $pdo->prepare("SELECT questionnaire_id FROM questionnaires WHERE title = ?");
        $stmt->execute([$cat]);
        $q_id = $stmt->fetchColumn();

        if (!$q_id) {
            // 3. Create questionnaire
            $stmt = $pdo->prepare("INSERT INTO questionnaires (title, status) VALUES (?, 'active')");
            $stmt->execute([$cat]);
            $q_id = $pdo->lastInsertId();
            echo "Created questionnaire: $cat (ID: $q_id)\n";
        }

        // 4. Copy questions
        $stmt = $pdo->prepare("SELECT * FROM evaluation_questions WHERE category = ?");
        $stmt->execute([$cat]);
        $questions = $stmt->fetchAll();

        foreach ($questions as $q) {
            // Check if question already exists in this questionnaire
            $stmt2 = $pdo->prepare("SELECT question_id FROM questions WHERE questionnaire_id = ? AND question_text = ?");
            $stmt2->execute([$q_id, $q['question_text']]);
            if (!$stmt2->fetchColumn()) {
                $stmt3 = $pdo->prepare("INSERT INTO questions (questionnaire_id, question_text, question_type, max_score) VALUES (?, ?, ?, ?)");
                $type = $q['question_type'] === 'scale' ? 'rating' : ($q['question_type'] === 'boolean' ? 'yesno' : 'text');
                $stmt3->execute([$q_id, $q['question_text'], $type, 5]);
            }
        }
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
