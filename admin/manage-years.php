<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

checkAccess('admin');

$msg = "";
$error = "";

// --- 1. THE FIX: WRAP DELETE IN TRY-CATCH ---
if (isset($_GET['delete'])) {
    $year_id = (int)$_GET['delete']; // Security: Force ID to be an integer
    
    try {
        // We use a Prepared Statement for better performance and security
        $stmt = $pdo->prepare("DELETE FROM academic_years WHERE year_id = ?");
        $stmt->execute([$year_id]);
        
        // Redirect to prevent "Confirm Form Resubmission" on refresh
        header("Location: manage-years.php?success=deleted");
        exit();
        
    } catch (PDOException $e) {
        // SQLSTATE 23000 is the specific code for Foreign Key/Integrity errors
        if ($e->getCode() == "23000") {
            $error = "<strong>Cannot Delete:</strong> This year is currently assigned to one or more <b>Sections</b>. You must delete those sections first to maintain data integrity.";
        } else {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Check for success message after redirect
if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $msg = "Academic Year removed successfully!";
}

// --- 2. ADD YEAR LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_year'])) {
    $year_name = trim($_POST['year_name']);
    if (!empty($year_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO academic_years (year_name) VALUES (?)");
            $stmt->execute([$year_name]);
            $msg = "Academic Year <b>$year_name</b> added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding year: " . $e->getMessage();
        }
    }
}

// --- 3. FETCH DATA ---
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <h1 class="page-title">Academic Year Management</h1>
        <p class="page-subtitle">Manage the time periods for evaluations.</p>
    </header>

    <?php if($msg): ?>
        <div class="alert alert-success" style="border-left: 5px solid #10b981; background: #ecfdf5; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            ✅ <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger" style="border-left: 5px solid #ef4444; background: #fef2f2; padding: 15px; margin-bottom: 20px; border-radius: 8px; color: #b91c1c;">
            ❌ <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid" style="display: grid; grid-template-columns: 350px 1fr; gap: 20px;">
        
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading" style="margin-bottom:15px;">Add New Year</h3>
                <form method="POST" class="admin-form">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Year Label</label>
                    <input type="text" name="year_name" class="form-control" placeholder="e.g. 2024/2025" required>
                    <button type="submit" name="add_year" class="btn-publish" style="width:100%; margin-top:15px; cursor:pointer;">
                        Save Academic Year
                    </button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <table class="data-table" style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                            <th style="padding:12px;">ID</th>
                            <th style="padding:12px;">Year Name</th>
                            <th style="padding:12px; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($years): ?>
                            <?php foreach($years as $y): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding:12px; color:#64748b;">#<?= $y['year_id'] ?></td>
                                <td style="padding:12px;"><strong><?= htmlspecialchars($y['year_name']) ?></strong></td>
                                <td style="padding:12px; text-align:right;">
                                    <a href="manage-years.php?delete=<?= $y['year_id'] ?>" 
                                       class="btn-action btn-delete" 
                                       style="padding:6px 12px; background:#fee2e2; color:#dc2626; text-decoration:none; border-radius:4px; font-size:0.85rem;"
                                       onclick="return confirm('Warning: If sections are linked to this year, deletion will be blocked. Continue?')">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding:30px; color:#94a3b8;">No academic years found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php include '../includes/footer.php'; ?>