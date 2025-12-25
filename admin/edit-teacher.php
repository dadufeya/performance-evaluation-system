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
        $user_id  = $_POST['user_id'];

        // Update Users table (for full name)
        $stmtU = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmtU->execute([$fullname, $user_id]);

        // Update Teachers table
        $stmtT = $pdo->prepare("UPDATE teachers SET full_name = ?, email = ?, department_id = ?, course_info = ? WHERE teacher_id = ?");
        $stmtT->execute([$fullname, $email, $dept_id, $course, $teacher_id]);

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
    <header class="page-header">
        <h1 class="page-title">Edit Teacher Profile</h1>
        <a href="manage-teachers.php" style="color: #64748b; text-decoration: none;">← Back to Directory</a>
    </header>

    <?php if($error): ?> <div class="alert alert-danger">❌ <?= $error ?></div> <?php endif; ?>

    <section class="form-section" style="max-width: 600px; margin-top: 20px;">
        <div class="section-card">
            <form method="POST" class="admin-form">
                <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
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

                <div class="form-group" style="margin-top:15px; margin-bottom:20px;">
                    <label>Primary Course</label>
                    <input type="text" name="course_info" class="form-control" value="<?= htmlspecialchars($teacher['course_info']) ?>" required>
                </div>

                <button type="submit" name="update_teacher" class="btn-publish" style="width:100%;">Save Changes</button>
            </form>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>