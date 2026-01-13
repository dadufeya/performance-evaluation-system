<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    
    if (!empty($full_name)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $user_id]);
            $_SESSION['full_name'] = $full_name; // Update session
            $msg = "Profile details updated successfully.";
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $error = "Full Name cannot be empty.";
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        try {
            // Verify old password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_pass, $user['password'])) {
                // Update Password
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update->execute([$new_hash, $user_id]);
                $msg = "Password changed successfully.";
            } else {
                $error = "Incorrect current password.";
            }
        } catch (Exception $e) {
            $error = "Error changing password: " . $e->getMessage();
        }
    }
}

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<!-- Internal Styles specific to this page container -->
<style>
    .admin-container {
        margin-left: 260px;
        padding: 30px;
        background-color: #f8fafc;
        min-height: 100vh;
    }
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    .card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
    }
    .card-title {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: #1e293b;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 10px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #64748b;
    }
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .btn-save {
        background: #2563eb;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-save:hover {
        background: #1d4ed8;
    }
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 500;
    }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<div class="admin-container">
    <h1 style="color:#0f172a; font-weight:800; margin-bottom:10px;">Administration Profile</h1>
    <p style="color:#64748b; margin-bottom:30px;">Manage your account details and security settings.</p>

    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Edit Profile Section -->
        <div class="card">
            <h3 class="card-title">📝 Personal Information</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Username (ID)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                    <small style="color:#94a3b8;">Usernames cannot be changed.</small>
                </div>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Account Status</label>
                    <input type="text" class="form-control" value="<?= ucfirst($u['status']) ?>" disabled style="background:#dcfce7; color:#166534; font-weight:bold; border:none;">
                </div>

                <button type="submit" name="update_profile" class="btn-save">Update Profile</button>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="card">
            <h3 class="card-title">🔒 Security Settings</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" minlength="6" required>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password" minlength="6" required>
                </div>

                <button type="submit" name="change_password" class="btn-save" style="background:#0f172a;">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
