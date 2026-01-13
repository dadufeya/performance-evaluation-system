<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('admin');

$msg = ""; $error = "";

/* ===============================
    1. ACTION HANDLERS
================================ */

// --- RESET PASSWORD ---
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    try {
        $newPass = substr(str_shuffle("23456789ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 8);
        $u = $pdo->prepare("SELECT user_id FROM teachers WHERE teacher_id = ? LIMIT 1");
        $u->execute([$_GET['tid']]);
        $uid = $u->fetchColumn();
        
        if($uid) {
            $pdo->prepare("UPDATE users SET password=?, temp_pass=? WHERE user_id=?")
                ->execute([password_hash($newPass, PASSWORD_DEFAULT), $newPass, $uid]);
            $msg = "Password for {$_GET['tid']} reset successfully. New Password: <b>$newPass</b>";
        }
    } catch (Exception $e) { $error = "Reset failed: " . $e->getMessage(); }
}

// --- DELETE ASSIGNMENT ---
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ? AND course_info = ? AND section = ?");
        $stmt->execute([$_GET['tid'], $_GET['course'], $_GET['sec']]);
        $msg = "Assignment removed successfully.";
    } catch (Exception $e) { $error = "Delete failed."; }
}

// --- EDIT UPDATE (Sync Username with First Name) ---
if (isset($_POST['update_teacher'])) {
    try {
        $pdo->beginTransaction();
        
        // Generate Username from First Name only (no numbers)
        $nameParts = explode(' ', trim($_POST['e_name']));
        $newUsername = strtolower($nameParts[0]); 

        // 1. Update Teachers table
        $stmt = $pdo->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, department_id=?, course_info=?, year=?, section=?, teacher_id=? WHERE teacher_id=?");
        $stmt->execute([
            $_POST['e_name'], 
            $_POST['e_email'], 
            $_POST['e_phone'], 
            $_POST['e_dept'], 
            $_POST['e_course'], 
            $_POST['e_year'], 
            $_POST['e_sec'], 
            $newUsername, 
            $_POST['e_tid'] 
        ]);
        
        // 2. Update Users table
        $stmt2 = $pdo->prepare("UPDATE users SET full_name=?, username=? WHERE username=?");
        $stmt2->execute([$_POST['e_name'], $newUsername, $_POST['e_tid']]);
        
        $pdo->commit(); 
        $msg = "Record updated. Username is now: <b>$newUsername</b>";
    } catch (Exception $e) { 
        if($pdo->inTransaction()) $pdo->rollBack(); 
        $error = "Update failed: " . $e->getMessage(); 
    }
}

/* ===============================
    2. HIERARCHICAL ASSIGN LOGIC
================================ */
if(isset($_POST['add_teacher'])){
    try {
        $pdo->beginTransaction();
        
        $c = $pdo->prepare("SELECT course_name FROM courses WHERE course_id=?"); 
        $c->execute([$_POST['course_id']]); $course_name = $c->fetchColumn();
        
        $y = $pdo->prepare("SELECT year_name FROM academic_years WHERE year_id=?"); 
        $y->execute([$_POST['year_id']]); $year_name = $y->fetchColumn();

        $u = $pdo->prepare("SELECT user_id FROM users WHERE username=?"); 
        $u->execute([$_POST['teacher_id']]); $usr = $u->fetch();

        if(!$usr){
            $plain = substr(str_shuffle("23456789ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 8);
            $newUsername = strtolower(trim($_POST['teacher_id']));
            $pdo->prepare("INSERT INTO users(username,password,full_name,role_id,status,temp_pass) VALUES (?,?,?,2,'active',?)")
                ->execute([$newUsername, password_hash($plain, PASSWORD_DEFAULT), $_POST['full_name'], $plain]);
            $uid = $pdo->lastInsertId();
        } else { 
            $uid = $usr['user_id']; 
            $newUsername = strtolower(trim($_POST['teacher_id'])); // Ensure consistency if user exists
        }

        $pdo->prepare("INSERT INTO teachers (teacher_id,user_id,full_name,email,phone,department_id,course_info,year,section) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$newUsername, $uid, $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['dept_id'], $course_name, $year_name, $_POST['section_number']]);
        
        $pdo->commit();
        $msg = "Teacher Assigned Successfully!";
    } catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); $error="Error: " . $e->getMessage(); }
}

// Fetch data
$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$years_data = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$courses_data = $pdo->query("SELECT course_id, course_name, department_id FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT t.*, u.temp_pass, d.department_name FROM teachers t JOIN users u ON t.user_id = u.user_id JOIN departments d ON t.department_id = d.department_id ORDER BY t.teacher_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
/* Styles remain unchanged */
.admin-container { margin-left:260px; padding:30px; background:#f1f5f9; min-height:100vh; font-family:'Segoe UI', sans-serif; }
.card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom:20px; }
.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px; }
input, select { padding:12px; border:1px solid #cbd5e1; border-radius:8px; width:100%; box-sizing: border-box; font-size: 14px; background:white; }
select:disabled { background: #f8fafc; cursor: not-allowed; opacity: 0.6; }
.btn { padding:10px 18px; border-radius:8px; border:none; cursor:pointer; font-weight:bold; font-size:12px; display:inline-block; text-decoration:none; }
.btn-primary { background:#2563eb; color:#fff; width:100%; height:45px; margin-top:10px; }
.btn-edit { background:#f59e0b; color:#fff; }
.btn-del { background:#ef4444; color:#fff; }
.btn-reset { background:#10b981; color:#fff; }
.btn-print { background:#64748b; color:#fff; }
.btn-eval { background:#8b5cf6; color:#fff; } /* NEW button style */
table { width:100%; border-collapse:collapse; margin-top:15px; }
th { text-align:left; background:#f8fafc; padding:12px; color:#64748b; font-size:11px; text-transform:uppercase; border-bottom:2px solid #e2e8f0; }
td { padding:12px; border-bottom:1px solid #f1f5f9; font-size:13px; vertical-align:top; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
.modal-content { background:#fff; margin:5% auto; padding:30px; width:550px; border-radius:15px; }
.search-wrapper { margin-bottom: 20px; }
#registrySearch { border: 2px solid #e2e8f0; font-weight: 500; }

#printSlip { display: none; }
@media print { 
    body * { visibility: hidden; } 
    #printSlip, #printSlip * { visibility: visible; } 
    #printSlip { display: block !important; position: absolute; top: 0; left: 0; width:100%; text-align:center; padding:50px; } 
}
</style>

<div class="admin-container no-print">
    <h2 style="margin-bottom:25px; font-weight: 800;">Teacher Assignment System</h2>
    
    <?php if($msg): ?><div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px;">✅ <?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px;">❌ <?= $error ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:25px;">
        <div class="card">
            <h3 style="margin-top:0;">1. Manual Assignment</h3>
            <form method="POST">
                <!-- Form fields unchanged -->
                <div class="form-row">
                    <div><label>Teacher Username</label><input name="teacher_id" placeholder="Ex: john" required></div>
                    <div><label>Full Name</label><input name="full_name" placeholder="John Doe" required></div>
                </div>
                <div class="form-row">
                    <div><label>Email</label><input name="email" type="email" placeholder="email@univ.edu" required></div>
                    <div><label>Phone</label><input name="phone" placeholder="09..." required></div>
                </div>
                <div class="form-row">
                    <div><label>Department</label>
                        <select name="dept_id" id="d_main" onchange="filterHierarchy()" required>
                            <option value="">-- Select Dept --</option>
                            <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Course</label>
                        <select name="course_id" id="c_main" required disabled><option value="">-- Select Dept First --</option></select>
                    </div>
                </div>
                <div class="form-row">
                    <div><label>Academic Year</label>
                        <select name="year_id" id="y_main" onchange="fetchSections()" required disabled><option value="">-- Select Dept First --</option></select>
                    </div>
                    <div><label>Section</label>
                        <select name="section_number" id="s_main" required disabled><option value="">-- Select Year First --</option></select>
                    </div>
                </div>
                <button name="add_teacher" class="btn btn-primary">Assign Teacher</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">2. Bulk CSV Upload</h3>
            <form method="POST" enctype="multipart/form-data">
                <p style="font-size:12px; color:#64748b;">Headers: TeacherID, Name, Email, Phone, DeptID, CourseID, YearID, Section.</p>
                <input type="file" name="csv_file" required style="margin:20px 0;">
                <button name="import_csv" class="btn" style="background:#16a34a; color:#fff; width:100%; height:45px;">Process CSV File</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Current Registry</h3>
        <div class="search-wrapper">
            <input type="text" id="registrySearch" onkeyup="searchRegistry()" placeholder="🔍 Search by name, email, or course...">
        </div>
        <table id="teacherTable">
            <thead>
                <tr>
                    <th>Teacher info</th>
                    <th>Contact</th>
                    <th>Dept & Course</th>
                    <th>Year & Sec</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teachers as $t): ?>
                <tr>
                    <td><b><?= htmlspecialchars($t['full_name']) ?></b><br><small>User: <?= $t['teacher_id'] ?></small></td>
                    <td><small>📧 <?= htmlspecialchars($t['email']) ?></small><br><small>📞 <?= htmlspecialchars($t['phone']) ?></small></td>
                    <td><?= htmlspecialchars($t['department_name']) ?><br><small><?= $t['course_info'] ?></small></td>
                    <td><?= htmlspecialchars($t['year']) ?><br><small>Sec: <?= $t['section'] ?></small></td>
                    <td style="text-align:right;">
                        <button onclick="preparePrint(<?= htmlspecialchars(json_encode($t)) ?>)" class="btn btn-print">Print</button>
                        <button onclick="openEdit(<?= htmlspecialchars(json_encode($t)) ?>)" class="btn btn-edit">Edit</button>
                        <a href="?action=reset&tid=<?= $t['teacher_id'] ?>" class="btn btn-reset">Reset</a>
                        <a href="?action=delete&tid=<?= $t['teacher_id'] ?>&course=<?= urlencode($t['course_info']) ?>&sec=<?= $t['section'] ?>" class="btn btn-del" onclick="return confirm('Remove assignment?')">Delete</a>
                        <!-- NEW SEND EVALUATION BUTTON -->
                        <a href="send-evaluation.php?teacher_id=<?= $t['teacher_id'] ?>" class="btn btn-eval">Send Evaluation</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Edit Teacher Record</h3>
        <form method="POST">
            <input type="hidden" name="e_tid" id="e_tid">
            <label>Full Name (First name used for Username)</label><input name="e_name" id="e_name" required style="margin-bottom:10px;">
            <label>Email</label><input name="e_email" id="e_email" type="email" required style="margin-bottom:10px;">
            <label>Phone</label><input name="e_phone" id="e_phone" required style="margin-bottom:10px;">
            <label>Dept</label>
            <select name="e_dept" id="e_dept" required style="margin-bottom:10px;">
                <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
            </select>
            <label>Course Name</label><input name="e_course" id="e_course" required style="margin-bottom:10px;">
            <label>Year</label><input name="e_year" id="e_year" required style="margin-bottom:10px;">
            <label>Section</label><input name="e_sec" id="e_sec" required style="margin-bottom:15px;">
            <div style="display:flex; gap:10px;">
                <button type="submit" name="update_teacher" class="btn btn-primary" style="flex:1;">Update Record</button>
                <button type="button" onclick="closeEdit()" class="btn btn-del" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="printSlip">
    <h1>Teacher Login Credentials</h1><hr>
    <p><b>Full Name:</b> <span id="p_name"></span></p>
    <p><b>Username:</b> <span id="p_id"></span></p>
    <p><b>Temporary Password:</b> <span id="p_pass" style="color:red; font-weight:bold; font-size:24px;"></span></p>
</div>

<script>
const yearsMaster = <?= json_encode($years_data) ?>;
const coursesMaster = <?= json_encode($courses_data) ?>;

function filterHierarchy() {
    const deptId = document.getElementById('d_main').value;
    const yearBox = document.getElementById('y_main');
    const courseBox = document.getElementById('c_main');
    const sectionBox = document.getElementById('s_main');

    yearBox.innerHTML = '<option value="">-- Select Year --</option>';
    courseBox.innerHTML = '<option value="">-- Select Course --</option>';
    sectionBox.innerHTML = '<option value="">-- Select Year First --</option>';
    
    if(deptId) {
        yearsMaster.filter(y => y.department_id == deptId).forEach(y => yearBox.innerHTML += `<option value="${y.year_id}">${y.year_name}</option>`);
        coursesMaster.filter(c => c.department_id == deptId).forEach(c => courseBox.innerHTML += `<option value="${c.course_id}">${c.course_name}</option>`);
        yearBox.disabled = false; courseBox.disabled = false;
    }
}

function fetchSections() {
    const yId = document.getElementById('y_main').value;
    const sBox = document.getElementById('s_main');
    fetch('fetch_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'year_id=' + yId
    }).then(r => r.text()).then(html => { sBox.innerHTML = html; sBox.disabled = false; });
}

function searchRegistry() {
    let filter = document.getElementById('registrySearch').value.toLowerCase();
    let rows = document.querySelectorAll('#teacherTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
}

function openEdit(t) {
    document.getElementById('e_tid').value = t.teacher_id;
    document.getElementById('e_name').value = t.full_name;
    document.getElementById('e_email').value = t.email;
    document.getElementById('e_phone').value = t.phone;
    document.getElementById('e_dept').value = t.department_id;
    document.getElementById('e_course').value = t.course_info;
    document.getElementById('e_year').value = t.year;
    document.getElementById('e_sec').value = t.section;
    document.getElementById('editModal').style.display = 'block';
}

function closeEdit() { 
    document.getElementById('editModal').style.display = 'none'; 
}

function preparePrint(d) {
    document.getElementById('p_id').innerText = d.teacher_id;
    document.getElementById('p_name').innerText = d.full_name;
    document.getElementById('p_pass').innerText = d.temp_pass;
    window.print();
}
</script>

<?php require_once '../includes/footer.php'; ?>
