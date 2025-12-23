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
            $msg = "Department added successfully!";
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
            $msg = "CSV Data imported successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error importing CSV: " . $e->getMessage();
        }
    }
}

// Fetch Departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_id DESC")->fetchAll();

include('../includes/header.php');
include('../includes/sidebar-admin.php');
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

<main class="main-content">
    <div class="page-header">
        <h1>Manage Departments</h1>
        <p>Dashboard > Academic Setup > Departments</p>
    </div>

    <?php if($msg): ?> <div class="alert alert-success"><?= $msg ?></div> <?php endif; ?>
    <?php if($error): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>

    <div class="dashboard-grid">
        <div class="card">
            <h3>Add Manually</h3>
            <form method="POST" class="form-group">
                <label>Department Name</label>
                <input type="text" name="department_name" placeholder="e.g. College of Science" required>
                <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
            </form>
        </div>

        <div class="card">
            <h3>CSV Import</h3>
            <p style="font-size: 0.8rem; color: #666;">File format: .csv (One column: department_name)</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required style="border: 2px dashed #007bff; padding: 15px; background: #f8f9fc;">
                <button type="submit" name="import_csv" class="btn btn-success" style="width:100%; margin-top: 10px;">Upload CSV</button>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div style="padding: 20px;">
            <h3 style="margin:0;">Existing Departments</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $index => $dept): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= htmlspecialchars($dept['department_name']) ?></strong></td>
                    <td>
                        <a href="edit-dept.php?id=<?= $dept['department_id'] ?>" class="btn-action btn-edit">Edit</a>
                        <a href="delete-dept.php?id=<?= $dept['department_id'] ?>" 
                           class="btn-action btn-delete" 
                           onclick="return confirm('Delete this department?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include('../includes/footer.php'); ?>