<?php
require_once '../config/config.php';
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';

// Prepare data (with error handling)
$results = [];
try {
    // Get average score per question for this teacher
    $sql = "SELECT q.question_text, AVG(e.answer) as avg_score, COUNT(e.evaluation_id) as total_evals
            FROM teacher_evaluations e 
            JOIN evaluation_questions q ON e.question_id = q.question_id
            WHERE e.teacher_id = ? 
            GROUP BY q.question_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching performance: " . $e->getMessage());
}
?>

<div class="dashboard-wrapper">
    <div style="max-width: 900px; margin: 0 auto;">
        <h2 style="margin-bottom: 25px; color: #1e293b;">Performance Report</h2>
        
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <?php if(empty($results)): ?>
                <div style="text-align:center; padding:30px; color:#64748b;">
                    No evaluation data available yet.
                </div>
            <?php else: ?>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0;">
                            <th style="text-align:left; padding:15px; color:#475569;">Evaluation Criterion</th>
                            <th style="text-align:center; padding:15px; color:#475569;">Score (Avg)</th>
                            <th style="text-align:center; padding:15px; color:#475569;">Responses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): 
                            $score = round($r['avg_score'], 2);
                            $color = $score >= 4 ? '#16a34a' : ($score >= 3 ? '#ca8a04' : '#dc2626');
                        ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:15px; color:#334155;"><?= htmlspecialchars($r['question_text']) ?></td>
                            <td style="padding:15px; text-align:center; font-weight:bold; color:<?= $color ?>;">
                                <?= $score ?> / 5.0
                            </td>
                            <td style="padding:15px; text-align:center; color:#64748b;">
                                <?= $r['total_evals'] ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/teacher-footer.php'; ?>