<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('admin');

$msg = "";
$error = "";
$teachers = []; 
$depts = [];

// --- 1. HANDLE REGISTRATION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    try {
        $pdo->beginTransaction();

        $fullname    = trim($_POST['full_name']);
        $username    = trim($_POST['username']);
        $email       = trim($_POST['email']);
        $dept_id     = $_POST['dept_id'];
        $course_info = trim($_POST['course_info']); // New variable for course
        
        // DYNAMIC ROLE CHECK
        $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'teacher' OR role_name = 'Teacher' LIMIT 1");
        $roleStmt->execute();
        $role_data = $roleStmt->fetch();
        
        if (!$role_data) {
            throw new Exception("The 'teacher' role does not exist in the roles table.");
        }
        $role_id = $role_data['role_id'];

        // Create Login Account
        $pw = password_hash('teacher123', PASSWORD_DEFAULT);
        
        $stmtUser = $pdo->prepare("INSERT INTO users (role_id, username, password, role, full_name, status) VALUES (?, ?, ?, 'teacher', ?, 'active')");
        $stmtUser->execute([$role_id, $username, $pw, $fullname]);
        $new_user_id = $pdo->lastInsertId();

        // Create Profile in 'teachers' table including course_info
        $stmtTeacher = $pdo->prepare("INSERT INTO teachers (user_id, full_name, email, department_id, course_info) VALUES (?, ?, ?, ?, ?)");
        $stmtTeacher->execute([$new_user_id, $fullname, $email, $dept_id, $course_info]);
        
        $pdo->commit();
        $msg = "Teacher and Course assigned successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Registration Error: " . $e->getMessage();
    }
}

// --- 2. FETCH DATA ---
try {
    $depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $query = "SELECT t.*, d.department_name 
              FROM teachers t 
              LEFT JOIN departments d ON t.department_id = d.department_id 
              ORDER BY t.teacher_id DESC";
    $teachers = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $error = "Fetch Error: " . $e->getMessage();
}

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

<main class="main-content">
    <div class="page-header">
        <h1>Teacher Management</h1>
        <p>Dashboard > Faculty & Course Assignment</p>
    </div>

    <?php if($msg): ?> <div class="alert alert-success" style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;"><?= $msg ?></div> <?php endif; ?>
    <?php if($error): ?> <div class="alert alert-danger" style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;"><?= $error ?></div> <?php endif; ?>

    <div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <div class="card" style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="color:#007bff; margin-bottom:20px;">Register New Teacher</h3>
            <form method="POST">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Full Name</label>
                <input type="text" name="full_name" required style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd; border-radius:5px;">
                
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Username</label>
                        <input type="text" name="username" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Email</label>
                        <input type="email" name="email" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Department</label>
                        <select name="dept_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; background:#fff;">
                            <option value="">-- Choose --</option>
                            <?php foreach($depts as $d): ?>
                                <option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Course (Code or Name)</label>
                        <input type="text" name="course_info" placeholder="e.g. CS101 or Java Programming" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                </div>

                <button type="submit" name="add_teacher" style="background:#007bff; color:#fff; border:none; padding:12px 20px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold;">Register & Assign Course</button>
            </form>
        </div>

        <div class="card" style="background:#f0f7ff; padding:25px; border-radius:10px; border:1px solid #b8daff;">
            <h4 style="margin-top:0; color:#004085;">Assignment Instructions</h4>
            <p style="font-size:14px; color:#004085;">1. Enter the teacher's personal details.</p>
            <p style="font-size:14px; color:#004085;">2. Select the parent Department.</p>
            <p style="font-size:14px; color:#004085;">3. Manually type the <strong>Course Code</strong> or <strong>Course Name</strong> they are currently teaching.</p>
        </div>
    </div>

    <div class="card" style="margin-top:30px; background:#fff; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.05); overflow:hidden;">
        <table style="width:100%; border-collapse:collapse;">
            <thead style="background:#f8f9fa;">
                <tr>
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #eee;">Name & Email</th>
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #eee;">Department</th>
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #eee;">Course Assigned</th>
                    <th style="padding:15px; text-align:center; border-bottom:2px solid #eee;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($teachers)): ?>
                    <?php foreach($teachers as $t): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:15px;">
                            <strong><?= htmlspecialchars($t['full_name']) ?></strong><br>
                            <small style="color:#666;"><?= htmlspecialchars($t['email']) ?></small>
                        </td>
                        <td style="padding:15px;"><?= htmlspecialchars($t['department_name'] ?? 'N/A') ?></td>
                        <td style="padding:15px;">
                            <span style="background:#eef2ff; color:#007bff; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:bold;">
                                <?= htmlspecialchars($t['course_info'] ?? 'No Course') ?>
                            </span>
                        </td>
                        <td style="padding:15px; text-align:center;">
                            <button style="padding:5px 10px; background:#f8f9fa; border:1px solid #ddd; border-radius:4px; cursor:pointer;">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding:30px; text-align:center; color:#999;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>