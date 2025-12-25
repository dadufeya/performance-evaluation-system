<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Ensure this matches your database column name (likely year_id based on your previous code)
    $stmt = $pdo->prepare("DELETE FROM academic_years WHERE year_id = ?"); 
    $stmt->execute([$id]);
    header("Location: manage-years.php?msg=deleted");
    exit();
}

// Handle Form Submission
$msg = "";
if (isset($_POST['add'])) {
    $year_name = trim($_POST['year_name']);
    if (!empty($year_name)) {
        $stmt = $pdo->prepare("INSERT INTO academic_years (year_name) VALUES (?)");
        $stmt->execute([$year_name]);
        header("Location: manage-years.php?msg=added");
        exit();
    }
}

// Fetch Years
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Academic Year Management</h1>
            <p class="page-subtitle">Define and manage the different year levels for the evaluation system.</p>
        </div>
    </header>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            <?= $_GET['msg'] == 'added' ? '✅ Academic Year added successfully!' : '🗑️ Academic Year removed successfully.' ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Add New Year Level</h3>
                <form method="post" class="admin-form">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; font-weight: 600;">Year Designation</label>
                        <input type="text" name="year_name" class="form-control" placeholder="e.g., 1st Year, Freshman, 2024-2025" required>
                    </div>
                    <button type="submit" name="add" class="btn-publish" style="width: 100%; border:none; cursor:pointer;">
                        Save Academic Year
                    </button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Existing Year Levels</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50%">Year Name</th>
                            <th width="25%">Status</th>
                            <th width="25%" style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($years): ?>
                            <?php foreach ($years as $y): ?>
                            <tr>
                                <td><strong style="color:#0f172a;"><?= htmlspecialchars($y['year_name']) ?></strong></td>
                                <td><span class="badge-pending" style="background:#f0fdf4; color:#16a34a; border: 1px solid #bbf7d0;">Active</span></td>
                                <td style="text-align:right;">
                                    <a href="?delete=<?= $y['year_id'] ?>" 
                                       style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.85rem;" 
                                       onclick="return confirm('Are you sure you want to delete this year? This may affect student records.')">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 40px; color:#94a3b8;">No years defined in the system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
