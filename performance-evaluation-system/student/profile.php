<?php
require_once '../includes/auth.php';
// Ensure only students can access this
checkAccess('student'); 
require_once '../config/config.php'; 

// Use the attractive student header and sidebar
require_once '../includes/student-header.php'; 
require_once '../includes/sidebar-student.php'; 

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// --- 1. FETCH DATA REGISTERED BY ADMIN ---
try {
    // We use LEFT JOIN so the page still loads even if the admin hasn't assigned a year/section yet
    $stmt = $pdo->prepare("
        SELECT 
            u.username, u.email, 
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

    // If the record is missing for some reason, create an empty fallback array to prevent errors
    if (!$student) {
        $student = [
            'full_name' => 'Not Found', 'student_id_card' => 'N/A', 'gender' => 'N/A',
            'batch' => 'N/A', 'department_name' => 'Unassigned', 'year_name' => 'N/A',
            'section_number' => 'N/A', 'username' => 'N/A', 'email' => 'N/A'
        ];
    }
} catch (PDOException $e) {
    $error_message = "System Error: Unable to fetch profile. " . $e->getMessage();
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
        // Verify current password first
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
?>

<style>
    .profile-container { margin-left: 280px; padding: 40px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; min-height: 100vh; }
    .page-title { margin-bottom: 30px; }
    .page-title h1 { color: #1e293b; font-size: 24px; font-weight: 700; margin: 0; }
    .page-title p { color: #64748b; margin-top: 5px; }

    .profile-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 25px; }
    .card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
    .card-header i { color: #3b82f6; font-size: 20px; }
    .card-header h3 { margin: 0; color: #334155; font-size: 18px; }

    /* Info Display Styling */
    .info-list { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-group { margin-bottom: 15px; }
    .info-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 15px; color: #1e293b; font-weight: 500; margin-top: 4px; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; background: #eff6ff; color: #2563eb; font-weight: 600; font-size: 12px; }

    /* Form Styling */
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: 0.2s; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .btn-save { background: #2563eb; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; transition: 0.2s; }
    .btn-save:hover { background: #1d4ed8; }

    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
    .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

    @media (max-width: 992px) { .profile-container { margin-left: 0; padding: 20px; } .profile-grid { grid-template-columns: 1fr; } }
</style>

<div class="profile-container">
    <div class="page-title">
        <h1>My Profile</h1>
        <p>This information is managed by the Admin. Please contact them for corrections.</p>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= $error_message ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success_message ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-id-card"></i>
                <h3>Academic Profile</h3>
            </div>
            <div class="info-list">
                <div class="info-group">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?= htmlspecialchars($student['full_name']) ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Student ID</div>
                    <div class="info-value"><span class="badge"><?= htmlspecialchars($student['student_id_card']) ?></span></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Department</div>
                    <div class="info-value"><?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Academic Year</div>
                    <div class="info-value"><?= htmlspecialchars($student['year_name'] ?? 'N/A') ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Section</div>
                    <div class="info-value">Section <?= htmlspecialchars($student['section_number'] ?? 'N/A') ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Batch</div>
                    <div class="info-value"><?= htmlspecialchars($student['batch']) ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?= htmlspecialchars($student['gender']) ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Login Username</div>
                    <div class="info-value" style="color: #6366f1;"><?= htmlspecialchars($student['username']) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-shield-alt"></i>
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
                <button type="submit" name="update_password" class="btn-save">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>