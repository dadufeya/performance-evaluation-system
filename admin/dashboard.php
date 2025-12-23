<?php
require_once('../config/config.php');
require_once('../includes/auth.php');

// --- FETCH LIVE STATS ---
$countStudents = 0; $countTeachers = 0; $countDepts = 0; $countEvals = 0; $error = "";

try {
    $countStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $countTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
    $countDepts    = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    $countEvals    = $pdo->query("SELECT COUNT(*) FROM evaluations")->fetchColumn();
} catch (PDOException $e) {
    $error = "Stats Error: " . $e->getMessage();
}

$displayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator';

require_once '../includes/header.php'; 
require_once '../includes/sidebar-admin.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Management Dashboard</h1>
            <p class="page-subtitle">Welcome, <span class="user-highlight"><?= htmlspecialchars($displayName); ?></span>. Here is what's happening today.</p>
        </div>
        <div class="header-actions">
            <button class="btn-generate" onclick="window.print()">Print System Report</button>
        </div>
    </header>

    <section class="stats-grid">
        <div class="stat-card students">
            <div class="stat-info">
                <div class="stat-title">Total Students</div>
                <div class="stat-value"><?= number_format($countStudents) ?></div>
            </div>
            <div class="stat-icon">👥</div>
        </div>
        
        <div class="stat-card teachers">
            <div class="stat-info">
                <div class="stat-title">Faculty Members</div>
                <div class="stat-value"><?= number_format($countTeachers) ?></div>
            </div>
            <div class="stat-icon">👨‍🏫</div>
        </div>

        <div class="stat-card evals">
            <div class="stat-info">
                <div class="stat-title">Evaluations</div>
                <div class="stat-value"><?= number_format($countEvals) ?></div>
            </div>
            <div class="stat-icon">📝</div>
        </div>

        <div class="stat-card depts">
            <div class="stat-info">
                <div class="stat-title">Departments</div>
                <div class="stat-value"><?= number_format($countDepts) ?></div>
            </div>
            <div class="stat-icon">🏢</div>
        </div>
    </section>

    <div class="dashboard-secondary-grid">
        <section class="activity-section">
            <div class="section-card">
                <h3 class="section-heading">Recent Evaluations</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Teacher</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Abebe Bikila</td>
                            <td>Dr. Smith</td>
                            <td>Today, 10:45 AM</td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                        <tr>
                            <td>Sara John</td>
                            <td>Prof. Kebede</td>
                            <td>Yesterday</td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="command-section">
            <div class="action-card">
                <h4>👤 System Admin</h4>
                <p>Manage access levels and user profiles.</p>
                <a href="manage-students.php" class="action-link">Students List</a>
                <a href="manage-teachers.php" class="action-link">Teachers List</a>
                
                <hr class="card-divider">
                
                <h4>📊 Academic Tools</h4>
                <p>Control the evaluation process.</p>
                <a href="manage-courses.php" class="action-link">Courses</a>
                <a href="create-questionnaire.php" class="action-link">Questionnaire</a>
                <a href="release-results.php" class="action-link btn-highlight">Publish Results →</a>
            </div>
        </section>
    </div>
</main>

<?php include('../includes/footer.php'); ?>