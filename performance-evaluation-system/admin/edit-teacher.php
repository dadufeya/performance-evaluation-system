<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('admin');

$msg = "";
$error = "";
$teacher_id = $_GET['id'] ?? null;

if (!$teacher_id) {
    header("Location: manage-teachers.php");
    exit();
}

// --- 1. HANDLE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_teacher'])) {
    try {
        $pdo->beginTransaction();

        $fullname = trim($_POST['full_name']);
        $email    = trim($_POST['email']);
        $dept_id  = $_POST['dept_id'];
        $course   = trim($_POST['course_info']);
        $year     = trim($_POST['year']);
        $section  = trim($_POST['section']);
        $user_id  = $_POST['user_id'];

        // Update Users table (keeps names in sync)
        $stmtU = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmtU->execute([$fullname, $user_id]);

        // Update Teachers table (including new year and section)
        $stmtT = $pdo->prepare("UPDATE teachers SET full_name = ?, email = ?, department_id = ?, course_info = ?, year = ?, section = ? WHERE teacher_id = ?");
        $stmtT->execute([$fullname, $email, $dept_id, $course, $year, $section, $teacher_id]);

        $pdo->commit();
        header("Location: manage-teachers.php?msg=" . urlencode("Teacher updated successfully!"));
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Update Error: " . $e->getMessage();
    }
}

// --- 2. FETCH TEACHER DATA ---
$stmt = $pdo->prepare("SELECT t.*, u.user_id FROM teachers t JOIN users u ON t.user_id = u.user_id WHERE t.teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die("Teacher not found.");
}

$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header" style="display: flex; align-items: center; gap: 15px;">
        <a href="manage-teachers.php" class="btn-action" style="text-decoration: none; padding: 8px 12px; background: #f1f5f9; border-radius: 50%; color: #2563eb; font-size: 1.2rem; font-weight: bold;">
            &#10229;
        </a>
        <h1 class="page-title" style="margin:0;">Edit Teacher Profile</h1>
    </header>

    <?php if($error): ?> <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <section class="form-section" style="max-width: 650px; margin-top: 20px;">
        <div class="section-card">
            <form method="POST" class="admin-form">
                <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Teacher ID</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($teacher['teacher_id']) ?>" disabled style="background:#f8fafc;">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top:15px;">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email']) ?>" required>
                </div>

                <div class="form-group" style="margin-top:15px;">
                    <label>Department</label>
                    <select name="dept_id" class="form-select" required>
                        <?php foreach($depts as $d): ?>
                            <option value="<?= $d['department_id'] ?>" <?= $d['department_id'] == $teacher['department_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-top:15px;">
                    <label>Primary Course</label>
                    <input type="text" name="course_info" class="form-control" value="<?= htmlspecialchars($teacher['course_info']) ?>" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px; margin-bottom:25px;">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($teacher['year'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section" class="form-control" value="<?= htmlspecialchars($teacher['section'] ?? '') ?>" required>
                    </div>
                </div>

                <button type="submit" name="update_teacher" class="btn-publish" style="width:100%;">Update Profile</button>
            </form>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>