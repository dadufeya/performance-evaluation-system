<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM academic_years WHERE id = ?"); // Ensure column is 'id'
    $stmt->execute([$id]);
    header("Location: manage-years.php?msg=deleted");
    exit();
}

// Handle Form Submission
$success_msg = "";
if (isset($_POST['add'])) {
    $year_name = trim($_POST['year_name']);
    if (!empty($year_name)) {
        $stmt = $pdo->prepare("INSERT INTO academic_years (year_name) VALUES (?)");
        $stmt->execute([$year_name]);
        $success_msg = "Academic Year added successfully!";
    }
}

// Fetch Years
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

<main class="main-content">
    <header class="page-header">
        <h1 class="page-title">Academic Year Management</h1>
    </header>

    <?php if ($success_msg || isset($_GET['msg'])): ?>
        <div class="alert alert-success">Operation successful!</div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <div class="section-card">
            <h3 class="section-heading">Add New Year</h3>
            <form method="post" class="admin-form">
                <div class="form-group">
                    <label>Year Name</label>
                    <input type="text" name="year_name" placeholder="e.g. 1st Year" required>
                </div>
                <button type="submit" name="add" class="btn-primary">Save Year</button>
            </form>
        </div>

        <div class="section-card">
            <h3 class="section-heading">System Academic Years</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Year Level</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($years as $y): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($y['year_name']) ?></td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td style="text-align:right;">
<a href="?delete=<?= $y['year_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>                               class="btn-delete" 
                               </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>