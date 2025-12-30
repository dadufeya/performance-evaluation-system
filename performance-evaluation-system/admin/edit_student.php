<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

checkAccess('admin');

$student = null;
if (isset($_GET['id'])) {
    // Join with users table to get the printable Username/Student ID
    $stmt = $pdo->prepare("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?");
    $stmt->execute([$_GET['id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$student) { header("Location: manage-students.php"); exit(); }

$error = "";

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    try {
        $stmt = $pdo->prepare("UPDATE students SET full_name=?, gender=?, batch=?, semester=?, department_id=?, year_id=?, section_id=? WHERE student_id=?");
        $stmt->execute([
            $_POST['full_name'], 
            $_POST['gender'], 
            $_POST['batch'], 
            $_POST['semester'], 
            $_POST['department_id'], 
            $_POST['year_id'], 
            $_POST['section_id'],
            $_POST['student_id']
        ]);
        header("Location: manage-students.php?msg=" . urlencode("Student profile updated successfully!"));
        exit();
    } catch (PDOException $e) {
        $error = "Update Error: " . $e->getMessage();
    }
}

// FETCH DROPDOWNS
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$years = $pdo->query("SELECT * FROM academic_years")->fetchAll(PDO::FETCH_ASSOC);
$sections = $pdo->query("SELECT * FROM sections")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<style>
    .edit-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
    .glass-card {
        background: #ffffff;
        padding: 35px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .full-width { grid-column: span 2; }
    
    .btn-group { display: flex; gap: 15px; margin-top: 30px; }
    .btn-update { flex: 2; background: #2563eb; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 1rem; }
    .btn-back { flex: 1; background: #f1f5f9; color: #475569; text-align: center; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: 700; border: 1px solid #e2e8f0; }
    .btn-delete { flex: 1; background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; }
    
    .btn-update:hover { background: #1d4ed8; }
    .btn-back:hover { background: #e2e8f0; }
    .btn-delete:hover { background: #fee2e2; }

    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 0.9rem; }
    .id-badge { background: #eff6ff; color: #2563eb; padding: 5px 12px; border-radius: 6px; font-family: monospace; font-weight: 700; }
</style>

<main class="main-content">
    <div class="edit-container">
        <header style="margin-bottom: 30px;">
            <h1 class="page-title" style="margin-bottom: 5px;">Edit Student Profile</h1>
            <p style="color: #64748b;">Currently editing: <span class="id-badge"><?= $student['username'] ?></span></p>
        </header>

        <?php if($error): ?>
            <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:10px; margin-bottom:20px; border: 1px solid #fecaca;"><?= $error ?></div>
        <?php endif; ?>

        <div class="glass-card">
            <form method="POST">
                <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Batch (Year)</label>
                        <input type="text" name="batch" class="form-control" value="<?= htmlspecialchars($student['batch']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Semester</label>
                        <input type="number" name="semester" class="form-control" value="<?= $student['semester'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                            <?php foreach($departments as $d): ?>
                                <option value="<?= $d['department_id'] ?>" <?= $student['department_id'] == $d['department_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Academic Year</label>
                        <select name="year_id" class="form-select" required>
                            <?php foreach($years as $y): ?>
                                <option value="<?= $y['year_id'] ?>" <?= $student['year_id'] == $y['year_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($y['year_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-select" required>
                            <?php foreach($sections as $s): ?>
                                <option value="<?= $s['section_id'] ?>" <?= $student['section_id'] == $s['section_id'] ? 'selected' : '' ?>>
                                    Section <?= htmlspecialchars($s['section_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="update" class="btn-update">Update Profile</button>
                    <a href="manage-students.php" class="btn-back">Cancel</a>
                    <a href="manage-students.php?delete=<?= $student['student_id'] ?>" class="btn-delete" onclick="return confirm('Are you sure? This action cannot be undone.')">Delete Student</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>