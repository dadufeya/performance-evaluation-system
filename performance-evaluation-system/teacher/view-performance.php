<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('teacher');

// Get teacher info from session
$user_id = $_SESSION['user_id'];

// Fetch teacher details
$teacherStmt = $pdo->prepare("
    SELECT t.*, d.department_name, c.course_name 
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.department_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    WHERE t.user_id = ?
");
$teacherStmt->execute([$user_id]);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    die("Teacher profile not found");
}

$teacher_id = $teacher['teacher_id'];

// Check if results are released
$releasedStmt = $pdo->prepare("SELECT COUNT(*) FROM evaluations WHERE teacher_id = ? AND released = 1");
$releasedStmt->execute([$teacher_id]);
$hasReleasedResults = $releasedStmt->fetchColumn() > 0;

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
    JOIN evaluations e ON e.teacher_id = er.teacher_id AND e.released = 1
    WHERE q.question_id IN (
        SELECT DISTINCT question_id FROM evaluation_responses WHERE teacher_id = ?
    )
    GROUP BY q.question_id, q.question_text, q.question_type
    ORDER BY q.question_id
");
$questionsStmt->execute([$teacher_id, $teacher_id]);
$questions = $questionsStmt->fetchAll();

// Fetch anonymous text feedback
$feedbackStmt = $pdo->prepare("
    SELECT er.answer_value, er.submitted_at, q.question_text
    FROM evaluation_responses er
    JOIN questions q ON er.question_id = q.question_id
    JOIN evaluations e ON e.teacher_id = er.teacher_id AND e.released = 1
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
    WHERE e.teacher_id = ? AND e.released = 1
");
$overallStmt->execute([$teacher_id]);
$overall = $overallStmt->fetch();

require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';
?>

<style>
    .main-content {
        margin-left: 260px;
        padding: 30px;
        background-color: #f8fafc;
        min-height: 100vh;
    }
    
    .performance-header {
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
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .section-card {
        background: white;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
    
    .no-data-message {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }
</style>

<main class="main-content">
    <?php if (!$hasReleasedResults): ?>
        <div class="section-card">
            <div class="no-data-message">
                <i class="fas fa-clock" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px; display: block;"></i>
                <h3 style="color: #1e293b; margin-bottom: 10px;">Results Pending Release</h3>
                <p>Your evaluation results have not been released yet by the administration.</p>
                <p style="font-size: 0.9rem; color: #94a3b8;">Please check back later.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="performance-header">
            <h1 style="margin: 0 0 10px 0; font-size: 2rem;">My Performance Report</h1>
            <p style="margin: 0; opacity: 0.9;">
                <?= htmlspecialchars($teacher['department_name']) ?> • <?= htmlspecialchars($teacher['course_name']) ?>
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Total Evaluations</div>
                <div style="font-size: 2.5rem; font-weight: 800; color: #1e293b;"><?= $overall['total_evals'] ?? 0 ?></div>
                <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px;">Student responses</div>
            </div>
            <div class="stat-card">
                <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Overall Average</div>
                <div style="font-size: 2.5rem; font-weight: 800; color: #6366f1;">
                    <?= number_format(($overall['overall_avg'] ?? 0), 2) ?>
                </div>
                <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px;">Out of 5.0</div>
            </div>
            <div class="stat-card">
                <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Performance Score</div>
                <div style="font-size: 2.5rem; font-weight: 800; color: #16a34a;">
                    <?= number_format((($overall['overall_avg'] ?? 0) / 5) * 100, 1) ?>%
                </div>
                <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px;">Overall rating</div>
            </div>
        </div>

        <div class="section-card">
            <h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 1.3rem;">
                <i class="fas fa-chart-bar" style="color: #6366f1; margin-right: 10px;"></i>
                Question-by-Question Breakdown
            </h3>
            
            <?php if (count($questions) > 0): ?>
                <?php foreach ($questions as $q): ?>
                    <div class="question-item">
                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 15px; font-size: 1.05rem;">
                            <?= htmlspecialchars($q['question_text']) ?>
                        </div>
                        
                        <?php if ($q['question_type'] === 'rating' || $q['question_type'] === 'scale'): ?>
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <div style="flex-grow: 1; max-width: 300px; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                    <div style="width: <?= (($q['avg_rating'] ?? 0) / 5) * 100 ?>%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7);"></div>
                                </div>
                                <span style="font-weight: 700; color: #6366f1; font-size: 1.3rem;">
                                    <?= number_format($q['avg_rating'] ?? 0, 2) ?> / 5.0
                                </span>
                                <span style="color: #64748b; font-size: 0.9rem; background: white; padding: 4px 12px; border-radius: 20px; border: 1px solid #e2e8f0;">
                                    <?= $q['response_count'] ?> responses
                                </span>
                            </div>
                        <?php else: ?>
                            <div style="color: #64748b; background: white; padding: 8px 12px; border-radius: 8px; display: inline-block;">
                                <i class="fas fa-comment-dots"></i> <?= $q['response_count'] ?> text responses (see below)
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #94a3b8; text-align: center; padding: 20px;">No rating questions found.</p>
            <?php endif; ?>
        </div>

        <?php if (count($textFeedback) > 0): ?>
        <div class="section-card">
            <h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 1.3rem;">
                <i class="fas fa-comments" style="color: #6366f1; margin-right: 10px;"></i>
                Anonymous Student Feedback
            </h3>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;">
                Student identities are kept confidential to encourage honest feedback.
            </p>
            
            <?php foreach ($textFeedback as $feedback): ?>
                <div class="feedback-item">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 8px; font-weight: 600;">
                        <i class="fas fa-question-circle"></i> <?= htmlspecialchars($feedback['question_text']) ?>
                    </div>
                    <div style="color: #1e293b; font-style: italic; padding: 10px; background: white; border-radius: 6px;">
                        "<?= htmlspecialchars($feedback['answer_value']) ?>"
                    </div>
                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 8px;">
                        <i class="fas fa-calendar"></i> Submitted: <?= date('M d, Y', strtotime($feedback['submitted_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>