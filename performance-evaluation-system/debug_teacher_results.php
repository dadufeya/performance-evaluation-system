<?php
require_once 'config/config.php';

// Simulate teacher login - teacher_id 131 has user_id 580
$user_id = 580;

echo "=== DEBUGGING TEACHER RESULTS ===\n\n";

// 1. Get teacher info
$teacherStmt = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacherStmt->execute([$user_id]);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    die("Teacher not found for user_id: $user_id\n");
}

echo "Teacher Found:\n";
echo "- teacher_id: " . $teacher['teacher_id'] . "\n";
echo "- full_name: " . $teacher['full_name'] . "\n";
echo "- user_id: " . $teacher['user_id'] . "\n\n";

$teacher_id = $teacher['teacher_id'];

// 2. Check evaluations
echo "=== EVALUATIONS TABLE ===\n";
$evalStmt = $pdo->prepare("SELECT * FROM evaluations WHERE teacher_id = ?");
$evalStmt->execute([$teacher_id]);
$evals = $evalStmt->fetchAll();
echo "Total evaluations for this teacher: " . count($evals) . "\n";
foreach ($evals as $eval) {
    echo "- evaluation_id: {$eval['evaluation_id']}, released: {$eval['released']}, student_id: {$eval['student_id']}\n";
}
echo "\n";

// 3. Check evaluation_responses
echo "=== EVALUATION_RESPONSES TABLE ===\n";
$respStmt = $pdo->prepare("SELECT COUNT(*) as count FROM evaluation_responses WHERE teacher_id = ?");
$respStmt->execute([$teacher_id]);
$respCount = $respStmt->fetchColumn();
echo "Total responses for this teacher: $respCount\n\n";

// 4. Check released evaluations
echo "=== RELEASED EVALUATIONS CHECK ===\n";
$releasedStmt = $pdo->prepare("SELECT COUNT(*) FROM evaluations WHERE teacher_id = ? AND released = 1");
$releasedStmt->execute([$teacher_id]);
$releasedCount = $releasedStmt->fetchColumn();
echo "Released evaluations: $releasedCount\n";
echo "Has released results: " . ($releasedCount > 0 ? "YES" : "NO") . "\n\n";

// 5. Test the actual query from view-performance.php
echo "=== TESTING ACTUAL QUERY ===\n";
$testQuery = "
    SELECT 
        q.question_id,
        q.question_text,
        q.question_type,
        COUNT(er.response_id) as response_count,
        AVG(CASE WHEN q.question_type IN ('rating', 'scale') THEN CAST(er.answer_value AS DECIMAL(5,2)) ELSE NULL END) as avg_rating
    FROM questions q
    LEFT JOIN evaluation_responses er ON q.question_id = er.question_id AND er.teacher_id = ?
    LEFT JOIN evaluations e ON e.teacher_id = er.teacher_id AND e.released = 1
    WHERE q.question_id IN (
        SELECT DISTINCT question_id FROM evaluation_responses WHERE teacher_id = ?
    )
    GROUP BY q.question_id, q.question_text, q.question_type
    ORDER BY q.question_id
";
$testStmt = $pdo->prepare($testQuery);
$testStmt->execute([$teacher_id, $teacher_id]);
$results = $testStmt->fetchAll();
echo "Questions found: " . count($results) . "\n";
foreach ($results as $r) {
    echo "- Q{$r['question_id']}: {$r['question_text']} | Responses: {$r['response_count']} | Avg: " . ($r['avg_rating'] ?? 'N/A') . "\n";
}
