<?php
require_once('../config/config.php');
require_once('../includes/auth.php');
checkAccess('admin'); // Only admins can see this

include('../includes/header.php');
include('../includes/sidebar-admin.php');
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<div class="dashboard-container">
    <main class="content">
        <header class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
        </header>

        <section class="dashboard-overview">
            <div class="stats-grid">
                <div class="card">
                    <h3>Total Students</h3>
                    <p>250</p>
                </div>
                <div class="card">
                    <h3>Total Teachers</h3>
                    <p>45</p>
                </div>
                <div class="card">
                    <h3>Active Evaluations</h3>
                    <p>2</p>
                </div>
            </div>
        </section>

        <section class="quick-links">
            <h2>Quick Links</h2>
            <div class="links-grid">
                <a href="manage-students.php" class="link-card">Manage Students</a>
                <a href="manage-teachers.php" class="link-card">Manage Teachers</a>
                <a href="manage-courses.php" class="link-card">Manage Courses</a>
                <a href="manage-questionnaire.php" class="link-card">Manage Questionnaire</a>
                <a href="view-evaluations.php" class="link-card">View Evaluations</a>
                <a href="release-results.php" class="link-card">Release Results</a>
            </div>
        </section>
    </main>
</div>

<style>
    .dashboard-container {
        display: flex;
        margin-top: 60px; /* Ensures alignment with the fixed header */
    }

    .sidebar {
        flex: 0 0 250px;
        height: calc(100vh - 60px); /* Adjusted to fit below the header */
        position: fixed;
        top: 60px;
        left: 0;
        z-index: 999;
    }

    .content {
        flex: 1;
        margin-left: 250px;
        padding: 20px;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .dashboard-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .card {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .quick-links {
        margin-top: 40px;
    }

    .links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .link-card {
        display: block;
        padding: 15px;
        background: #007bff;
        color: #fff;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
        transition: background 0.3s;
    }

    .link-card:hover {
        background: #0056b3;
    }
</style>

<?php include('../includes/footer.php'); ?>