<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Ensure only students can access
checkAccess('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

// Get student ID from session
$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    die("Student not logged in.");
}

// Get Teacher and Course IDs
$teacher_id = $_POST['teacher_id'] ?? null;
$course_id = $_POST['course_id'] ?? null;

if (!$teacher_id || !$course_id) {
    die("Missing teacher or course information.");
}

// Prepare submitted answers
$answers = $_POST['answers'] ?? [];

if (empty($answers)) {
    die("No answers submitted.");
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // 2. Already Evaluated Check (Redundant safety check)
    // (This matches the new logic: prevent duplicate entries per teacher/course)
    // We do NOT create an 'evaluations' parent record because the table doesn't seem to link correctly.
    // Instead we check if any response exists.
    
    $check = $pdo->prepare("SELECT response_id FROM evaluation_responses WHERE student_id=? AND teacher_id=? AND course_id=? LIMIT 1");
    $check->execute([$student_id, $teacher_id, $course_id]);
    if ($check->fetch()) {
         die("You have already submitted an evaluation for this teacher and course.");
    }

    $inserted = 0;

    foreach ($answers as $question_id => $answer) {
        $val = '';
        if (is_array($answer)) {
            $val = implode(',', $answer); 
        } else {
            $val = trim($answer);
            // Quick cleanup for boolean '1'/'0' or text
        }

        // Insert response using verified schema:
        // (question_id, student_id, teacher_id, course_id, answer_value, submitted_at)
        $stmt = $pdo->prepare("
            INSERT INTO evaluation_responses
            (question_id, student_id, teacher_id, course_id, answer_value, submitted_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $question_id,
            $student_id,
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
