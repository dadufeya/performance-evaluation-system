<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$teacher_id = $_POST['teacher_id'] ?? null;

if (!$teacher_id) {
    header('Location: view-evaluations.php?error=invalid');
    exit();
}

try {
    // Get teacher info
    $teacherStmt = $pdo->prepare("
        SELECT t.*, u.username, d.department_name, c.course_name
        FROM teachers t
        JOIN users u ON t.user_id = u.user_id
        LEFT JOIN departments d ON t.department_id = d.department_id
        LEFT JOIN courses c ON t.course_id = c.course_id
        WHERE t.teacher_id = ?
    ");
    $teacherStmt->execute([$teacher_id]);
    $teacher = $teacherStmt->fetch();
    
    if (!$teacher) {
        header('Location: view-evaluations.php?error=notfound');
        exit();
    }
    
    // IMPORTANT: Release all evaluations for this teacher
    $releaseStmt = $pdo->prepare("UPDATE evaluations SET released = 1 WHERE teacher_id = ?");
    $releaseStmt->execute([$teacher_id]);
    $releasedCount = $releaseStmt->rowCount();
    
    // Get evaluation stats
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT e.evaluation_id) as total_evals,
            AVG(CASE WHEN q.question_type IN ('rating', 'scale') THEN CAST(er.answer_value AS DECIMAL(5,2)) ELSE NULL END) as overall_avg
        FROM evaluations e
        JOIN evaluation_responses er ON e.teacher_id = er.teacher_id
        JOIN questions q ON er.question_id = q.question_id
        WHERE e.teacher_id = ?
    ");
    $statsStmt->execute([$teacher_id]);
    $stats = $statsStmt->fetch();
    
    $score = round((($stats['overall_avg'] ?? 0) / 5) * 100, 1);
    
    // Success message
    $_SESSION['notification_sent'] = true;
    $_SESSION['notification_teacher'] = $teacher['full_name'];
    $_SESSION['notification_score'] = $score;
    $_SESSION['notification_count'] = $releasedCount;
    
    header('Location: view-evaluations.php?sent=success&teacher=' . urlencode($teacher['full_name']) . '&score=' . $score);
    exit();
    
} catch (PDOException $e) {
    error_log('Error sending notification: ' . $e->getMessage());
    header('Location: view-evaluations.php?error=failed');
    exit();
}
