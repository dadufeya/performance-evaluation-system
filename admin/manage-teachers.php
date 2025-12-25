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

function generateTeacherUsername($fullName) {
    $parts = explode(' ', str_replace(['Dr.', 'Mr.', 'Mrs.', 'Ms.', 'Prof.'], '', trim($fullName)));
    $firstName = strtolower(trim($parts[0] ?? ''));
    $lastName = strtolower(trim($parts[count($parts) - 1] ?? ''));
    $userPart = substr($firstName, 0, 1) . $lastName;
    return preg_replace('/[^a-z0-9]/', '', $userPart);
}

// Get Teacher Role ID
$roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE LOWER(role_name) = 'teacher' LIMIT 1");
$roleStmt->execute();
$role_id = $roleStmt->fetchColumn();

// --- 2. RESET PASSWORD LOGIC ---
if (isset($_GET['reset_pw'])) {
    try {
        $new_plain_pw = generateRandomPassword();
        $hashed_pw = password_hash($new_plain_pw, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT u.user_id, u.username, t.full_name, t.email FROM teachers t JOIN users u ON t.user_id = u.user_id WHERE t.teacher_id = ?");
        $stmt->execute([$_GET['reset_pw']]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $update->execute([$hashed_pw, $user['user_id']]);
            
            $reset_data = [
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['full_name'],
                'password' => $new_plain_pw
            ];
            $msg = "Teacher password has been reset!";
        }
    } catch (PDOException $e) { $error = "Reset Error: " . $e->getMessage(); }
}

// --- 3. DELETE LOGIC ---
if (isset($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$_GET['delete']]);
        $uid = $stmt->fetchColumn();
        if ($uid) {
            $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ?")->execute([$_GET['delete']]);
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
            $pdo->commit();
            header("Location: manage-teachers.php?msg=" . urlencode("Teacher record and account deleted."));
            exit();
        }
    } catch (PDOException $e) { $pdo->rollBack(); $error = "Delete Error: " . $e->getMessage(); }
}

// --- 4. REGISTRATION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        try {
            $email = trim($_POST['email']);
            $check = $pdo->prepare("SELECT teacher_id FROM teachers WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->fetch()) {
                $error = "Registration Failed: Email <b>$email</b> already exists.";
            } else {
                $pdo->beginTransaction();
                $fullname = trim($_POST['full_name']);
                $username = generateTeacherUsername($fullname);
                $uCheck = $pdo->prepare("SELECT count(*) FROM users WHERE username = ?");
                $uCheck->execute([$username]);
                if ($uCheck->fetchColumn() > 0) { $username .= rand(10, 99); }

                $auto_pw = generateRandomPassword();
                $stmtU = $pdo->prepare("INSERT INTO users (role_id, username, password, role, full_name, status) VALUES (?, ?, ?, 'teacher', ?, 'active')");
                $stmtU->execute([$role_id, $username, password_hash($auto_pw, PASSWORD_DEFAULT), $fullname]);
                $u_id = $pdo->lastInsertId();

                $stmtT = $pdo->prepare("INSERT INTO teachers (user_id, full_name, email, department_id, course_info) VALUES (?, ?, ?, ?, ?)");
                $stmtT->execute([$u_id, $fullname, $email, $_POST['dept_id'], $_POST['course_info']]);
                
                $pdo->commit();
                $reset_data = ['username' => $username, 'email' => $email, 'name' => $fullname, 'password' => $auto_pw];
                $msg = "Teacher registered successfully!";
            }
        } catch (Exception $e) { $pdo->rollBack(); $error = $e->getMessage(); }
    }
    // (Bulk Import logic remains the same as previous)
}

// Fetch Data
$depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$teachers = $pdo->query("SELECT t.*, d.department_name, u.username FROM teachers t 
                         LEFT JOIN departments d ON t.department_id = d.department_id 
                         JOIN users u ON t.user_id = u.user_id ORDER BY t.teacher_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">
<style>
    .btn-action { padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; border: 1px solid transparent; cursor: pointer; display: inline-block; margin-left: 2px;}
    .btn-print { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .btn-reset { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .btn-edit { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
    .btn-delete { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
</style>

<main class="main-content">
    <header class="page-header">
        <h1 class="page-title">Faculty Management</h1>
    </header>

    <?php if($msg): ?> <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div> <?php endif; ?>
    <?php if($error): ?> <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <?php if($reset_data): ?>
        <div id="printableArea" style="padding:20px; background:#fff; border:2px solid #2563eb; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="color:#2563eb; font-size: 1.1rem;">FACULTY CREDENTIALS:</strong><br>
                Name: <strong><?= htmlspecialchars($reset_data['name']) ?></strong><br>
                Username: <b style="background: #f1f5f9; padding: 2px 5px;"><?= $reset_data['username'] ?></b> | 
                Password: <b style="color:#dc2626; background: #fef2f2; padding: 2px 5px;"><?= $reset_data['password'] ?></b>
            </div>
            <button onclick="printDiv('printableArea')" style="background:#2563eb; color:white; border:none; padding:12px 20px; border-radius:5px; cursor:pointer; font-weight: bold;">Print Login Slip</button>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid" style="display: grid; grid-template-columns: 380px 1fr; gap: 20px;">
        <section class="form-section">
            <div class="section-card">
                <div style="margin-bottom: 20px;">
                    <button id="btn-single" class="tab-btn active" onclick="switchTab('single')">Manual Add</button>
                    <button id="btn-bulk" class="tab-btn" onclick="switchTab('bulk')">Bulk CSV</button>
                </div>
                <div id="form-single">
                    <form method="POST" class="admin-form">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                        <label>Department</label>
                        <select name="dept_id" class="form-select" required>
                            <?php foreach($depts as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                        </select>
                        <label>Primary Course</label>
                        <input type="text" name="course_info" class="form-control" required>
                        <button type="submit" name="add_teacher" class="btn-publish" style="width:100%; margin-top:10px;">Register Teacher</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Faculty Member</th>
                            <th>Dept / Course</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $t): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($t['full_name']) ?></strong><br>
                                <small style="color: #64748b;">User: <?= $t['username'] ?></small>
                            </td>
                            <td>
                                <span class="badge-pending"><?= $t['department_name'] ?></span><br>
                                <small style="color:#2563eb;"><?= $t['course_info'] ?></small>
                            </td>
                            <td style="text-align: right;">
                                <button onclick="printTeacher('<?= addslashes($t['full_name']) ?>', '<?= $t['username'] ?>')" class="btn-action btn-print">Print</button>
                                <a href="manage-teachers.php?reset_pw=<?= $t['teacher_id'] ?>" class="btn-action btn-reset" onclick="return confirm('Reset this teacher\'s password?')">PW Reset</a>
                                <a href="edit-teacher.php?id=<?= $t['teacher_id'] ?>" class="btn-action btn-edit">Edit</a>
                                <a href="manage-teachers.php?delete=<?= $t['teacher_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete teacher?')">Del</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script>
function printTeacher(name, username) {
    var win = window.open('', '', 'height=400,width=600');
    win.document.write('<html><body style="font-family:sans-serif; text-align:center; padding:50px; border: 5px solid #2563eb;">');
    win.document.write('<h1>Faculty Access Card</h1>');
    win.document.write('<p style="font-size: 1.2rem;">Name: <strong>' + name + '</strong></p>');
    win.document.write('<p>Username: <strong>' + username + '</strong></p>');
    win.document.write('<p>Initial Password: <strong>teacher123</strong></p>');
    win.document.write('<hr><p><small>Please change your password after your first login.</small></p>');
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}

function printDiv(divId) {
    var content = document.getElementById(divId).innerHTML;
    var win = window.open('', '', 'height=500,width=700');
    win.document.write('<html><body style="font-family:sans-serif; padding:40px;">' + content + '</body></html>');
    win.document.close();
    win.print();
}

function switchTab(t) {
    document.getElementById('form-single').style.display = t=='single'?'block':'none';
    document.getElementById('btn-single').classList.toggle('active', t=='single');
}
</script>

<?php include '../includes/footer.php'; ?>