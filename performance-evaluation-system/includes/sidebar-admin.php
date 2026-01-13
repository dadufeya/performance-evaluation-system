<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch count of pending complaints
try {
    $pending_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM complaints 
        WHERE status = 'pending'
    ");
    $pending_count = (int) $pending_stmt->fetchColumn();
} catch (Exception $e) {
    $pending_count = 0;
}
?>

<aside class="sidebar">

    <!-- ================= BRAND ================= -->
    <div class="sidebar-brand">
        <span class="icon">🛡️</span>
        <span>ADMIN PANEL</span>
    </div>

    <div class="sidebar-menu">

        <!-- ================= DASHBOARD ================= -->
        <div class="menu-label">Dashboard</div>
        <a href="dashboard.php"
           class="sidebar-link <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>">
            <span class="icon">📊</span>
            Dashboard
        </a>

        <!-- ================= USER MANAGEMENT ================= -->
        <div class="menu-label">User Management</div>

        <a href="manage-students.php"
           class="sidebar-link <?= ($current_page === 'manage-students.php') ? 'active' : '' ?>">
            <span class="icon">👨‍🎓</span>
            Students
        </a>

        <a href="manage-teachers.php"
           class="sidebar-link <?= ($current_page === 'manage-teachers.php') ? 'active' : '' ?>">
            <span class="icon">👨‍🏫</span>
            Teachers
        </a>

        <!-- ================= ACADEMIC STRUCTURE ================= -->
        <div class="menu-label">Academic Structure</div>

        <a href="manage-years.php"
           class="sidebar-link <?= ($current_page === 'manage-years.php') ? 'active' : '' ?>">
            <span class="icon">📅</span>
            Academic Years
        </a>

        <a href="manage-departments.php"
           class="sidebar-link <?= ($current_page === 'manage-departments.php') ? 'active' : '' ?>">
            <span class="icon">🏫</span>
            Departments
        </a>

        <a href="manage-courses.php"
           class="sidebar-link <?= ($current_page === 'manage-courses.php') ? 'active' : '' ?>">
            <span class="icon">📚</span>
            Courses
        </a>

        <!-- ================= EVALUATION WORKFLOW ================= -->
        <div class="menu-label">Evaluation Workflow</div>

        <a href="create-questionnaire.php"
           class="sidebar-link <?= ($current_page === 'create-questionnaire.php') ? 'active' : '' ?>">
            <span class="icon">📝</span>
            Questionnaire
        </a>

        <a href="create-evaluation.php"
           class="sidebar-link <?= ($current_page === 'create-evaluation.php') ? 'active' : '' ?>">
            <span class="icon">🧩</span>
            Assign Evaluation
        </a>

        <a href="view-evaluations.php"
           class="sidebar-link <?= ($current_page === 'view-evaluations.php') ? 'active' : '' ?>">
            <span class="icon">📈</span>
            View Results
        </a>

        <a href="release-results.php"
           class="sidebar-link <?= ($current_page === 'release-results.php') ? 'active' : '' ?>">
            <span class="icon">🚀</span>
            Release Results
        </a>

        <!-- ================= QUALITY & FEEDBACK ================= -->
        <div class="menu-label">Quality & Feedback</div>

        <a href="manage-complaints.php"
           class="sidebar-link <?= ($current_page === 'manage-complaints.php') ? 'active' : '' ?>">
            <span class="icon">📩</span>
            Complaints

            <?php if ($pending_count > 0): ?>
                <span class="badge">
                    <?= $pending_count ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- ================= PROFILE ================= -->
        <div class="menu-label">Account</div>

        <a href="profile.php"
           class="sidebar-link <?= ($current_page === 'profile.php') ? 'active' : '' ?>">
            <span class="icon">👤</span>
            Profile
        </a>

        <a href="../logout.php" class="sidebar-link">
            <span class="icon">🚪</span>
            Logout
        </a>

    </div>
</aside>

<style>
/* Optional badge style (safe if not in CSS file) */
.badge {
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    padding: 2px 7px;
    border-radius: 50px;
    margin-left: auto;
    font-weight: bold;
}
</style>
