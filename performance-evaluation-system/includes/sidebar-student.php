<?php
// Get the current script name to determine the active page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .student-sidebar {
        width: var(--sidebar-width);
        background: #0f172a;
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

    .sidebar-brand i { color: #6366f1; }

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
    }

    .nav-item:hover, .nav-item.active {
        background: rgba(99, 102, 241, 0.1);
        color: white;
    }

    .nav-item.active i { color: #6366f1; }

    .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
    .logout-link { color: #f87171; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }
</style>

<aside class="student-sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-graduation-cap"></i>
        <span>PES Portal</span>
    </div>

    <div class="nav-menu">
        <a href="student_dashboard.php" class="nav-item <?php echo $current_page === 'student_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
      
        <a href="evaluation-history.php" class="nav-item <?php echo $current_page === 'evaluation-history.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> My History
        </a>
        <a href="my-teachers.php" class="nav-item <?php echo $current_page === 'my-teachers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> My Teachers
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> Profile
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>