<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage-courses.php?msg=deleted");
    exit();
}

// --- HANDLE ADD (Fixed with year_id) ---
if (isset($_POST['add'])) {
    $course_name = trim($_POST['course_name']);
    $department_id = $_POST['department_id'];
    $year_id = $_POST['year_id']; 
    
    if (!empty($course_name) && !empty($department_id) && !empty($year_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, department_id, year_id) VALUES (?, ?, ?)");
            $stmt->execute([$course_name, $department_id, $year_id]);
            header("Location: manage-courses.php?msg=added");
            exit();
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch Departments for Dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();

// Fetch Academic Years for Dropdown
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll();

// Fetch Courses with JOINS
$courses = $pdo->query("SELECT c.*, d.department_name, y.year_name 
                        FROM courses c 
                        JOIN departments d ON c.department_id = d.department_id 
                        JOIN academic_years y ON c.year_id = y.year_id
                        ORDER BY y.year_name DESC, c.course_name ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Course Management</h1>
            <p class="page-subtitle">Assign courses to departments and academic years.</p>
        </div>
    </header>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" style="margin-top: 20px;"><?= $error ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            <?= $_GET['msg'] == 'added' ? '✅ Course successfully registered!' : '🗑️ Course removed successfully.' ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Add New Course</h3>
                <form method="post" class="admin-form">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Academic Year</label>
                        <select name="year_id" class="form-select" required>
                            <option value="">-- Select Year --</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y['year_id'] ?>"><?= htmlspecialchars($y['year_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Course Title</label>
                        <input type="text" name="course_name" class="form-control" placeholder="e.g. Advanced Mathematics" required>
                    </div>

                    <button type="submit" name="add" class="btn-publish" style="width: 100%; border:none; cursor:pointer;">Save Course</button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Course Directory</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Year</th>
                            <th>Department</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($courses): ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td style="font-weight:700;"><?= htmlspecialchars($c['course_name']) ?></td>
                                    <td><span class="badge-pending" style="background:#e0f2fe; color:#0369a1;"><?= htmlspecialchars($c['year_name']) ?></span></td>
                                    <td><span class="badge-pending" style="background:#f1f5f9; color:#475569;"><?= htmlspecialchars($c['department_name']) ?></span></td>
                                    <td style="text-align:right;">
                                        <a href="?delete=<?= $c['course_id'] ?>" 
                                           style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.85rem;"
                                           onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color:#94a3b8;">No courses registered yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>