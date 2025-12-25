<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

checkAccess('admin');

$msg = "";
$error = "";
$reset_data = null; 

// --- 1. UTILITIES ---
function generateRandomPassword($length = 8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$";
    return substr(str_shuffle($chars), 0, $length);
}

// Automatically generates username from name
function generateUsername($fullName) {
    $parts = explode(' ', trim($fullName));
    $firstName = strtolower($parts[0] ?? '');
    $lastName = strtolower($parts[count($parts) - 1] ?? '');
    $userPart = substr($firstName, 0, 2) . substr($lastName, 0, 6);
    return preg_replace('/[^a-z0-9]/', '', $userPart);
}

// --- 2. GET STUDENT ROLE ID ---
$roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'student' LIMIT 1");
$roleStmt->execute();
$student_role_id = $roleStmt->fetchColumn();

// --- 3. RESET PASSWORD LOGIC ---
if (isset($_GET['reset_pw'])) {
    try {
        $new_plain_pw = generateRandomPassword();
        $hashed_pw = password_hash($new_plain_pw, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT u.user_id, u.username, s.full_name, s.student_id_card FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?");
        $stmt->execute([$_GET['reset_pw']]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $update->execute([$hashed_pw, $user['user_id']]);
            
            $reset_data = [
                'username' => $user['username'],
                'student_id_card' => $user['student_id_card'],
                'name' => $user['full_name'],
                'password' => $new_plain_pw
            ];
            $msg = "Password Reset Successful!";
        }
    } catch (PDOException $e) { $error = "Reset Error: " . $e->getMessage(); }
}

// --- 4. DELETE LOGIC ---
if (isset($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
        $stmt->execute([$_GET['delete']]);
        $uid = $stmt->fetchColumn();
        if ($uid) {
            $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$_GET['delete']]);
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
        }
        $pdo->commit();
        header("Location: manage-students.php?msg=" . urlencode("Student deleted."));
        exit();
    } catch (PDOException $e) { $pdo->rollBack(); $error = "Delete Error: " . $e->getMessage(); }
}

// --- 5. REGISTRATION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $student_role_id) {
    
    // --- SINGLE ENTRY ---
    if (isset($_POST['add'])) {
        try {
            $idCard = trim($_POST['student_id_card']);
            
            // Check if ID already exists
            $checkID = $pdo->prepare("SELECT student_id FROM students WHERE student_id_card = ?");
            $checkID->execute([$idCard]);
            
            if ($checkID->fetch()) {
                $error = "Registration Failed: Student ID <b>$idCard</b> already exists in the system.";
            } else {
                $pdo->beginTransaction();
                $fullName = trim($_POST['full_name']);
                $generatedUser = generateUsername($fullName); 

                // Uniqueness check for system username
                $checkU = $pdo->prepare("SELECT count(*) FROM users WHERE username = ?");
                $checkU->execute([$generatedUser]);
                if ($checkU->fetchColumn() > 0) { $generatedUser .= rand(10, 99); }

                $auto_password = generateRandomPassword();
                $stmtU = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
                $stmtU->execute([$generatedUser, password_hash($auto_password, PASSWORD_DEFAULT), $student_role_id]);
                $user_id = $pdo->lastInsertId();

                $stmtS = $pdo->prepare("INSERT INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmtS->execute([$user_id, $fullName, $idCard, $_POST['gender'], $_POST['batch'], 1, $_POST['department_id'], $_POST['year_id'], $_POST['section_id']]);
                
                $pdo->commit();
                $reset_data = ['username' => $generatedUser, 'student_id_card' => $idCard, 'name' => $fullName, 'password' => $auto_password];
                $msg = "Student Registered Successfully!";
            }
        } catch (Exception $e) { $pdo->rollBack(); $error = "Error: " . $e->getMessage(); }
    }

    // --- BULK IMPORT (WITH JUMP/SKIP LOGIC) ---
    if (isset($_POST['import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
        $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
        fgetcsv($handle); 
        $imported = 0; $skipped = 0;
        $default_pw = "astu123";
        $hashed_pw = password_hash($default_pw, PASSWORD_DEFAULT);

        try {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) < 7) { $skipped++; continue; }

                $fullName = trim($row[0]);
                $idCard   = trim($row[1]);

                // JUMP LOGIC: Check if Student ID Card already exists
                $existCheck = $pdo->prepare("SELECT student_id FROM students WHERE student_id_card = ?");
                $existCheck->execute([$idCard]);
                if ($existCheck->fetch()) {
                    $skipped++; // ID exists, jump this student
                    continue; 
                }

                // Process New Student
                $pdo->beginTransaction();
                try {
                    $username = generateUsername($fullName);
                    $checkU = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                    $checkU->execute([$username]);
                    if ($checkU->fetch()) { $username .= rand(10, 99); }

                    $stmtU = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
                    $stmtU->execute([$username, $hashed_pw, $student_role_id]);
                    $u_id = $pdo->lastInsertId();

                    $stmtS = $pdo->prepare("INSERT INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmtS->execute([$u_id, $fullName, $idCard, trim($row[2]), trim($row[3]), 1, trim($row[4]), trim($row[5]), trim($row[6])]);
                    
                    $pdo->commit();
                    $imported++;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $skipped++;
                }
            }
            $msg = "Import Complete: <b>$imported</b> added, <b>$skipped</b> skipped (already exist or invalid).";
        } catch (Exception $e) { $error = "Import Error: " . $e->getMessage(); }
        fclose($handle);
    }
}

// FETCH DATA
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
$sections = $pdo->query("SELECT * FROM sections")->fetchAll();
$students = $pdo->query("SELECT s.*, d.department_name, u.username FROM students s 
                         LEFT JOIN departments d ON s.department_id = d.department_id 
                         JOIN users u ON s.user_id = u.user_id ORDER BY s.student_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    .main-content { margin-left: 260px; padding: 20px; background: #f8fafc; min-height: 100vh; }
    .action-group { display: flex; gap: 5px; justify-content: flex-end; }
    .btn-action { padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; border: 1px solid transparent; cursor: pointer;}
    .btn-print { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .btn-reset { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .btn-edit { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
    .btn-delete { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .form-control, .form-select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;}
</style>

<main class="main-content">
    <h1 class="page-title">Student Management</h1>

    <?php if($msg): ?><div style="padding:15px; background:#dcfce7; color:#15803d; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0;"><?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div style="padding:15px; background:#fee2e2; color:#b91c1c; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca;"><?= $error ?></div><?php endif; ?>

    <?php if($reset_data): ?>
        <div id="printableArea" style="padding:15px; background:#fff; border:2px solid #2563eb; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="color:#2563eb;">CREDENTIALS GENERATED:</strong><br>
                Name: <?= htmlspecialchars($reset_data['name']) ?> | 
                ID Card: <b><?= $reset_data['student_id_card'] ?></b> | 
                Username: <b><?= $reset_data['username'] ?></b> | 
                PW: <b style="color:#dc2626;"><?= $reset_data['password'] ?></b>
            </div>
            <button onclick="printDiv('printableArea')" style="background:#2563eb; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Print Now</button>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 20px;">
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; height:fit-content;">
            <div style="display:flex; background:#f8fafc;">
                <button id="btn-m" onclick="showTab('m')" style="flex:1; padding:12px; border:none; cursor:pointer; font-weight:bold; border-bottom:2px solid #2563eb; background:#fff;">Single</button>
                <button id="btn-b" onclick="showTab('b')" style="flex:1; padding:12px; border:none; cursor:pointer; font-weight:bold; color:#64748b;">Bulk Import</button>
            </div>
            
            <div id="tab-m" style="padding:15px;">
                <form method="POST">
                    <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                    <input type="text" name="student_id_card" class="form-control" placeholder="Student ID Card" required>
                    <select name="gender" class="form-select"><option>Male</option><option>Female</option></select>
                    <input type="number" name="batch" class="form-control" value="<?= date('Y') ?>">
                    <select name="department_id" class="form-select" required><option value="">Department</option><?php foreach($departments as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?></select>
                    <select name="year_id" class="form-select" required><option value="">Year</option><?php foreach($years as $y): ?><option value="<?= $y['year_id'] ?>"><?= $y['year_name'] ?></option><?php endforeach; ?></select>
                    <select name="section_id" class="form-select" required><option value="">Section</option><?php foreach($sections as $s): ?><option value="<?= $s['section_id'] ?>">Section <?= $s['section_number'] ?></option><?php endforeach; ?></select>
                    <button type="submit" name="add" style="width:100%; padding:10px; background:#2563eb; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-top:10px;">Register Student</button>
                </form>
            </div>

            <div id="tab-b" style="padding:15px; display:none;">
                <div style="background:#fffbeb; padding:10px; border-radius:5px; font-size:11px; margin-bottom:10px; color:#92400e;">
                    <b>CSV Format (7 columns):</b><br>Name, ID_Card, Gender, Batch, DeptID, YearID, SectID
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required style="margin-bottom:15px;">
                    <button type="submit" name="import_csv" style="width:100%; padding:10px; background:#1e293b; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">Upload CSV</button>
                </form>
            </div>
        </div>

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:15px;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:12px;">
                        <th style="padding:10px;">STUDENT INFO</th>
                        <th>DEPARTMENT</th>
                        <th style="text-align:right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;">
                            <small style="color:#64748b;"><?= $s['student_id_card'] ?></small><br>
                            <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
                            <small style="color:#2563eb;">Username: <?= $s['username'] ?></small>
                        </td>
                        <td style="font-size:12px;"><?= $s['department_name'] ?></td>
                        <td class="action-group">
                            <button onclick="printStudent('<?= addslashes($s['full_name']) ?>', '<?= $s['student_id_card'] ?>', '<?= $s['username'] ?>')" class="btn-action btn-print">Print</button>
                            <a href="manage-students.php?reset_pw=<?= $s['student_id'] ?>" class="btn-action btn-reset" onclick="return confirm('Reset password?')">Reset</a>
                            <a href="edit-student.php?id=<?= $s['student_id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="manage-students.php?delete=<?= $s['student_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete?')">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function printStudent(name, id, username) {
    var win = window.open('', '', 'height=400,width=600');
    win.document.write('<html><body style="font-family:sans-serif; text-align:center; padding:50px;">');
    win.document.write('<div style="border:2px solid #000; padding:20px;">');
    win.document.write('<h2>Student Login Details</h2>');
    win.document.write('<p>Name: <strong>' + name + '</strong></p>');
    win.document.write('<p>Student ID: <strong>' + id + '</strong></p>');
    win.document.write('<p>Username: <strong>' + username + '</strong></p>');
    win.document.write('<p>Initial Password: <strong>astu123</strong></p>');
    win.document.write('</div></body></html>');
    win.document.close();
    win.print();
}

function printDiv(divId) {
    var content = document.getElementById(divId).innerHTML;
    var win = window.open('', '', 'height=400,width=600');
    win.document.write('<html><body style="font-family:sans-serif; padding:40px;">' + content + '</body></html>');
    win.document.close();
    win.print();
}

function showTab(type) {
    document.getElementById('tab-m').style.display = type === 'm' ? 'block' : 'none';
    document.getElementById('tab-b').style.display = type === 'b' ? 'block' : 'none';
    document.getElementById('btn-m').style.borderBottom = type === 'm' ? '2px solid #2563eb' : 'none';
    document.getElementById('btn-b').style.borderBottom = type === 'b' ? '2px solid #2563eb' : 'none';
}
</script>

<?php include '../includes/footer.php'; ?>