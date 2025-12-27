<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

checkAccess('admin');

$msg = ""; $error = ""; $reset_data = null;

// --- 1. UTILITIES ---
function generateRandomPassword($length = 8) {
    return substr(str_shuffle("abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789"), 0, $length);
}

function generateUsername($fullName) {
    $parts = explode(' ', trim($fullName));
    $name = strtolower(($parts[0] ?? 'std') . ($parts[1] ?? 'user'));
    return preg_replace('/[^a-z0-9]/', '', $name) . rand(100, 999);
}

$roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'student' LIMIT 1");
$roleStmt->execute();
$student_role_id = $roleStmt->fetchColumn();

// --- 2. DELETE LOGIC (FIXED) ---
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

// --- 3. PASSWORD RESET ---
if (isset($_GET['reset_pw'])) {
    try {
        $new_pass = generateRandomPassword();
        $hashed_pw = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("SELECT u.user_id, u.username, s.full_name, s.student_id_card FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?");
        $stmt->execute([$_GET['reset_pw']]);
        $user = $stmt->fetch();
        if ($user) {
            $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hashed_pw, $user['user_id']]);
            $reset_data = ['sid' => $_GET['reset_pw'], 'u' => $user['username'], 'p' => $new_pass, 'id' => $user['student_id_card'], 'n' => $user['full_name'], 'type' => 'Password Reset'];
            $msg = "Password Reset Successful!";
        }
    } catch (PDOException $e) { $error = "Reset Error: " . $e->getMessage(); }
}

// --- 4. MANUAL REGISTRATION ---
if (isset($_POST['add_manual'])) {
    try {
        $pdo->beginTransaction();
        $user = generateUsername($_POST['full_name']); $pass = generateRandomPassword();
        $stmtU = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
        $stmtU->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $student_role_id]);
        $u_id = $pdo->lastInsertId();
        $stmtS = $pdo->prepare("INSERT INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmtS->execute([$u_id, $_POST['full_name'], strtoupper($_POST['student_id_card']), $_POST['gender'], $_POST['batch'], 1, $_POST['dept'], $_POST['year'], $_POST['sect']]);
        $new_sid = $pdo->lastInsertId();
        $pdo->commit();
        $reset_data = ['sid' => $new_sid, 'u' => $user, 'p' => $pass, 'id' => $_POST['student_id_card'], 'n' => $_POST['full_name'], 'type' => 'New Registration'];
        $msg = "Student Registered Successfully!";
    } catch (Exception $e) { $pdo->rollBack(); $error = "Error: " . $e->getMessage(); }
}

// --- 5. BULK IMPORT ---
if (isset($_POST['import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
    $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
    fgetcsv($handle); 
    $imported = 0; $skipped = 0;
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (count($row) < 7) { $skipped++; continue; }
        try {
            $pdo->beginTransaction();
            $username = generateUsername($row[0]);
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
            $stmtU->execute([$username, password_hash("astu123", PASSWORD_DEFAULT), $student_role_id]);
            $u_id = $pdo->lastInsertId();
            $stmtS = $pdo->prepare("INSERT INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmtS->execute([$u_id, $row[0], $row[1], $row[2], $row[3], 1, $row[4], $row[5], $row[6]]);
            $pdo->commit(); $imported++;
        } catch (Exception $e) { $pdo->rollBack(); $skipped++; }
    }
    fclose($handle); $msg = "Imported $imported students.";
}

// --- 6. SEARCH & FETCH ---
$search = $_GET['search'] ?? '';
$f_dept = $_GET['f_dept'] ?? '';
$sql = "SELECT s.*, d.department_name, u.username FROM students s 
        LEFT JOIN departments d ON s.department_id = d.department_id 
        JOIN users u ON s.user_id = u.user_id WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (s.full_name LIKE ? OR s.student_id_card LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($f_dept) { $sql .= " AND s.department_id = ?"; $params[] = $f_dept; }
$sql .= " ORDER BY s.student_id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$students = $stmt->fetchAll();

$depts = $pdo->query("SELECT * FROM departments")->fetchAll();
$years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
$sects = $pdo->query("SELECT * FROM sections")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    .main-content { margin-left: 260px; padding: 25px; background: #f1f5f9; min-height: 100vh; font-family: sans-serif; }
    .card { background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .btn { padding: 7px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 11px; font-weight: bold; border: none; display: inline-block; }
    .btn-blue { background: #2563eb; color: white; } .btn-green { background: #10b981; color: white; }
    .btn-red { background: #ef4444; color: white; } .btn-gray { background: #64748b; color: white; }
    input, select { padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; }
    .grid-container { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th { background:#f8fafc; text-align: left; font-size:12px; color:#64748b; border-bottom:1px solid #e2e8f0; padding:15px; }
    td { padding:12px 15px; border-bottom: 1px solid #f1f5f9; }
</style>

<main class="main-content">
    <h2 style="margin-top:0;">Student Records</h2>

    <?php if(isset($_GET['msg'])) echo '<div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:15px;">'.htmlspecialchars($_GET['msg']).'</div>'; ?>
    <?php if($msg): ?><div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:15px; border:1px solid #bbf7d0;"><?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; margin-bottom:15px;"><?= $error ?></div><?php endif; ?>

    <?php if($reset_data): ?>
        <div id="printArea" style="background:#fff; border: 2px solid #2563eb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-top:0; color:#2563eb;"><?= $reset_data['type'] ?> Credentials</h3>
            <p>Name: <b><?= htmlspecialchars($reset_data['n']) ?></b> | ID: <b><?= $reset_data['id'] ?></b></p>
            <p>User: <b><?= $reset_data['u'] ?></b> | Password: <span style="background:yellow; padding:2px 10px; font-size:18px; font-weight:bold; color:black;"><?= $reset_data['p'] ?></span></p>
            <button onclick="printDiv('printArea')" class="btn btn-blue" style="padding:10px 20px;">Print This Slip</button>
        </div>
    <?php endif; ?>

    <div class="grid-container">
        <div>
            <div class="card">
                <h4 style="margin-top:0;">Manual Registration</h4>
                <form method="POST">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="text" name="student_id_card" placeholder="Student ID" required style="margin-top:10px;">
                    <select name="gender" style="margin-top:10px;"><option>Male</option><option>Female</option></select>
                    <select name="dept" required style="margin-top:10px;"><option value="">Department</option><?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?></select>
                    <select name="year" required style="margin-top:10px;"><option value="">Year</option><?php foreach($years as $y): ?><option value="<?= $y['year_id'] ?>"><?= $y['year_name'] ?></option><?php endforeach; ?></select>
                    <select name="sect" required style="margin-top:10px;"><option value="">Section</option><?php foreach($sects as $s): ?><option value="<?= $s['section_id'] ?>">Section <?= $s['section_number'] ?></option><?php endforeach; ?></select>
                    <input type="hidden" name="batch" value="2025">
                    <button type="submit" name="add_manual" class="btn btn-blue" style="width:100%; margin-top:15px;">Add Student</button>
                </form>
            </div>
            <div class="card">
                <h4 style="margin-top:0;">Bulk Import</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <button type="submit" name="import_csv" class="btn btn-gray" style="width:100%; margin-top:10px;">Process CSV</button>
                </form>
            </div>
        </div>

        <div>
            <form class="card" style="display:flex; gap:10px;" method="GET">
                <input type="text" name="search" placeholder="Search name or ID..." value="<?= htmlspecialchars($search) ?>">
                <select name="f_dept" style="width:200px;">
                    <option value="">All Depts</option>
                    <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>" <?= $f_dept==$d['department_id']?'selected':'' ?>><?= $d['department_name'] ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-blue">Filter</button>
                <a href="manage-students.php" class="btn btn-gray" style="line-height:22px;">Clear</a>
            </form>

            <div class="card" style="padding:0; overflow:hidden;">
                <table>
                    <tr>
                        <th>ID & NAME</th>
                        <th>DEPARTMENT</th>
                        <th style="text-align:right; padding-right:15px;">ACTIONS</th>
                    </tr>
                    <?php foreach($students as $s): 
                        $current_pw = ($reset_data && $reset_data['sid'] == $s['student_id']) ? $reset_data['p'] : 'astu123';
                    ?>
                    <tr>
                        <td><b><?= $s['student_id_card'] ?></b><br><small><?= htmlspecialchars($s['full_name']) ?></small></td>
                        <td style="font-size:13px;"><?= $s['department_name'] ?></td>
                        <td style="text-align:right; padding-right:15px;">
                            <button onclick="printRow('<?= $s['full_name'] ?>','<?= $s['student_id_card'] ?>','<?= $s['username'] ?>','<?= $current_pw ?>')" class="btn btn-green">Print</button>
                            <a href="manage-students.php?reset_pw=<?= $s['student_id'] ?>" class="btn btn-blue">Reset</a>
                            <a href="edit-student.php?id=<?= $s['student_id'] ?>" class="btn btn-gray">Edit</a>
                            <a href="manage-students.php?delete=<?= $s['student_id'] ?>" class="btn btn-red" onclick="return confirm('Delete permanently?')">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function printDiv(divId) {
    var content = document.getElementById(divId).innerHTML;
    content = content.replace(/<button.*<\/button>/, ""); 
    var win = window.open('', '', 'height=400,width=600');
    win.document.write('<html><body style="font-family:sans-serif; padding:50px; border:2px solid #2563eb; border-radius:10px;">');
    win.document.write('<h2 style="text-align:center;">Student Credentials</h2>' + content + '</body></html>');
    win.document.close(); win.print();
}

function printRow(name, id, user, pass) {
    var win = window.open('', '', 'height=400,width=600');
    win.document.write('<html><body style="font-family:sans-serif; text-align:center; padding:40px; border:2px solid #10b981; border-radius:10px;">');
    win.document.write('<h2>Student Login Slip</h2><hr>');
    win.document.write('<p><b>Name:</b> ' + name + '</p><p><b>ID:</b> ' + id + '</p>');
    win.document.write('<p><b>Username:</b> ' + user + '</p>');
    win.document.write('<p><b>Password:</b> <span style="font-size:18px; font-weight:bold; color:#b91c1c;">' + pass + '</span></p>');
    win.document.write('</body></html>');
    win.document.close(); win.print();
}
</script>

<?php include '../includes/footer.php'; ?>