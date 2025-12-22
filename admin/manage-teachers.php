<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';        // This includes your Topbar
require_once '../includes/sidebar-admin.php';  // This includes your Sidebar

/* ======================
   ADMIN ONLY CHECK
====================== */
if ($_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit;
}

/* ======================
   ADD TEACHER LOGIC
====================== */
if (isset($_POST['add_teacher'])) {
    $full_name = trim($_POST['full_name']);
    $dept_id = $_POST['department_id'];
    $batch = $_POST['batch'];
    $semester = $_POST['semester'];

    $stmt = $pdo->prepare("INSERT INTO teachers (full_name, department_id, batch, semester) VALUES (?, ?, ?, ?)");
    $stmt->execute([$full_name, $dept_id, $batch, $semester]);
    header("Location: manage-teachers.php?success=1");
    exit;
}

/* ======================
   CSV IMPORT LOGIC
====================== */
if (isset($_POST['import_csv'])) {
    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");
        fgetcsv($file); // Skip header row
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            $stmt = $pdo->prepare("INSERT INTO teachers (full_name, department_id, batch, semester) VALUES (?, ?, ?, ?)");
            $stmt->execute([$column[0], $column[1], $column[2], $column[3]]);
        }
        fclose($file);
        header("Location: manage-teachers.php?imported=1");
        exit;
    }
}

/* ======================
   FETCH DATA
====================== */
$teachers = $pdo->query("
    SELECT t.*, d.department_name 
    FROM teachers t 
    JOIN departments d ON t.department_id = d.department_id 
    ORDER BY t.full_name
")->fetchAll();

$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<div class="main-wrapper">
    <main class="content-area">
        <header class="content-header">
            <h2>Manage Teachers</h2>
            <div class="import-box">
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <button type="submit" name="import_csv" class="btn-import">CSV Import</button>
                </form>
            </div>
        </header>

        <div class="grid-container">
            <section class="card">
                <h3>Add New Teacher</h3>
                <form method="POST" class="standard-form">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>

                    <label>Department</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Batch Year</label>
                    <input type="number" name="batch" value="<?= date('Y') ?>" required>

                    <label>Semester</label>
                    <select name="semester">
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>

                    <button type="submit" name="add_teacher" class="btn-save">Save Teacher</button>
                </form>
            </section>

            <section class="card">
                <h3>Teacher List</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Dept.</th>
                                <th>Batch/Sem</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($teachers as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['full_name']) ?></td>
                                <td><?= htmlspecialchars($t['department_name']) ?></td>
                                <td><?= $t['batch'] ?> / S<?= $t['semester'] ?></td>
                                <td>
                                    <a href="edit-teacher.php?id=<?= $t['teacher_id'] ?>" class="link-edit">Edit</a>
                                    <a href="manage-teachers.php?delete=<?= $t['teacher_id'] ?>" class="link-delete" onclick="return confirm('Delete this record?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>

