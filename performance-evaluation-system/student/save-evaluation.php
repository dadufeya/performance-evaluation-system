<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Ensure only students can access
checkAccess('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

// Get student ID from session and map to students table student_id
$stmtS = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmtS->execute([$_SESSION['user_id']]);
$student_info = $stmtS->fetch();
$real_student_id = $student_info['student_id'] ?? null;

if (!$real_student_id) {
    die("Student profile not found.");
}

// Get Teacher, Course, and Questionnaire IDs
$teacher_id = $_POST['teacher_id'] ?? null;
$course_id = $_POST['course_id'] ?? null;
$questionnaire_id = $_POST['questionnaire_id'] ?? null;

if (!$teacher_id || !$course_id || !$questionnaire_id) {
    die("Missing evaluation information.");
}

// Prepare submitted answers
$answers = $_POST['answers'] ?? [];

if (empty($answers)) {
    die("No answers submitted.");
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // 2. Already Evaluated Check
    $check = $pdo->prepare("SELECT evaluation_id FROM evaluations WHERE student_id=? AND teacher_id=? AND course_id=? LIMIT 1");
    $check->execute([$real_student_id, $teacher_id, $course_id]);
    if ($check->fetch()) {
         die("You have already submitted an evaluation for this teacher and course.");
    }

    // 3. Create Parent Evaluation Record (for status/release tracking)
    $evalStmt = $pdo->prepare("
        INSERT INTO evaluations (student_id, teacher_id, course_id, questionnaire_id, released, submitted_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $evalStmt->execute([$real_student_id, $teacher_id, $course_id, $questionnaire_id]);
    $parent_evaluation_id = $pdo->lastInsertId();

    $inserted = 0;
    foreach ($answers as $question_id => $answer) {
        $val = is_array($answer) ? implode(',', $answer) : trim($answer);

        // Insert response
        $stmt = $pdo->prepare("
            INSERT INTO evaluation_responses
            (question_id, student_id, teacher_id, course_id, answer_value, submitted_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $question_id,
            $real_student_id,
            $teacher_id,
            $course_id,
            $val
        ]);

        $inserted++;
    }

    if ($inserted === 0) {
        throw new Exception("No valid answers submitted.");
    }

    $pdo->commit();

    // Redirect back with success
    header("Location: evaluation-history.php?submitted=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Error saving evaluation: " . $e->getMessage());
}
