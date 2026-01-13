<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('admin');

$msg = ""; $error = ""; $reset_data = null;

// --- UTILITIES ---
function generateRandomPassword($length = 8) {
    return substr(str_shuffle("abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789"), 0, $length);
}

function generateUsername($fullName) {
    $parts = explode(' ', trim($fullName));
    $base = strtolower(($parts[0] ?? 'std'));
    return preg_replace('/[^a-z0-9]/', '', $base) . rand(100, 999);
}

/* ===============================
    1. ACTION HANDLERS (CRUD)
================================ */

// --- DELETE ---
if (isset($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
        $stmt->execute([$_GET['delete']]);
        $uid = $stmt->fetchColumn();
        if ($uid) {
            $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$_GET['delete']]);
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
            $pdo->commit();
            header("Location: manage-students.php?msg=" . urlencode("Student deleted successfully."));
            exit();
        }
    } catch (PDOException $e) { $pdo->rollBack(); $error = "Delete Error: " . $e->getMessage(); }
}

// --- PASSWORD RESET ---
if (isset($_GET['reset_pw'])) {
    try {
        $new_pass = generateRandomPassword();
        $stmt = $pdo->prepare("SELECT u.user_id, u.username, s.full_name, s.student_id_card FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?");
        $stmt->execute([$_GET['reset_pw']]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash($new_pass, PASSWORD_DEFAULT), $user['user_id']]);
            $reset_data = ['sid' => $_GET['reset_pw'], 'u' => $user['username'], 'p' => $new_pass, 'id' => $user['student_id_card'], 'n' => $user['full_name']];
            $msg = "Password reset! New password: <b>$new_pass</b>";
        }
    } catch (PDOException $e) { $error = "Reset Error: " . $e->getMessage(); }
}

// --- UPDATE STUDENT (EDIT MODAL LOGIC) ---
if (isset($_POST['update_student'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE students SET full_name=?, student_id_card=?, gender=?, batch=?, department_id=? WHERE student_id=?");
        $stmt->execute([$_POST['e_name'], $_POST['e_id_card'], $_POST['e_gender'], $_POST['e_batch'], $_POST['e_dept'], $_POST['e_sid']]);
        
        $stmt2 = $pdo->prepare("UPDATE users SET full_name=? WHERE user_id=(SELECT user_id FROM students WHERE student_id=?)");
        $stmt2->execute([$_POST['e_name'], $_POST['e_sid']]);
        
        $pdo->commit(); 
        $msg = "Student record updated successfully!";
    } catch (Exception $e) { if($pdo->inTransaction()) $pdo->rollBack(); $error = "Update failed: " . $e->getMessage(); }
}

/* ===============================
    2. REGISTRATION LOGIC
================================ */

// --- MANUAL ADD ---
if (isset($_POST['add_manual'])) {
    try {
        $pdo->beginTransaction();
        $roleStmt = $pdo->query("SELECT role_id FROM roles WHERE role_name = 'student' LIMIT 1");
        $student_role_id = $roleStmt->fetchColumn();

        $auto_user = generateUsername($_POST['full_name']);
        $new_pass = generateRandomPassword();
        
        $stmtU = $pdo->prepare("INSERT INTO users (username, password, full_name, role_id) VALUES (?, ?, ?, ?)");
        $stmtU->execute([$auto_user, password_hash($new_pass, PASSWORD_DEFAULT), $_POST['full_name'], $student_role_id]);
        $u_id = $pdo->lastInsertId();
        
        $stmtS = $pdo->prepare("INSERT INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmtS->execute([$u_id, $_POST['full_name'], strtoupper($_POST['student_id_card']), $_POST['gender'], $_POST['batch'], 1, $_POST['dept_id'], $_POST['year_id'], $_POST['section_id']]);
        $new_sid = $pdo->lastInsertId();
        
        $pdo->commit();
        $reset_data = ['sid' => $new_sid, 'u' => $auto_user, 'p' => $new_pass, 'id' => $_POST['student_id_card'], 'n' => $_POST['full_name']];
        $msg = "Student Added! User: <b>$auto_user</b>";
    } catch (Exception $e) { if($pdo->inTransaction()) $pdo->rollBack(); $error = "Error: " . $e->getMessage(); }
}

// --- BULK IMPORT ---
if (isset($_POST['import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
    $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
    fgetcsv($handle); // skip header
    $roleStmt = $pdo->query("SELECT role_id FROM roles WHERE role_name = 'student' LIMIT 1");
    $student_role_id = $roleStmt->fetchColumn();
    $count = 0;
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        try {
            $pdo->beginTransaction();
            $auto_user = generateUsername($row[0]);
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, full_name, role_id) VALUES (?, ?, ?, ?)");
            $stmtU->execute([$auto_user, password_hash("astu123", PASSWORD_DEFAULT), $row[0], $student_role_id]);
            $u_id = $pdo->lastInsertId();
            $stmtS = $pdo->prepare("INSERT INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmtS->execute([$u_id, $row[0], $row[1], $row[2], $row[3], 1, $row[4], $row[5], $row[6]]);
            $pdo->commit(); $count++;
        } catch (Exception $e) { $pdo->rollBack(); }
    }
    fclose($handle); $msg = "Imported $count students. (Default password: astu123)";
}

// --- FETCH DATA ---
$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$years_data = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$search = $_GET['search'] ?? '';
$sql = "SELECT s.*, d.department_name, u.username, y.year_name, sec.section_number 
        FROM students s 
        LEFT JOIN departments d ON s.department_id = d.department_id 
        LEFT JOIN academic_years y ON s.year_id = y.year_id
        LEFT JOIN sections sec ON s.section_id = sec.section_id
        JOIN users u ON s.user_id = u.user_id";
if ($search) { $sql .= " WHERE s.full_name LIKE ? OR s.student_id_card LIKE ?"; }
$sql .= " ORDER BY s.student_id DESC";

$stmt = $pdo->prepare($sql);
if ($search) { $stmt->execute(["%$search%", "%$search%"]); } else { $stmt->execute(); }
$students = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    .main-content { margin-left: 260px; padding: 25px; background: #f8fafc; min-height: 100vh; font-family: sans-serif; }
    .card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 12px; }
    label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase; }
    input, select { padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; font-size: 14px; }
    .btn { padding: 7px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 11px; font-weight: bold; border: none; color: white; transition: 0.2s; display: inline-block; }
    .btn-blue { background: #2563eb; }
    .btn-green { background: #10b981; }
    .btn-orange { background: #f59e0b; }
    .btn-red { background: #ef4444; }
    .btn-gray { background: #64748b; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background:#f1f5f9; text-align: left; font-size:11px; color:#475569; border-bottom:2px solid #e2e8f0; padding:15px; }
    td { padding:12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
    .modal-content { background:#fff; margin:5% auto; padding:30px; width:500px; border-radius:15px; position:relative; }
</style>

<main class="main-content">
    <h2 style="color:#1e293b; font-weight:800;">Student Management</h2>

    <?php if($msg): ?><div class="card" style="background:#dcfce7; color:#166534; border:none;">✅ <?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div class="card" style="background:#fee2e2; color:#991b1b; border:none;">❌ <?= $error ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns: 320px 1fr; gap: 20px;">
        <div>
            <div class="card">
                <h3 style="margin-top:0;">Registration</h3>
                <form method="POST">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                    <div class="form-group"><label>ID Card</label><input type="text" name="student_id_card" required></div>
                    <div class="form-group"><label>Department</label>
                        <select name="dept_id" id="d_main" onchange="filterHierarchy()" required>
                            <option value="">-- Select --</option>
                            <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Year</label>
                        <select name="year_id" id="y_main" onchange="fetchSections()" required disabled><option value="">-- Select Dept --</option></select>
                    </div>
                    <div class="form-group"><label>Section</label>
                        <select name="section_id" id="s_main" required disabled><option value="">-- Select Year --</option></select>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <div style="flex:1;"><label>Gender</label><select name="gender"><option>Male</option><option>Female</option></select></div>
                        <div style="flex:1;"><label>Batch</label><input type="text" name="batch" value="2025"></div>
                    </div>
                    <button type="submit" name="add_manual" class="btn btn-blue" style="width:100%; margin-top:10px; height:40px;">Add Student</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Bulk Import</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <button type="submit" name="import_csv" class="btn btn-gray" style="width:100%; margin-top:10px;">Upload CSV</button>
                </form>
            </div>
        </div>

        <div>
            <form class="card" style="display:flex; gap:10px; padding:12px;" method="GET">
                <input type="text" name="search" placeholder="Search by name or ID..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
                <button type="submit" class="btn btn-blue" style="width:100px;">Search</button>
            </form>

            <div class="card" style="padding:0; overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Details</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): 
                            $print_p = ($reset_data && $reset_data['sid'] == $s['student_id']) ? $reset_data['p'] : 'astu123';
                        ?>
                        <tr>
                            <td><b style="color:#2563eb;"><?= $s['student_id_card'] ?></b></td>
                            <td><?= htmlspecialchars($s['full_name']) ?></td>
                            <td><small><?= $s['department_name'] ?><br><?= $s['year_name'] ?> - Sec <?= $s['section_number'] ?></small></td>
                            <td style="text-align:right; white-space:nowrap;">
                                <button onclick="printSlip('<?= addslashes($s['full_name']) ?>','<?= $s['student_id_card'] ?>','<?= $s['username'] ?>', '<?= $print_p ?>')" class="btn btn-green">Slip</button>
                                <button onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-orange">Edit</button>
                                <a href="manage-students.php?reset_pw=<?= $s['student_id'] ?>" class="btn btn-blue" onclick="return confirm('Reset password?')">Reset</a>
                                <a href="manage-students.php?delete=<?= $s['student_id'] ?>" class="btn btn-red" onclick="return confirm('Delete permanently?')">Del</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Edit Student Record</h3>
        <form method="POST">
            <input type="hidden" name="e_sid" id="e_sid">
            <div class="form-group"><label>Full Name</label><input name="e_name" id="e_name" required></div>
            <div class="form-group"><label>ID Card</label><input name="e_id_card" id="e_id_card" required></div>
            <div class="form-group"><label>Batch</label><input name="e_batch" id="e_batch" required></div>
            <div class="form-group"><label>Gender</label>
                <select name="e_gender" id="e_gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group"><label>Department</label>
                <select name="e_dept" id="e_dept">
                    <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="update_student" class="btn btn-blue" style="flex:1; height:40px;">Update</button>
                <button type="button" onclick="closeEdit()" class="btn btn-red" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const yearsMaster = <?= json_encode($years_data) ?>;

function filterHierarchy() {
    const dId = document.getElementById('d_main').value;
    const yBox = document.getElementById('y_main');
    yBox.innerHTML = '<option value="">-- Select --</option>';
    if(dId) {
        yearsMaster.filter(y => y.department_id == dId).forEach(y => {
            yBox.innerHTML += `<option value="${y.year_id}">${y.year_name}</option>`;
        });
        yBox.disabled = false;
    }
}

async function fetchSections() {
    const dId = document.getElementById('d_main').value;
    const yId = document.getElementById('y_main').value;
    const sBox = document.getElementById('s_main');
    if(yId) {
        const res = await fetch(`fetch-sections.php?year_id=${yId}&dept_id=${dId}`);
        const data = await res.json();
        sBox.innerHTML = '<option value="">-- Select --</option>';
        data.forEach(s => { sBox.innerHTML += `<option value="${s.section_id}">Section ${s.section_number}</option>`; });
        sBox.disabled = false;
    }
}

function openEdit(s) {
    document.getElementById('e_sid').value = s.student_id;
    document.getElementById('e_name').value = s.full_name;
    document.getElementById('e_id_card').value = s.student_id_card;
    document.getElementById('e_batch').value = s.batch;
    document.getElementById('e_gender').value = s.gender;
    document.getElementById('e_dept').value = s.department_id;
    document.getElementById('editModal').style.display = 'block';
}

function closeEdit() { document.getElementById('editModal').style.display = 'none'; }

function printSlip(name, id, user, pass) {
    var win = window.open('', '', 'height=450,width=600');
    win.document.write('<html><body style="font-family:sans-serif; text-align:center; padding:40px; border:10px solid #2563eb;">');
    win.document.write('<h1 style="color:#2563eb;">ASTU STUDENT LOGIN</h1><hr>');
    win.document.write('<p style="font-size:18px;"><b>Name:</b> '+name+'</p>');
    win.document.write('<p style="font-size:18px;"><b>Student ID:</b> '+id+'</p>');
    win.document.write('<div style="background:#f1f5f9; padding:20px; border-radius:10px; margin-top:20px; border: 1px dashed #cbd5e1;">');
    win.document.write('<p><b>Username:</b> '+user+'</p>');
    win.document.write('<p><b>Password:</b> <br><span style="font-size:24px; color:#ef4444; font-weight:bold;">'+pass+'</span></p>');
    win.document.write('</div><p style="color:#64748b; font-size:12px;">Please change password after first login.</p></body></html>');
    win.document.close(); 
    win.print();
}
</script>

<?php include '../includes/footer.php'; ?>