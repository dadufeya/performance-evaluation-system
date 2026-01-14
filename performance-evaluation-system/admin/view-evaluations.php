<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

// Fetch aggregated performance data from the unified system
$query = "SELECT 
            t.teacher_id,
            t.full_name AS teacher, 
            c.course_name,
            d.department_name,
            COUNT(DISTINCT e.evaluation_id) AS total_evaluations,
            COUNT(DISTINCT er.response_id) AS total_responses,
            AVG(CASE WHEN q.question_type IN ('rating', 'scale') THEN CAST(er.answer_value AS DECIMAL(5,2)) ELSE NULL END) AS avg_rating,
            (AVG(CASE WHEN q.question_type IN ('rating', 'scale') THEN CAST(er.answer_value AS DECIMAL(5,2)) ELSE NULL END) / 5) * 100 AS performance_percentage
          FROM teachers t
          LEFT JOIN departments d ON t.department_id = d.department_id
          LEFT JOIN courses c ON t.course_id = c.course_id
          LEFT JOIN evaluations e ON t.teacher_id = e.teacher_id
          LEFT JOIN evaluation_responses er ON e.teacher_id = er.teacher_id
          LEFT JOIN questions q ON er.question_id = q.question_id
          GROUP BY t.teacher_id, t.full_name, c.course_name, d.department_name
          ORDER BY performance_percentage DESC";

$results = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<style>
    .main-content {
        margin-left: 260px;
        padding: 30px;
        background-color: #f8fafc;
        min-height: 100vh;
    }
    
    .page-header {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }
    
    .page-subtitle {
        color: #64748b;
        margin-top: 5px;
    }
    
    .btn-generate {
        background: #6366f1;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }
    
    .btn-generate:hover {
        background: #4f46e5;
    }
    
    .section-card {
        background: white;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        padding: 25px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
    }
    
    .data-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .data-table td {
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .data-table tr:hover {
        background-color: #f8fafc;
    }
    
    .view-details-btn {
        background: #6366f1;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s;
    }
    
    .view-details-btn:hover {
        background: #4f46e5;
    }
</style>

<main class="main-content">
    <?php if (isset($_GET['sent']) && $_GET['sent'] === 'success'): ?>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> 
            <strong>Results Sent!</strong> 
            Evaluation results (<?= htmlspecialchars($_GET['score'] ?? '0') ?>% score) have been released to <strong><?= htmlspecialchars($_GET['teacher'] ?? 'teacher') ?></strong>. 
            They can now view their performance in their dashboard.
        </div>
    <?php endif; ?>
    
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Performance Analytics</h1>
            <p class="page-subtitle">Detailed breakdown of faculty evaluations and student feedback scores.</p>
        </div>
        <div class="header-actions">
            <button onclick="window.print()" class="btn-generate">
                <i class="fas fa-file-pdf"></i> Export PDF Report
            </button>
        </div>
    </header>

    <section class="list-section">
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Faculty Ranking</h3>
                <span style="font-size: 0.85rem; color: #64748b;">Total Faculty Scored: <?= count($results) ?></span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th width="25%">Instructor</th>
                        <th width="15%">Department</th>
                        <th width="15%">Course</th>
                        <th width="12%">Evaluations</th>
                        <th width="18%">Average Score</th>
                        <th width="10%">Status</th>
                        <th width="5%" style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($results && count($results) > 0): ?>
                        <?php foreach ($results as $r): 
                            $score = round($r['performance_percentage'] ?? 0, 1);
                            $statusText = 'No Data';
                            $color = "#94a3b8";

                            if($score >= 85) { $statusText = "Excellent"; $color = "#16a34a"; }
                            elseif($score >= 70) { $statusText = "Good"; $color = "#2563eb"; }
                            elseif($score >= 50) { $statusText = "Satisfactory"; $color = "#ca8a04"; }
                            elseif($score > 0) { $statusText = "Needs Review"; $color = "#ef4444"; }
                        ?>
                        <tr>
                            <td>
                                <strong style="color:#0f172a;"><?= htmlspecialchars($r['teacher']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($r['department_name'] ?? 'N/A') ?></td>
                            <td>
                                <small style="color:#64748b;"><?= htmlspecialchars($r['course_name'] ?? 'General') ?></small>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #475569;">
                                    <?= $r['total_evaluations'] ?>
                                </span> <small style="color: #94a3b8;">entries</small>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex-grow: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; max-width: 100px;">
                                        <div style="width: <?= $score ?>%; height: 100%; background: <?= $color ?>;"></div>
                                    </div>
                                    <strong style="color: <?= $color ?>;"><?= $score ?>%</strong>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.75rem; font-weight: 700; color: <?= $color ?>; text-transform: uppercase;">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <?php if ($r['total_evaluations'] > 0): ?>
                                        <a href="view-teacher-details.php?teacher_id=<?= $r['teacher_id'] ?>" class="view-details-btn" style="background: #6366f1;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <form method="POST" action="send-teacher-notification.php" style="display: inline;">
                                            <input type="hidden" name="teacher_id" value="<?= $r['teacher_id'] ?>">
                                            <button type="submit" class="view-details-btn" style="background: #16a34a; border: none; cursor: pointer;" title="Send results notification to teacher">
                                                <i class="fas fa-paper-plane"></i> Send
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.85rem;">No data</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 50px; color: #94a3b8;">No evaluation data available yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>