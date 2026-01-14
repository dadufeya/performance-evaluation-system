<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

checkAccess('admin');

$msg = $_GET['msg'] ?? ""; $error = "";
$TEACHER_ROLE_ID = 2; // Verify this matches your 'roles' table

// --- 1. ACTIONS: DELETE, RESET, UPDATE ---
if (isset($_GET['reset_uid'])) {
    $uid = $_GET['reset_uid'];
    $new_plain = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8);
    $new_hash = password_hash($new_plain, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password = ?, temp_pass = ? WHERE user_id = ?");
    $upd->execute([$new_hash, $new_plain, $uid]);
    header("Location: manage-sections.php?msg=Reset Successful! New Pass: " . $new_plain);
    exit;
}

if (isset($_GET['delete_tid']) && isset($_GET['course_id']) && isset($_GET['section_id'])) {
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ? AND course_id = ? AND section_id = ?");
    $stmt->execute([$_GET['delete_tid'], $_GET['course_id'], $_GET['section_id']]);
    header("Location: manage-sections.php?msg=Assignment Removed");
    exit;
}

if (isset($_POST['update_teacher'])) {
    try {
        $pdo->beginTransaction();
        $updU = $pdo->prepare("UPDATE users SET full_name = ?, username = ? WHERE user_id = ?");
        $updU->execute([$_POST['edit_name'], $_POST['edit_tid'], $_POST['edit_uid']]);
        
        $updT = $pdo->prepare("UPDATE teachers SET teacher_id = ?, full_name = ?, email = ?, phone = ?, year_id = ?, section_id = ?, course_id = ? 
                               WHERE teacher_id = ? AND course_id = ? AND section_id = ?");
        $updT->execute([
            $_POST['edit_tid'], $_POST['edit_name'], $_POST['edit_email'], $_POST['edit_phone'], $_POST['edit_year_id'], $_POST['edit_section_id'], $_POST['edit_course_id'],
            $_POST['old_tid'], $_POST['old_course_id'], $_POST['old_section_id']
        ]);
        $pdo->commit();
        $msg = "Teacher updated successfully.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Update failed: " . $e->getMessage();
    }
}

// --- 2. REGISTRATION LOGIC ---
if (isset($_POST['add_teacher'])) {
    try {
        $tid = trim($_POST['tid']);
        $fullname = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dept_id = $_POST['dept_id'];
        $course_id = $_POST['course_id'];
        $year_id = $_POST['year_id'];
        $section_id = $_POST['section_id'];

        $pdo->beginTransaction();
        $stmtU = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmtU->execute([$tid]);
        $existingUser = $stmtU->fetch();

        if (!$existingUser) {
            $plain = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8);
            $hash = password_hash($plain, PASSWORD_DEFAULT);
            $insU = $pdo->prepare("INSERT INTO users (username, password, full_name, role_id, status, temp_pass) VALUES (?, ?, ?, ?, 'active', ?)");
            $insU->execute([$tid, $hash, $fullname, $TEACHER_ROLE_ID, $plain]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $existingUser['user_id'];
        }

        $stmtT = $pdo->prepare("INSERT INTO teachers (teacher_id, user_id, full_name, email, phone, department_id, course_id, year_id, section_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtT->execute([$tid, $user_id, $fullname, $email, $phone, $dept_id, $course_id, $year_id, $section_id]);
        
        $pdo->commit();
        $msg = "Teacher assigned successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// --- 3. DATA FETCHING ---
$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$all_courses = $pdo->query("SELECT course_id, course_name, department_id FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$years_data = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$teachers = $pdo->query("
    SELECT t.*, d.department_name, u.temp_pass, u.user_id as uid, c.course_name, y.year_name, sec.section_number 
    FROM teachers t 
    JOIN users u ON t.user_id = u.user_id 
    LEFT JOIN departments d ON t.department_id = d.department_id 
    LEFT JOIN courses c ON t.course_id = c.course_id
    LEFT JOIN academic_years y ON t.year_id = y.year_id
    LEFT JOIN sections sec ON t.section_id = sec.section_id
    ORDER BY t.teacher_id DESC
")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    .admin-container { margin-left: 260px; padding: 30px; background: #f0f4f8; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    .card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #e2e8f0; }
    .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .input-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 5px; box-sizing: border-box; }
    .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
    .data-table th { background: #f8fafc; padding: 15px; text-align: left; color: #64748b; font-size: 13px; border-bottom: 2px solid #edf2f7; }
    .data-table td { padding: 15px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
    .sec-badge { background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 4px; font-weight: bold; border: 1px solid #fecaca; }
    .btn-save { background: #2563eb; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: 600; grid-column: span 4; transition: 0.3s; }
    .btn-save:hover { background: #1d4ed8; }
    #editModal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
</style>

<div class="admin-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="color:#1e293b;">Teacher & Section Management</h2>
        <?php if($msg): ?><div style="background:#dcfce7; color:#166534; padding:10px 20px; border-radius:8px;">✅ <?= $msg ?></div><?php endif; ?>
        <?php if($error): ?><div style="background:#fee2e2; color:#991b1b; padding:10px 20px; border-radius:8px;">❌ <?= $error ?></div><?php endif; ?>
    </div>

    <div class="card">
        <h3 style="margin-top:0; font-size:16px; color:#475569;">Register & Assign Teacher</h3>
        <form method="POST" class="form-grid">
            <div>
                <label>Teacher ID</label>
                <input type="text" name="tid" placeholder="ASTU/0001/T" class="input-control" required>
            </div>
            <div>
                <label>Full Name</label>
                <input type="text" name="name" class="input-control" required>
            </div>
            <div>
                <label>Email Address</label>
                <input type="email" name="email" class="input-control" required>
            </div>
            <div>
                <label>Phone Number</label>
                <input type="text" name="phone" class="input-control" required>
            </div>
            <div>
                <label>Choose Department</label>
                <select name="dept_id" id="dept_select" class="input-control" onchange="filterCourses()" required>
                    <option value="">-- Select Dept --</option>
                    <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Choose Course</label>
                <select name="course_id" id="course_select" class="input-control" disabled required>
                    <option value="">-- Select Course --</option>
                </select>
            </div>
            <div>
                <label>Academic Year</label>
                <select name="year_id" id="year_select" class="input-control" onchange="fetchSections()" required disabled>
                    <option value="">-- Select Dept First --</option>
                </select>
            </div>
            <div>
                <label>Section</label>
                <select name="section_id" id="section_select" class="input-control" required disabled>
                    <option value="">-- Select Year First --</option>
                </select>
            </div>
            <button type="submit" name="add_teacher" class="btn-save">Save Teacher Assignment</button>
        </form>
    </div>

    <div class="card" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Teacher ID</th>
                    <th>Name & Department</th>
                    <th>Assigned Course</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teachers as $t): ?>
                <tr>
                    <td><b style="color:#2563eb;"><?= $t['teacher_id'] ?></b></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($t['full_name']) ?></div>
                        <small style="color:#64748b;"><?= htmlspecialchars($t['department_name']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($t['course_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($t['year_name'] ?? 'N/A') ?></td>
                    <td><span class="sec-badge"><?= htmlspecialchars($t['section_number'] ?? 'N/A') ?></span></td>
                    <td>
                        <button onclick="printSlip('<?= addslashes($t['full_name']) ?>','<?= $t['teacher_id'] ?>','<?= $t['temp_pass'] ?>')" style="color:#2563eb; background:none; border:none; cursor:pointer;">Print</button> |
                        <button onclick="openEdit(<?= htmlspecialchars(json_encode($t)) ?>)" style="color:#10b981; background:none; border:none; cursor:pointer;">Edit</button> |
                         <a href="?reset_uid=<?= $t['uid'] ?>" style="color:#f59e0b; text-decoration:none;">Reset</a> |
                        <a href="?delete_tid=<?= $t['teacher_id'] ?>&course_id=<?= $t['course_id'] ?>&section_id=<?= $t['section_id'] ?>" style="color:#ef4444; text-decoration:none;" onclick="return confirm('Delete?')">Del</a> |
                        <a href="send-evaluation.php?teacher_id=<?= $t['teacher_id'] ?>&course_id=<?= $t['course_id'] ?>&section_id=<?= $t['section_id'] ?>" style="color:#8b5cf6; text-decoration:none; font-weight:bold;">Send Eval</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editModal">
    <div style="background:#fff; width:450px; margin:50px auto; padding:30px; border-radius:15px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0;">Update Assignment</h3>
        <form method="POST">
            <input type="hidden" name="edit_uid" id="edit_uid">
            <input type="hidden" name="old_tid" id="old_tid">
            <input type="hidden" name="old_course_id" id="old_course_id">
            <input type="hidden" name="old_section_id" id="old_section_id">
            
            <label>Full Name</label><input type="text" name="edit_name" id="edit_name" class="input-control" required>
            <label>Teacher ID</label><input type="text" name="edit_tid" id="edit_tid" class="input-control" required>
            <label>Email</label><input type="email" name="edit_email" id="edit_email" class="input-control" required>
            <label>Phone</label><input type="text" name="edit_phone" id="edit_phone" class="input-control" required>
            
            <label>Course</label>
            <select name="edit_course_id" id="edit_course_id" class="input-control" required></select>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div><label>Year</label><select name="edit_year_id" id="edit_year_id" class="input-control" onchange="fetchEditSections()" required></select></div>
                <div><label>Section</label><select name="edit_section_id" id="edit_section_id" class="input-control" required></select></div>
            </div>
            
            <button type="submit" name="update_teacher" class="btn-save" style="margin-top:20px;">Update Details</button>
            <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="width:100%; background:none; border:none; margin-top:10px; color:gray; cursor:pointer;">Cancel</button>
        </form>
    </div>
</div>

<script>
const courseData = <?= json_encode($all_courses); ?>;
const yearsMaster = <?= json_encode($years_data); ?>;

function filterCourses() {
    const dId = document.getElementById('dept_select').value;
    const cSel = document.getElementById('course_select');
    const ySel = document.getElementById('year_select');

    cSel.innerHTML = '<option value="">-- Select Course --</option>';
    ySel.innerHTML = '<option value="">-- Select Year --</option>';
    
    if(dId) {
        courseData.filter(c => c.department_id == dId).forEach(c => {
            let o = document.createElement('option'); o.value = c.course_id; o.textContent = c.course_name;
            cSel.appendChild(o);
        });
        yearsMaster.filter(y => y.department_id == dId).forEach(y => {
            let o = document.createElement('option'); o.value = y.year_id; o.textContent = y.year_name;
            ySel.appendChild(o);
        });
        cSel.disabled = false;
        ySel.disabled = false;
    }
}

function fetchSections() {
    const yId = document.getElementById('year_select').value;
    const sBox = document.getElementById('section_select');
    if(!yId) return;

    fetch('fetch_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'year_id=' + yId
    }).then(r => r.text()).then(html => { 
        sBox.innerHTML = html;
        sBox.disabled = false;
    });
}

function printSlip(name, id, pass) {
    const win = window.open('', '', 'height=400,width=600');
    win.document.write(`<html><body style="font-family:sans-serif; text-align:center; padding:50px; border:5px solid #2563eb;">`);
    win.document.write(`<h1>Login Credentials</h1><hr>`);
    win.document.write(`<h3>Teacher: ${name}</h3>`);
    win.document.write(`<p><b>Username:</b> ${id}</p>`);
    win.document.write(`<p><b>Temporary Password:</b> ${pass}</p>`);
    win.document.write(`<small>Please change your password after logging in.</small>`);
    win.document.write(`</body></html>`);
    win.print(); win.close();
}

function openEdit(t) {
    document.getElementById('edit_uid').value = t.uid;
    document.getElementById('old_tid').value = t.teacher_id;
    document.getElementById('old_course_id').value = t.course_id;
    document.getElementById('old_section_id').value = t.section_id;
    
    document.getElementById('edit_tid').value = t.teacher_id;
    document.getElementById('edit_name').value = t.full_name;
    document.getElementById('edit_email').value = t.email;
    document.getElementById('edit_phone').value = t.phone;
    
    // Setup Edit modal hierarchy
    const deptId = t.department_id;
    const cSel = document.getElementById('edit_course_id');
    const ySel = document.getElementById('edit_year_id');
    
    cSel.innerHTML = '<option value="">-- Select Course --</option>';
    ySel.innerHTML = '<option value="">-- Select Year --</option>';
    
    courseData.filter(c => c.department_id == deptId).forEach(c => {
        let o = document.createElement('option'); o.value = c.course_id; o.textContent = c.course_name;
        if(c.course_id == t.course_id) o.selected = true;
        cSel.appendChild(o);
    });
    
    yearsMaster.filter(y => y.department_id == deptId).forEach(y => {
        let o = document.createElement('option'); o.value = y.year_id; o.textContent = y.year_name;
        if(y.year_id == t.year_id) o.selected = true;
        ySel.appendChild(o);
    });

    fetchEditSections(t.year_id, t.section_id);
    document.getElementById('editModal').style.display = 'block';
}

function fetchEditSections(yId = null, selectedSecId = null) {
    const yearId = yId || document.getElementById('edit_year_id').value;
    const sBox = document.getElementById('edit_section_id');

    fetch('fetch_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'year_id=' + yearId
    }).then(r => r.text()).then(html => { 
        sBox.innerHTML = html;
        if(selectedSecId) sBox.value = selectedSecId;
    });
}
</script>