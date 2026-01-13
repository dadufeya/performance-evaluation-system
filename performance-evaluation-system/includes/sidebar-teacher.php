<?php
// Get the current script name to determine the active page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root {
        --tch-primary: #0d9488; /* Professional Teal */
        --tch-dark: #064e3b;
        --sidebar-width: 260px;
    }

    .teacher-sidebar {
        width: var(--sidebar-width);
        background: #0f172a; /* Keeping the dark theme for consistency */
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        flex-direction: column;
        z-index: 1100;
    }

    .sidebar-brand {
        padding: 30px 25px;
        color: white;
        font-weight: 800;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar-brand i { color: var(--tch-primary); }

    .nav-menu { flex: 1; padding: 10px 15px; }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #94a3b8;
        text-decoration: none;
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 5px;
        transition: 0.3s;
        font-size: 0.95rem;
    }

    .nav-item:hover, .nav-item.active {
        background: rgba(13, 148, 136, 0.1);
        color: white;
    }

    /* Teal active indicator */
    .nav-item.active {
        background: var(--tch-primary);
        color: white;
    }

    .nav-item i {
        width: 20px;
        text-align: center;
    }

    .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
    .logout-link { color: #f87171; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }
</style>

<aside class="teacher-sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-chalkboard-teacher"></i>
        <span>Teacher Portal</span>
    </div>

    <div class="nav-menu">
        <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>

        <a href="view-performance.php" class="nav-item <?php echo $current_page === 'view-performance.php' ? 'active' : ''; ?>">
            <i class="fas fa-poll"></i> View Performance
        </a>

        <a href="feedback.php" class="nav-item <?php echo $current_page === 'feedback.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i> Student Feedback
        </a>

        <a href="complaints.php" class="nav-item <?php echo $current_page === 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> My Complaints
        </a>

        <a href="profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> Profile Settings
        </a>

        <a href="change_password.php" class="nav-item <?php echo $current_page === 'change_password.php' ? 'active' : ''; ?>">
            <i class="fas fa-key"></i> Change Password
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>