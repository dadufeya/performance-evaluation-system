<?php 
// 1. Start session and check access
if (session_status() === PHP_SESSION_NONE) session_start(); 

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/config.php';

// 2. Auth Check: If not a teacher, redirect to login
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: " . BASE_URL . "login.php?error=unauthorized");
    exit();
}

// 3. Fetch Real Stats
$teacher_id = $_SESSION['user_id'];
$stats = [
    'total' => 0,
    'avg' => 0,
    'breakdown' => []
];

try {
    // Total Evaluations & Average Rating
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, AVG(answer) as avg_rating FROM teacher_evaluations WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total'] = $result['total'] ?? 0;
    $stats['avg'] = round($result['avg_rating'] ?? 0, 1);

    // Performance Breakdown by Category
    $stmt = $pdo->prepare("
        SELECT q.category, AVG(e.answer) as score 
        FROM teacher_evaluations e
        JOIN evaluation_questions q ON e.question_id = q.question_id
        WHERE e.teacher_id = ?
        GROUP BY q.category
    ");
    $stmt->execute([$teacher_id]);
    $stats['breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending Complaints (Complaints submitted by the teacher)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$teacher_id]); // $teacher_id is actually $_SESSION['user_id']
    $stats['pending_complaints'] = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}
?>

<?php
require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';
?>

<!-- Styles are now loaded from teacher-header.php -->

<div class="dashboard-wrapper">
    <section class="welcome-section">
        <h1 style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-bottom: 8px;">Hello, Prof. <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</h1>
        <p style="color: #64748b; font-size: 1rem;">Here’s an overview of your teaching performance and student feedback.</p>
    </section>

    <!-- Inline styles for specific dashboard elements if not in global css -->
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid rgba(226, 232, 240, 0.8); display: flex; justify-content: space-between; align-items: center; transition: transform 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; display: block; }
        .stat-label { color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .icon-eval { background: #f0fdf4; color: #16a34a; }
        .icon-feedback { background: #eff6ff; color: #3b82f6; }
        .icon-complaint { background: #fef2f2; color: #ef4444; }
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        .glass-card { background: white; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
        .performance-item { margin-bottom: 20px; }
        .performance-info { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; }
        .progress-bar-bg { background: #f1f5f9; height: 10px; border-radius: 10px; overflow: hidden; }
        .progress-fill { background: #0d9488; height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }
    </style>

    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Evaluations</span>
                <span class="stat-value"><?= $stats['total'] ?></span>
            </div>
            <div class="stat-icon icon-eval"><i class="fas fa-file-signature"></i></div>
        </div>
        <div class="stat-card">
            <div>
                <span class="stat-label">Avg. Rating</span>
                <span class="stat-value"><?= $stats['avg'] ?> / 5</span>
            </div>
            <div class="stat-icon icon-feedback"><i class="fas fa-star"></i></div>
        </div>
        <div class="stat-card">
            <div>
                <span class="stat-label">Pending Complaints</span>
                <span class="stat-value"><?= $stats['pending_complaints'] ?? 0 ?></span>
            </div>
            <div class="stat-icon icon-complaint"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>

    <div class="content-grid">
        <div class="glass-card">
            <div class="card-header">
                <span class="card-title">Performance Breakdown</span>
                <a href="view-performance.php" style="color: #0d9488; text-decoration: none; font-size: 0.8rem; font-weight: 700;">View Full Report →</a>
            </div>
            
            <?php if (empty($stats['breakdown'])): ?>
                <p style="color: #64748b; font-style: italic;">No performance data available yet.</p>
            <?php else: ?>
                <?php foreach ($stats['breakdown'] as $cat): 
                    $pct = ($cat['score'] / 5) * 100;
                ?>
                <div class="performance-item">
                    <div class="performance-info">
                        <span><?= htmlspecialchars($cat['category']) ?></span>
                        <span><?= round($pct) ?>% (<?= round($cat['score'], 1) ?>)</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <div class="card-header">
                <span class="card-title">Latest Feedback</span>
            </div>
            <p style="font-size: 0.85rem; color: #64748b; line-height: 1.6; font-style: italic;">
                "The professor explains complex topics very clearly and is always available for extra help after class..."
            </p>
            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 15px 0;">
            <a href="feedback.php" style="display: block; text-align: center; color: #64748b; font-size: 0.8rem; text-decoration: none;">Read more comments</a>
        </div>
    </div>
</div>

<?php include('../includes/teacher-footer.php'); ?>