<?php require_once __DIR__ . '/../config/constants.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Performance Evaluation System</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-container">
        <div class="logo">
            <img src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
            <h2>Performance Evaluation System</h2>
        </div>
        <nav class="topbar-nav">
            <a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>profile.php">Profile</a>
            <a href="<?= BASE_URL ?>logout.php">Logout</a>
        </nav>
    </div>
</header>

<style>
    .topbar {
        background: linear-gradient(90deg, #007bff, #0056b3);
        color: #fff;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }

    .topbar-container {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo img {
        height: 40px;
        width: 40px;
    }

    .logo h2 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: bold;
    }

    .topbar-nav {
        display: flex;
        gap: 20px;
    }

    .topbar-nav a {
        color: #fff;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 500;
        transition: color 0.3s, transform 0.2s;
    }

    .topbar-nav a:hover {
        color: #d1e7ff;
        transform: scale(1.1);
    }
</style>