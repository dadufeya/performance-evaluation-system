<?php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

// Security: Check if student is logged in
checkAccess('student');

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// --- 1. FETCH DATA REGISTERED BY ADMIN ---
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.username, 
            s.full_name, s.student_id_card, s.gender, s.batch,
            d.department_name,
            y.year_name,
            sec.section_number
        FROM users u
        LEFT JOIN students s ON u.user_id = s.user_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        LEFT JOIN academic_years y ON s.year_id = y.year_id
        LEFT JOIN sections sec ON s.section_id = sec.section_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $student = [
            'full_name' => 'Not Found', 'student_id_card' => 'N/A', 'gender' => 'N/A',
            'batch' => 'N/A', 'department_name' => 'Unassigned', 'year_name' => 'N/A',
            'section_number' => 'N/A', 'username' => 'N/A'
        ];
    }
} catch (PDOException $e) {
    $error_message = "System Error: Unable to fetch profile.";
    $student = [
        'full_name' => 'Error Loading', 'student_id_card' => 'N/A', 'gender' => 'N/A',
        'batch' => 'N/A', 'department_name' => 'N/A', 'year_name' => 'N/A',
        'section_number' => 'N/A', 'username' => 'N/A'
    ];
}

// --- 2. HANDLE PASSWORD UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $confirm_pass) {
        $error_message = "The new passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error_message = "New password must be at least 6 characters.";
    } else {
        $vStmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $vStmt->execute([$user_id]);
        $user_row = $vStmt->fetch();

        if ($user_row && password_verify($current_pass, $user_row['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $uStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $uStmt->execute([$hashed, $user_id]);
            $success_message = "Password updated successfully!";
        } else {
            $error_message = "Your current password was incorrect.";
        }
    }
}

// --- VIEW STARTS HERE ---
require_once '../includes/student-header.php';
require_once '../includes/sidebar-student.php';
?>

<style>
    .profile-card { background: #fff; border-radius: 15px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .card-title { display: flex; align-items: center; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
    .card-title i { color: #6366f1; font-size: 20px; }
    .card-title h3 { margin: 0; color: #1e293b; font-size: 1.2rem; }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
    .info-item label { display: block; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; }
    .info-item div { font-size: 1rem; color: #1e293b; font-weight: 500; }
    
    .badge-id { background: #eef2ff; color: #4f46e5; padding: 4px 12px; border-radius: 50px; font-weight: 700; font-size: 0.8rem; border: 1px solid #e0e7ff; }

    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; outline: none; transition: 0.3s; }
    .form-control:focus { border-color: #6366f1; }
    
    .btn-update { background: #6366f1; color: white; border: none; padding: 14px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2); }
    .btn-update:hover { background: #4f46e5; }
</style>

<main class="main-content">
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin: 0;">My Profile</h1>
        <p style="color: #64748b; margin-top: 5px;">Manage your account information and academic details.</p>
    </div>

    <?php if ($error_message): ?>
        <div style="padding:15px; background:#fef2f2; color:#dc2626; border-radius:10px; border:1px solid #fee2e2; margin-bottom:25px;">
            <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div style="padding:15px; background:#f0fdf4; color:#166534; border-radius:10px; border:1px solid #bbf7d0; margin-bottom:25px;">
            <i class="fas fa-check-circle"></i> <?= $success_message ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 25px;">
        <!-- Academic Info -->
        <div class="profile-card">
            <div class="card-title">
                <i class="fas fa-graduation-cap"></i>
                <h3>Academic Details</h3>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name</label>
                    <div><?= htmlspecialchars($student['full_name']) ?></div>
                </div>
                <div class="info-item">
                    <label>Student ID</label>
                    <div><span class="badge-id"><?= htmlspecialchars($student['student_id_card']) ?></span></div>
                </div>
                <div class="info-item">
                    <label>Department</label>
                    <div><?= htmlspecialchars($student['department_name'] ?? 'Unassigned') ?></div>
                </div>
                <div class="info-item">
                    <label>Year / Section</label>
                    <div><?= htmlspecialchars($student['year_name'] ?? 'N/A') ?> - Section <?= htmlspecialchars($student['section_number'] ?? 'N/A') ?></div>
                </div>
                <div class="info-item">
                    <label>Batch</label>
                    <div><?= htmlspecialchars($student['batch']) ?></div>
                </div>
                <div class="info-item">
                    <label>Gender</label>
                    <div><?= htmlspecialchars($student['gender']) ?></div>
                </div>
                <div class="info-item">
                    <label>Username</label>
                    <div style="color: #6366f1; font-weight: 700;"><?= htmlspecialchars($student['username']) ?></div>
                </div>
            </div>
        </div>

        <!-- Security -->
        <div class="profile-card">
            <div class="card-title">
                <i class="fas fa-lock"></i>
                <h3>Account Security</h3>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                </div>
                <button type="submit" name="update_password" class="btn-update">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>