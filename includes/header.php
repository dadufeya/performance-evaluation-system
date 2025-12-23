<?php require_once __DIR__ . '/../config/constants.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PES Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --sidebar-dark: #0f172a; /* Deep Navy */
            --sidebar-hover: #1e293b;
            --topbar-bg: rgba(255, 255, 255, 0.9);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-border: rgba(226, 232, 240, 0.8);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            padding-top: 70px; /* Space for fixed header */
        }

        /* --- ATTRACTIVE TOPBAR --- */
        .topbar {
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
            color: var(--sidebar-dark);
            letter-spacing: -1px;
        }

        .logo-text h2 span { color: var(--primary-blue); }

        .topbar-nav { display: flex; align-items: center; gap: 15px; }

        .nav-item {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .nav-item:hover { color: var(--primary-blue); background: rgba(59, 130, 246, 0.05); }

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

        .btn-logout:hover { background: #dc2626; transform: translateY(-1px); }

        /* --- ATTRACTIVE SIDEBAR --- */
        .sidebar {
            width: 260px;
            background: var(--sidebar-dark);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 90px; /* Start below topbar */
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }

        .sidebar-menu { list-style: none; padding: 0 15px; margin: 0; }

        .menu-label {
            color: #475569;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 25px 0 10px 15px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar-link.active {
            background: var(--primary-blue);
            color: #fff;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .sidebar-link .icon { font-size: 1.1rem; }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-container">
        <div class="logo-section">
            <div class="logo-text">
                <h2>PES<span>Admin</span></h2>
            </div>
        </div>

        <nav class="topbar-nav">
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-item">Dashboard</a>
            <a href="<?= BASE_URL ?>profile.php" class="nav-item">Profile</a>
            <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 10px;"></div>
            <a href="<?= BASE_URL ?>logout.php" class="btn-logout">Logout</a>
        </nav>
    </div>
</header>

<aside class="sidebar">
    <div class="sidebar-menu">
        <div class="menu-label">Main Navigation</div>
        <a href="dashboard.php" class="sidebar-link active">
            <span class="icon">📊</span> Dashboard
        </a>
        
        <div class="menu-label">Management</div>
        <a href="manage-years.php" class="sidebar-link">
            <span class="icon">📅</span> Academic Years
        </a>
        <a href="manage-sections.php" class="sidebar-link">
            <span class="icon">🏫</span> Sections
        </a>
        <a href="manage-courses.php" class="sidebar-link">
            <span class="icon">📚</span> Courses
        </a>
        <a href="manage-departments.php" class="sidebar-link">
            <span class="icon">🏢</span> Departments
        </a>
    </div>
</aside>