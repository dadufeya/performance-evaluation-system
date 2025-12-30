<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch count of pending complaints for the notification badge
try {
    $pending_stmt = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'");
    $pending_count = $pending_stmt->fetchColumn();
} catch (Exception $e) {
    $pending_count = 0;
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="icon">🛡️</span>
        <span>ADMIN PANEL</span>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label">Main Navigation</div>
        <a href="dashboard.php" class="sidebar-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        
        <div class="menu-label">User Management</div>
        <a href="manage-students.php" class="sidebar-link <?= ($current_page == 'manage-students.php') ? 'active' : '' ?>">
            <span class="icon">👨‍🎓</span> Students
        </a>
        <a href="manage-teachers.php" class="sidebar-link <?= ($current_page == 'manage-teachers.php') ? 'active' : '' ?>">
            <span class="icon">👨‍🏫</span> Teachers
        </a>

        <div class="menu-label">Academic Structure</div>
        <a href="manage-years.php" class="sidebar-link <?= ($current_page == 'manage-years.php') ? 'active' : '' ?>">
            <span class="icon">📅</span> Academic Years
        </a>
        <a href="manage-departments.php" class="sidebar-link <?= ($current_page == 'manage-departments.php') ? 'active' : '' ?>">
            <span class="icon">🏢</span> Departments
        </a>
        <a href="manage-sections.php" class="sidebar-link <?= ($current_page == 'manage-sections.php') ? 'active' : '' ?>">
            <span class="icon">🏫</span> Sections
        </a>
        <a href="manage-courses.php" class="sidebar-link <?= ($current_page == 'manage-courses.php') ? 'active' : '' ?>">
            <span class="icon">📚</span> Courses
        </a>

        <div class="menu-label">Quality & Feedback</div>
        <a href="create-questionnaire.php" class="sidebar-link <?= ($current_page == 'create-questionnaire.php') ? 'active' : '' ?>">
            <span class="icon">📝</span> Questionnaire
        </a>
        <a href="view-evaluations.php" class="sidebar-link <?= ($current_page == 'view-evaluations.php') ? 'active' : '' ?>">
            <span class="icon">📈</span> Evaluations
        </a>
        
        <a href="manage-complaints.php" class="sidebar-link <?= ($current_page == 'manage-complaints.php') ? 'active' : '' ?>">
            <span class="icon">📩</span> Complaints 
            <?php if($pending_count > 0): ?>
                <span style="background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 7px; border-radius: 50px; margin-left: auto; font-weight: bold;"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
    </div>
</aside>