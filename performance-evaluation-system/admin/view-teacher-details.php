<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$teacher_id = $_GET['teacher_id'] ?? null;
if (!$teacher_id) {
    header('Location: view-evaluations.php');
    exit();
}

// Fetch teacher info
$teacherStmt = $pdo->prepare("
    SELECT t.*, d.department_name, c.course_name 
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.department_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    WHERE t.teacher_id = ?
");
$teacherStmt->execute([$teacher_id]);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    die("Teacher not found");
}

// Fetch question-by-question breakdown
$questionsStmt = $pdo->prepare("
    SELECT 
        q.question_id,
        q.question_text,
        q.question_type,
        COUNT(er.response_id) as response_count,
        AVG(CASE WHEN q.question_type IN ('rating', 'scale') THEN CAST(er.answer_value AS DECIMAL(5,2)) ELSE NULL END) as avg_rating
    FROM questions q
    LEFT JOIN evaluation_responses er ON q.question_id = er.question_id AND er.teacher_id = ?
    WHERE q.question_id IN (
        SELECT DISTINCT question_id FROM evaluation_responses WHERE teacher_id = ?
    )
    GROUP BY q.question_id, q.question_text, q.question_type
    ORDER BY q.question_id
");
$questionsStmt->execute([$teacher_id, $teacher_id]);
$questions = $questionsStmt->fetchAll();

// Fetch text feedback (anonymous)
$feedbackStmt = $pdo->prepare("
    SELECT er.answer_value, er.submitted_at, q.question_text
    FROM evaluation_responses er
    JOIN questions q ON er.question_id = q.question_id
    WHERE er.teacher_id = ? AND q.question_type = 'text'
    ORDER BY er.submitted_at DESC
");
$feedbackStmt->execute([$teacher_id]);
$textFeedback = $feedbackStmt->fetchAll();

// Calculate overall score
$overallStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT e.evaluation_id) as total_evals,
        AVG(CASE WHEN q.question_type IN ('rating', 'scale') THEN CAST(er.answer_value AS DECIMAL(5,2)) ELSE NULL END) as overall_avg
    FROM evaluations e
    JOIN evaluation_responses er ON e.teacher_id = er.teacher_id
    JOIN questions q ON er.question_id = q.question_id
    WHERE e.teacher_id = ?
");
$overallStmt->execute([$teacher_id]);
$overall = $overallStmt->fetch();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    .main-content {
        margin-left: 260px;
        padding: 30px;
        background-color: #f8fafc;
        min-height: 100vh;
    }
    
    .teacher-header {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    
    .section-card {
        background: white;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        padding: 25px;
        margin-bottom: 25px;
    }
    
    .question-item {
        padding: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 15px;
        background: #f8fafc;
    }
    
    .feedback-item {
        padding: 15px;
        border-left: 4px solid #6366f1;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 15px;
    }
</style>

<main class="main-content">
    <div class="teacher-header">
        <h1 style="margin: 0 0 10px 0; font-size: 2rem;"><?= htmlspecialchars($teacher['full_name']) ?></h1>
        <p style="margin: 0; opacity: 0.9;">
            <?= htmlspecialchars($teacher['department_name']) ?> • <?= htmlspecialchars($teacher['course_name']) ?>
        </p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 5px;">Total Evaluations</div>
            <div style="font-size: 2rem; font-weight: 800; color: #1e293b;"><?= $overall['total_evals'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 5px;">Overall Average</div>
            <div style="font-size: 2rem; font-weight: 800; color: #6366f1;">
                <?= number_format(($overall['overall_avg'] ?? 0), 2) ?> / 5.0
            </div>
        </div>
        <div class="stat-card">
            <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 5px;">Performance Score</div>
            <div style="font-size: 2rem; font-weight: 800; color: #16a34a;">
                <?= number_format((($overall['overall_avg'] ?? 0) / 5) * 100, 1) ?>%
            </div>
        </div>
    </div>

    <div class="section-card">
        <h3 style="margin: 0 0 20px 0; color: #1e293b;">Question-by-Question Breakdown</h3>
        
        <?php foreach ($questions as $q): ?>
            <div class="question-item">
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 10px;">
                    <?= htmlspecialchars($q['question_text']) ?>
                </div>
                
                <?php if ($q['question_type'] === 'rating' || $q['question_type'] === 'scale'): ?>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="flex-grow: 1; max-width: 200px; height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden;">
                            <div style="width: <?= (($q['avg_rating'] ?? 0) / 5) * 100 ?>%; height: 100%; background: #6366f1;"></div>
                        </div>
                        <span style="font-weight: 700; color: #6366f1; font-size: 1.2rem;">
                            <?= number_format($q['avg_rating'] ?? 0, 2) ?> / 5.0
                        </span>
                        <span style="color: #64748b; font-size: 0.9rem;">
                            (<?= $q['response_count'] ?> responses)
                        </span>
                    </div>
                <?php else: ?>
                    <div style="color: #64748b;">
                        <?= $q['response_count'] ?> text responses (see below)
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($textFeedback) > 0): ?>
    <div class="section-card">
        <h3 style="margin: 0 0 20px 0; color: #1e293b;">Anonymous Student Feedback</h3>
        
        <?php foreach ($textFeedback as $feedback): ?>
            <div class="feedback-item">
                <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 8px;">
                    <strong><?= htmlspecialchars($feedback['question_text']) ?></strong>
                </div>
                <div style="color: #1e293b; font-style: italic;">
                    "<?= htmlspecialchars($feedback['answer_value']) ?>"
                </div>
                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 8px;">
                    Submitted: <?= date('M d, Y', strtotime($feedback['submitted_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="margin-top: 30px;">
        <a href="view-evaluations.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">
            ← Back to All Results
        </a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
