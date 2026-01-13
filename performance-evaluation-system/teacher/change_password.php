<?php
require_once '../config/config.php';
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_pass, $user['password'])) {
            // Update password
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($update->execute([$new_hash, $user_id])) {
                $msg = "Password changed successfully!";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}

require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';
?>

<div class="dashboard-wrapper">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px; color: #1e293b;">Change Password</h2>

        <?php if ($msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✅ <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #475569;">Current Password</label>
                    <input type="password" name="current_password" required 
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #475569;">New Password</label>
                    <input type="password" name="new_password" required minlength="6"
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <small style="color: #64748b;">Must be at least 6 characters long.</small>
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #475569;">Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <button type="submit" 
                        style="width: 100%; background: #2563eb; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/teacher-footer.php'; ?>
