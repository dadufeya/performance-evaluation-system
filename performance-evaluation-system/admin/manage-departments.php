<?php
require_once('../config/config.php');
require_once('../includes/auth.php');
checkAccess('admin');

$msg = "";
$error = "";

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Manual Add
    if (isset($_POST['add_department'])) {
        $name = trim($_POST['department_name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO departments (department_name) VALUES (?)");
            $stmt->execute([$name]);
            $msg = "added";
        }
    }

    // 2. CSV Import
    if (isset($_POST['import_csv']) && $_FILES['csv_file']['size'] > 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        fgetcsv($handle); // Skip header row
        
        $pdo->beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if(!empty($row[0])) {
                    $stmt = $pdo->prepare("INSERT INTO departments (department_name) VALUES (?)");
                    $stmt->execute([$row[0]]);
                }
            }
            $pdo->commit();
            $msg = "imported";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error importing CSV: " . $e->getMessage();
        }
    }
}

// Fetch Departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Department Management</h1>
            <p class="page-subtitle">Add or import academic departments and colleges.</p>
        </div>
    </header>

    <?php if($msg): ?> 
        <div class="alert alert-success" style="margin-top: 20px;">
            ✅ <?= ($msg == 'added') ? 'Department added successfully!' : 'CSV Data imported successfully!' ?>
        </div> 
    <?php endif; ?>
    
    <?php if($error): ?> 
        <div class="alert alert-danger" style="margin-top: 20px;">❌ <?= $error ?></div> 
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Add Manually</h3>
                <form method="POST" class="admin-form">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Department Name</label>
                        <input type="text" name="department_name" class="form-control" placeholder="e.g. College of Science" required>
                    </div>
                    <button type="submit" name="add_department" class="btn-publish" style="width: 100%; border:none; cursor:pointer;">Add Department</button>
                </form>

                <hr class="card-divider" style="margin: 25px 0;">

                <h3 class="section-heading">Bulk CSV Import</h3>
                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 10px;">Format: .csv (Column: department_name)</p>
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <input type="file" name="csv_file" accept=".csv" required 
                           style="width:100%; border: 2px dashed #cbd5e1; padding: 20px; background: #f8fafc; border-radius: 8px; margin-bottom: 10px;">
                    <button type="submit" name="import_csv" class="btn-generate" style="width:100%; border:none; cursor:pointer;">Upload & Import</button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Existing Departments</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="10%">#</th>
                            <th width="60%">Department Name</th>
                            <th width="30%" style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($departments): ?>
                            <?php foreach ($departments as $index => $dept): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong style="color:#0f172a;"><?= htmlspecialchars($dept['department_name']) ?></strong></td>
                                <td style="text-align:right;">
                                    <a href="edit-dept.php?id=<?= $dept['department_id'] ?>" 
                                       style="color:var(--primary-blue); text-decoration:none; font-weight:600; font-size:0.85rem; margin-right:15px;">Edit</a>
                                    <a href="delete-dept.php?id=<?= $dept['department_id'] ?>" 
                                       style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.85rem;"
                                       onclick="return confirm('Delete this department?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 40px; color:#94a3b8;">No departments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php include('../includes/footer.php'); ?>