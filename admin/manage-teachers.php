<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

checkAccess('admin');

$msg = ""; $error = ""; $reset_data = null; 

// --- 1. UTILITIES ---
function generateRandomPassword($length = 8) {
    return substr(str_shuffle("abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, $length);
}

function generateTeacherUsername($fullName) {
    $parts = explode(' ', str_replace(['Dr.', 'Mr.', 'Mrs.', 'Ms.', 'Prof.'], '', trim($fullName)));
    $firstName = strtolower(trim($parts[0] ?? ''));
    $lastName = strtolower(trim($parts[count($parts) - 1] ?? ''));
    return preg_replace('/[^a-z0-9]/', '', substr($firstName, 0, 1) . $lastName) . rand(10, 99);
}

// --- 2. DELETE LOGIC (Safe Version) ---
if (isset($_GET['delete'])) {
    try {
        $tid = $_GET['delete'];
        // Deletes all assignments for this specific teacher ID since there is no row ID
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$tid]);
        header("Location: manage-teachers.php?msg=Teacher records removed"); exit();
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// --- 3. REGISTRATION WITH 4-WAY STRICT VALIDATION ---
if (isset($_POST['add_teacher'])) {
    try {
        $tid = trim($_POST['teacher_id']);
        $fullname = trim($_POST['full_name']);
        $dept_id = $_POST['dept_id'];
        $course_id = $_POST['course_id'];
        $year_id = $_POST['year_id'];
        $sect = strtoupper(trim($_POST['section'])); // Force 'E' instead of 'e'
        $email = trim($_POST['email']);

        $pdo->beginTransaction();

        // Get Course & Year names
        $cStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
        $cStmt->execute([$course_id]);
        $course_name = $cStmt->fetchColumn();

        $yStmt = $pdo->prepare("SELECT year_name FROM academic_years WHERE year_id = ?");
        $yStmt->execute([$year_id]);
        $year_name = $yStmt->fetchColumn();

        // THE "DIFFERENT BY 1 THING" CHECK
        $checkDup = $pdo->prepare("SELECT COUNT(*) FROM teachers 
                                   WHERE teacher_id = ? 
                                   AND department_id = ? 
                                   AND course_info = ? 
                                   AND year = ? 
                                   AND section = ?");
        $checkDup->execute([$tid, $dept_id, $course_name, $year_name, $sect]);
        
        if ($checkDup->fetchColumn() > 0) {
            throw new Exception("The teacher already exists with this Department, Course, Section, and Year!");
        }

        // Account Logic
        $checkU = $pdo->prepare("SELECT user_id FROM teachers WHERE teacher_id = ? LIMIT 1");
        $checkU->execute([$tid]);
        $u_id = $checkU->fetchColumn();

        if (!$u_id) {
            $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE LOWER(role_name) = 'teacher' LIMIT 1");
            $roleStmt->execute(); $role_id = $roleStmt->fetchColumn();
            $user = generateTeacherUsername($fullname); $pass = generateRandomPassword();
            $stmtU = $pdo->prepare("INSERT INTO users (role_id, username, password, role, full_name, status) VALUES (?, ?, ?, 'teacher', ?, 'active')");
            $stmtU->execute([$role_id, $user, password_hash($pass, PASSWORD_DEFAULT), $fullname]);
            $u_id = $pdo->lastInsertId();
            $reset_data = ['username' => $user, 'name' => $fullname, 'password' => $pass, 'tid' => $tid];
        }

        $stmtT = $pdo->prepare("INSERT INTO teachers (teacher_id, user_id, full_name, email, department_id, course_info, year, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtT->execute([$tid, $u_id, $fullname, $email, $dept_id, $course_name, $year_name, $sect]);
        
        $pdo->commit();
        $msg = "Success: Assignment saved!";
    } catch (Exception $e) { 
        if($pdo->inTransaction()) $pdo->rollBack(); 
        $error = $e->getMessage(); 
    }
}

// --- 4. DATA FETCHING (WITHOUT t.id) ---
$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll();
$all_courses = $pdo->query("SELECT course_id, course_name, department_id FROM courses")->fetchAll(PDO::FETCH_ASSOC);

// Using GROUP BY to show teacher only once with multiple assignments
$teachers = $pdo->query("SELECT t.teacher_id, t.full_name, d.department_name,
                         GROUP_CONCAT(CONCAT(t.course_info, ' (', t.year, ' - Sec:', t.section, ')') SEPARATOR '||') as all_tasks
                         FROM teachers t 
                         LEFT JOIN departments d ON t.department_id = d.department_id 
                         GROUP BY t.teacher_id, t.full_name, d.department_name 
                         ORDER BY t.teacher_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    .teacher-container { margin-left: 260px; padding: 30px; background: #f8fafc; min-height: 100vh; }
    .teacher-grid { display: grid; grid-template-columns: 380px 1fr; gap: 20px; align-items: start; }
    .card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; }
    .form-group { margin-bottom: 15px; }
    label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; }
    .form-input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
    .btn-save { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .badge { background: #eff6ff; color: #1e40af; padding: 5px 10px; border-radius: 4px; font-size: 12px; margin: 3px; display: inline-block; border: 1px solid #bfdbfe; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px; background: #f1f5f9; font-size: 12px; }
    .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
</style>

<div class="teacher-container">
    <h2>Manage Teacher Assignments</h2>

    <?php if($msg): ?><div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px;">✅ <?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px;">❌ <?= $error ?></div><?php endif; ?>

    <div class="teacher-grid">
        <section class="card">
            <form method="POST">
                <div class="form-group"><label>Teacher ID</label><input type="text" name="teacher_id" class="form-input" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-input" required></div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="dept_id" id="dept_select" class="form-input" onchange="filterCourses()" required>
                        <option value="">-- Select --</option>
                        <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" id="course_select" class="form-input" required disabled><option value="">-- Choose Dept First --</option></select>
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;"><label>Year</label><select name="year_id" class="form-input"><?php foreach($years as $y): ?><option value="<?= $y['year_id'] ?>"><?= $y['year_name'] ?></option><?php endforeach; ?></select></div>
                    <div class="form-group" style="flex:1;"><label>Section</label><input type="text" name="section" class="form-input" required></div>
                </div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-input" required></div>
                <button type="submit" name="add_teacher" class="btn-save">Save Assignment</button>
            </form>
        </section>

        <section class="card" style="padding:0; overflow:hidden;">
            <table class="data-table">
                <thead>
                    <tr><th>Teacher</th><th>Assignments (Dept, Course, Year, Sec)</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach($teachers as $t): ?>
                    <tr>
                        <td><b><?= $t['teacher_id'] ?></b><br><?= htmlspecialchars($t['full_name']) ?></td>
                        <td><?php $list = explode('||', $t['all_tasks']); foreach($list as $item) echo "<span class='badge'>$item</span>"; ?></td>
                        <td><a href="manage-teachers.php?delete=<?= $t['teacher_id'] ?>" style="color:red;" onclick="return confirm('Delete teacher?')">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>

<script>
const courseList = <?= json_encode($all_courses) ?>;
function filterCourses() {
    const deptId = document.getElementById('dept_select').value;
    const cBox = document.getElementById('course_select');
    cBox.innerHTML = '<option value="">-- Choose Course --</option>';
    const filtered = courseList.filter(c => c.department_id == deptId);
    if(filtered.length > 0) {
        cBox.disabled = false;
        filtered.forEach(c => { let opt = document.createElement('option'); opt.value = c.course_id; opt.text = c.course_name; cBox.add(opt); });
    } else { cBox.disabled = true; }
}
</script>

<?php include '../includes/footer.php'; ?>