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

// --- 2. DELETE LOGIC ---
if (isset($_GET['delete'])) {
    try {
        $tid = $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$tid]);
        header("Location: manage-teachers.php?msg=Teacher and all associated assignments removed"); exit();
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// --- 3. REGISTRATION LOGIC ---
if (isset($_POST['add_teacher'])) {
    try {
        $tid = trim($_POST['teacher_id']);
        $fullname = trim($_POST['full_name']);
        $dept_id = $_POST['dept_id'];
        $course_id = $_POST['course_id'];
        $year_id = $_POST['year_id'];
        $sect = strtoupper(trim($_POST['section']));
        $email = trim($_POST['email']);

        $pdo->beginTransaction();

        // Fetch Course & Year names for the 'teachers' table denormalized columns
        $cStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
        $cStmt->execute([$course_id]);
        $course_name = $cStmt->fetchColumn();

        $yStmt = $pdo->prepare("SELECT year_name FROM academic_years WHERE year_id = ?");
        $yStmt->execute([$year_id]);
        $year_name = $yStmt->fetchColumn();

        // Check for exact assignment duplicate
        $checkDup = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ? AND department_id = ? AND course_info = ? AND year = ? AND section = ?");
        $checkDup->execute([$tid, $dept_id, $course_name, $year_name, $sect]);
        
        if ($checkDup->fetchColumn() > 0) {
            throw new Exception("This specific assignment (Course/Year/Section) already exists for this teacher.");
        }

        // Check if User Account exists
        $checkU = $pdo->prepare("SELECT user_id FROM teachers WHERE teacher_id = ? LIMIT 1");
        $checkU->execute([$tid]);
        $u_id = $checkU->fetchColumn();

        if (!$u_id) {
            // Create New User Account
            $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE LOWER(role_name) = 'teacher' LIMIT 1");
            $roleStmt->execute(); $role_id = $roleStmt->fetchColumn();
            
            $user = generateTeacherUsername($fullname); 
            $pass = generateRandomPassword();
            
            $stmtU = $pdo->prepare("INSERT INTO users (role_id, username, password, role, full_name, status) VALUES (?, ?, ?, 'teacher', ?, 'active')");
            $stmtU->execute([$role_id, $user, password_hash($pass, PASSWORD_DEFAULT), $fullname]);
            $u_id = $pdo->lastInsertId();
            
            // Store credentials to show the admin once
            $reset_data = ['username' => $user, 'name' => $fullname, 'password' => $pass];
        }

        $stmtT = $pdo->prepare("INSERT INTO teachers (teacher_id, user_id, full_name, email, department_id, course_info, year, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtT->execute([$tid, $u_id, $fullname, $email, $dept_id, $course_name, $year_name, $sect]);
        
        $pdo->commit();
        $msg = "Assignment successfully added!";
    } catch (Exception $e) { 
        if($pdo->inTransaction()) $pdo->rollBack(); 
        $error = $e->getMessage(); 
    }
}

// --- 4. DATA FETCHING ---
$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll();
$all_courses = $pdo->query("SELECT course_id, course_name, department_id FROM courses")->fetchAll(PDO::FETCH_ASSOC);

$teachers = $pdo->query("SELECT t.teacher_id, t.full_name, d.department_name,
                         GROUP_CONCAT(CONCAT(t.course_info, ' (', t.year, ' - ', t.section, ')') SEPARATOR '||') as all_tasks
                         FROM teachers t 
                         LEFT JOIN departments d ON t.department_id = d.department_id 
                         GROUP BY t.teacher_id, t.full_name, d.department_name 
                         ORDER BY t.full_name ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    :root { --primary: #2563eb; --bg: #f8fafc; --border: #e2e8f0; --text-main: #1e293b; }
    .teacher-container { margin-left: 260px; padding: 30px; background: var(--bg); min-height: 100vh; font-family: 'Inter', sans-serif; color: var(--text-main); }
    .teacher-grid { display: grid; grid-template-columns: 380px 1fr; gap: 25px; align-items: start; }
    
    .card { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; color: #0f172a; display: flex; align-items: center; gap: 8px; }

    .form-group { margin-bottom: 18px; }
    label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: #64748b; }
    .form-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; transition: border 0.2s; }
    .form-input:focus { outline: none; border-color: var(--primary); ring: 2px solid #dbeafe; }
    
    .btn-save { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: opacity 0.2s; }
    .btn-save:hover { opacity: 0.9; }

    .badge { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; margin: 4px 2px; display: inline-block; border: 1px solid var(--border); font-weight: 500; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 14px; background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 2px solid var(--border); }
    .data-table td { padding: 16px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .data-table tr:hover { background-color: #fafafa; }

    .credentials-box { background: #fff7ed; border: 1px solid #fed7aa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .delete-link { color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600; padding: 6px 10px; border-radius: 6px; }
    .delete-link:hover { background: #fee2e2; }
</style>

<div class="teacher-container">
    <header style="margin-bottom: 30px;">
        <h2 style="margin:0;">Teacher Management</h2>
        <p style="color: #64748b; margin-top: 5px;">Assign courses to teachers and manage their system access.</p>
    </header>

    <?php if($reset_data): ?>
        <div class="credentials-box">
            <h4 style="margin:0 0 10px 0; color: #9a3412;">⚠️ Copy New Account Credentials</h4>
            <p style="font-size: 14px; margin-bottom: 10px;">Account created for <strong><?= htmlspecialchars($reset_data['name']) ?></strong>. This is shown only once.</p>
            <div style="background:#fff; padding:10px; border-radius:5px; border:1px solid #fed7aa; font-family: monospace;">
                Username: <strong><?= $reset_data['username'] ?></strong><br>
                Password: <strong><?= $reset_data['password'] ?></strong>
            </div>
        </div>
    <?php endif; ?>

    <?php if($msg): ?><div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px;">✅ <?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px;">❌ <?= $error ?></div><?php endif; ?>

    <div class="teacher-grid">
        <section class="card">
            <div class="card-title">Assign New Course</div>
            <form method="POST">
                <div class="form-group"><label>Teacher Employee ID</label><input type="text" name="teacher_id" placeholder="e.g. T-1001" class="form-input" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" placeholder="Enter Full Name" class="form-input" required></div>
                <div class="form-group"><label>Official Email</label><input type="email" name="email" placeholder="email@university.edu" class="form-input" required></div>
                
                <div class="form-group">
                    <label>Department</label>
                    <select name="dept_id" id="dept_select" class="form-input" onchange="filterCourses()" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" id="course_select" class="form-input" required disabled>
                        <option value="">-- Choose Dept First --</option>
                    </select>
                </div>

                <div style="display:flex; gap:12px;">
                    <div class="form-group" style="flex:1;">
                        <label>Academic Year</label>
                        <select name="year_id" class="form-input">
                            <?php foreach($years as $y): ?><option value="<?= $y['year_id'] ?>"><?= $y['year_name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Section</label>
                        <input type="text" name="section" placeholder="e.g. A" class="form-input" required>
                    </div>
                </div>

                <button type="submit" name="add_teacher" class="btn-save">Confirm Assignment</button>
            </form>
        </section>

        <section class="card" style="padding:0; overflow:hidden;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Teacher Details</th>
                        <th>Current Assignments</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($teachers)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:40px;">No teacher assignments found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($teachers as $t): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--text-main);"><?= htmlspecialchars($t['full_name']) ?></div>
                            <div style="font-size:12px; color:#64748b;">ID: <?= $t['teacher_id'] ?></div>
                            <div style="font-size:12px; color:#2563eb; margin-top:4px;"><?= $t['department_name'] ?></div>
                        </td>
                        <td>
                            <?php 
                                $list = explode('||', $t['all_tasks']); 
                                foreach($list as $item) echo "<span class='badge'>$item</span>"; 
                            ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="manage-teachers.php?delete=<?= $t['teacher_id'] ?>" 
                               class="delete-link" 
                               onclick="return confirm('WARNING: This will delete the Teacher Account and ALL their course assignments. Continue?')">
                               Remove All
                            </a>
                        </td>
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
    
    // Clear previous options
    cBox.innerHTML = '<option value="">-- Choose Course --</option>';
    
    const filtered = courseList.filter(c => c.department_id == deptId);
    
    if(filtered.length > 0) {
        cBox.disabled = false;
        filtered.forEach(c => {
            let opt = document.createElement('option');
            opt.value = c.course_id;
            opt.text = c.course_name;
            cBox.add(opt);
        });
    } else {
        cBox.disabled = true;
    }
}
</script>

<?php include '../includes/footer.php'; ?>