<?php require_once __DIR__ . '/../config/constants.php'; ?>
<style>
    :root {
        --teacher-primary: #0d9488; /* Teal Accent */
        --topbar-bg: rgba(255, 255, 255, 0.9);
        --glass-border: rgba(226, 232, 240, 0.8);
        --text-main: #1e293b;
        --text-muted: #64748b;
    }

    /* --- GLASSMORPHISM TOPBAR --- */
    .teacher-topbar {
        background: var(--topbar-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--glass-border);
        height: 70px;
        display: flex;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1100;
    }

    .topbar-container {
        padding: 0 30px;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo-text h2 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a; /* Deep Navy */
        letter-spacing: -1px;
    }

    .logo-text h2 span { color: var(--teacher-primary); }

    .topbar-nav { display: flex; align-items: center; gap: 15px; }

    /* --- PROFILE PILL & NAV --- */
    .nav-item {
        text-decoration: none;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        padding: 8px 12px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .nav-item:hover { 
        color: var(--teacher-primary); 
        background: rgba(13, 148, 136, 0.05); 
    }

    /* --- ADMIN-STYLE LOGOUT BUTTON --- */
    .btn-logout {
        background: #ef4444;
        color: white;
        text-decoration: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);
        transition: 0.3s;
    }

    .btn-logout:hover { 
        background: #dc2626; 
        transform: translateY(-1px); 
        box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
    }

    /* Avatar Circle */
    .user-avatar {
        width: 35px;
        height: 35px;
        background: var(--teacher-primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
    }

    /* --- GLOBAL LAYOUT WRAPPER --- */
    .dashboard-wrapper {
        margin-left: 260px; /* Same as sidebar width */
        margin-top: 70px;  /* Same as topbar height */
        padding: 40px;
        min-height: calc(100vh - 70px);
        background-color: #f8fafc;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
</style>

<header class="teacher-topbar">
    <div class="topbar-container">
        <div class="logo-section">
            <div class="logo-text">
                <h2>PES<span>Teacher</span></h2>
            </div>
        </div>

        <nav class="topbar-nav">
            <div class="user-avatar">
                <?= strtoupper($_SESSION['username'][0] ?? 'T'); ?>
            </div>
            
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            
            <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 5px;"></div>
            
            <a href="<?= BASE_URL ?>logout.php" class="btn-logout">Logout</a>
        </nav>
    </div>
</header>