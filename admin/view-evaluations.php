<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

// Fetch aggregated performance data
$query = "SELECT 
            t.full_name AS teacher, 
            t.course_info,
            d.department_name,
            AVG(ea.rating) AS raw_avg,
            (AVG(ea.rating) / 5) * 100 AS performance_percentage,
            COUNT(DISTINCT e.evaluation_id) AS total_evaluations
          FROM teachers t
          LEFT JOIN departments d ON t.department_id = d.department_id
          LEFT JOIN evaluations e ON t.teacher_id = e.teacher_id
          LEFT JOIN evaluation_answers ea ON e.evaluation_id = ea.evaluation_id
          GROUP BY t.teacher_id
          ORDER BY performance_percentage DESC";

$results = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Performance Analytics</h1>
            <p class="page-subtitle">Detailed breakdown of faculty evaluations and student feedback scores.</p>
        </div>
        <div class="header-actions">
            <button onclick="window.print()" class="btn-generate" style="border:none; cursor:pointer; padding: 10px 20px;">
                Export PDF Report
            </button>
        </div>
    </header>

    <section class="list-section" style="margin-top: 25px;">
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 class="section-heading" style="margin-bottom: 0;">Faculty Ranking</h3>
                <span style="font-size: 0.8rem; color: #64748b;">Total Faculty Scored: <?= count($results) ?></span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th width="30%">Instructor</th>
                        <th width="20%">Department</th>
                        <th width="15%">Evaluations</th>
                        <th width="20%">Average Score</th>
                        <th width="15%" style="text-align:right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($results): ?>
                        <?php foreach ($results as $r): 
                            $score = round($r['performance_percentage'], 1);
                            // Determine status color
                            $statusClass = 'badge-pending'; // Default
                            $statusText = 'Average';
                            $color = "#64748b";

                            if($score >= 85) { $statusText = "Excellent"; $color = "#16a34a"; }
                            elseif($score >= 70) { $statusText = "Good"; $color = "#2563eb"; }
                            elseif($score >= 50) { $statusText = "Satisfactory"; $color = "#ca8a04"; }
                            else { $statusText = "Needs Review"; $color = "#ef4444"; }
                        ?>
                        <tr>
                            <td>
                                <strong style="color:#0f172a;"><?= htmlspecialchars($r['teacher']) ?></strong><br>
                                <small style="color:#64748b;"><?= htmlspecialchars($r['course_info'] ?? 'General') ?></small>
                            </td>
                            <td><?= htmlspecialchars($r['department_name'] ?? 'N/A') ?></td>
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
                            <td style="text-align:right;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: <?= $color ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?= $statusText ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 50px; color: #94a3b8;">No evaluation data available yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>