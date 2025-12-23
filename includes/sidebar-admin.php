<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <span>ADMIN PANEL</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="icon-dashboard"></i> <span>Dashboard</span>
        </a>
        <div class="nav-divider">Management</div>
        <a href="manage-students.php" class="sidebar-item <?= ($current_page == 'manage-students.php') ? 'active' : '' ?>">
            <i class="icon-students"></i> <span>Students</span>
        </a>
        <a href="manage-teachers.php" class="sidebar-item <?= ($current_page == 'manage-teachers.php') ? 'active' : '' ?>">
            <i class="icon-teachers"></i> <span>Teachers</span>
        </a>
        <a href="manage-departments.php" class="sidebar-item <?= ($current_page == 'manage-departments.php') ? 'active' : '' ?>">
            <i class="icon-departments"></i> <span>Departments</span>
        </a>
        <div class="nav-divider">Academic</div>
        <a href="manage-years.php" class="sidebar-item <?= ($current_page == 'manage-years.php') ? 'active' : '' ?>">
            <i class="icon-years"></i> <span>Years</span>
        </a>
        <a href="manage-sections.php" class="sidebar-item <?= ($current_page == 'manage-sections.php') ? 'active' : '' ?>">
            <i class="icon-sections"></i> <span>Sections</span>
        </a>
        <a href="manage-courses.php" class="sidebar-item <?= ($current_page == 'manage-courses.php') ? 'active' : '' ?>">
            <i class="icon-courses"></i> <span>Courses</span>
        </a>
        <div class="nav-divider">Reports</div>
        <a href="create-questionnaire.php" class="sidebar-item <?= ($current_page == 'create-questionnaire.php') ? 'active' : '' ?>">
            <i class="icon-questionnaire"></i> <span>Questionnaire</span>
        </a>
        <a href="view-evaluations.php" class="sidebar-item <?= ($current_page == 'view-evaluations.php') ? 'active' : '' ?>">
            <i class="icon-evaluations"></i> <span>Evaluations</span>
        </a>
        <a href="complaints.php" class="sidebar-item <?= ($current_page == 'complaints.php') ? 'active' : '' ?>">
            <i class="icon-complaints"></i> <span>Complaints</span>
        </a>
    </nav>
</aside>