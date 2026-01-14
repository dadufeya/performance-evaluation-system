<?php
require_once 'config/config.php';

try {
    $pdo->beginTransaction();

    // 1. Get unique categories from evaluation_questions
    $stmt = $pdo->query("SELECT DISTINCT category FROM evaluation_questions");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($categories as $cat) {
        if (empty($cat)) continue;

        // 2. Check if a questionnaire with this title already exists
        $stmt = $pdo->prepare("SELECT questionnaire_id FROM questionnaires WHERE title = ?");
        $stmt->execute([$cat]);
        $q_id = $stmt->fetchColumn();

        if (!$q_id) {
            // Create it
            $stmt = $pdo->prepare("INSERT INTO questionnaires (title, status) VALUES (?, 'active')");
            $stmt->execute([$cat]);
            $q_id = $pdo->lastInsertId();
            echo "Created questionnaire: $cat (ID $q_id)\n";
        }

        // 3. Move questions for this category
        $stmt = $pdo->prepare("SELECT * FROM evaluation_questions WHERE category = ?");
        $stmt->execute([$cat]);
        $ev_qs = $stmt->fetchAll();

        foreach ($ev_qs as $ev_q) {
            // Check if question already exists in questions table for this questionnaire
            $stmt = $pdo->prepare("SELECT question_id FROM questions WHERE questionnaire_id = ? AND question_text = ?");
            $stmt->execute([$q_id, $ev_q['question_text']]);
            if (!$stmt->fetch()) {
                // Insert it
                $stmt = $pdo->prepare("INSERT INTO questions (questionnaire_id, question_text, question_type) VALUES (?, ?, ?)");
                // Map types
                $type = $ev_q['question_type'];
                // questions table has enum('rating','yesno','text')
                // evaluation_questions has enum('scale','boolean','text')
                if ($type == 'scale') $type = 'rating';
                if ($type == 'boolean') $type = 'yesno';
                
                $stmt->execute([$q_id, $ev_q['question_text'], $type]);
                echo "  Added question: " . substr($ev_q['question_text'], 0, 50) . "...\n";
            }
        }
    }

    $pdo->commit();
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Migration failed: " . $e->getMessage());
}
