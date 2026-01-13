<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
    $stmt->execute([$_SESSION['username']]); // Assuming username is teacher_id
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$user && isset($_SESSION['user_id'])) {
         // Fallback to user_id check
         $stmt = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ?");
         $stmt->execute([$_SESSION['user_id']]);
         $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Silent fail
}

require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';
?>

<div class="dashboard-wrapper">
    <div style="max-width: 800px; margin: 0 auto;">
        <h2 style="margin-bottom: 25px; color: #1e293b;">My Profile</h2>

        <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display:flex; gap:30px; align-items:start;">
            
            <div style="width: 120px; height: 120px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-user" style="font-size: 3rem; color: #94a3b8;"></i>
            </div>

            <div style="flex:1;">
                <h3 style="margin-top:0; font-size:1.5rem; color:#0f172a;"><?= htmlspecialchars($user['full_name'] ?? $_SESSION['full_name']) ?></h3>
                <p style="color:#64748b; margin-top:5px;">Teacher ID: <strong><?= htmlspecialchars($user['teacher_id'] ?? 'N/A') ?></strong></p>
                <hr style="margin:20px 0; border:0; border-top:1px solid #f1f5f9;">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label style="display:block; font-size:0.85rem; color:#64748b; margin-bottom:5px;">Email Address</label>
                        <div style="font-weight:600; color:#334155;"><?= htmlspecialchars($user['email'] ?? 'Not set') ?></div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; color:#64748b; margin-bottom:5px;">Phone Number</label>
                        <div style="font-weight:600; color:#334155;"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; color:#64748b; margin-bottom:5px;">Department</label>
                        <div style="font-weight:600; color:#334155;"><?= htmlspecialchars($user['department_id'] ?? 'Not set') ?></div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; color:#64748b; margin-bottom:5px;">Course Info</label>
                        <div style="font-weight:600; color:#334155;"><?= htmlspecialchars($user['course_info'] ?? 'Not set') ?></div>
                    </div>
                </div>

                <div style="margin-top:30px;">
                    <a href="change_password.php" style="display:inline-block; padding:10px 20px; background:#0d9488; color:white; text-decoration:none; border-radius:8px; font-weight:bold;">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../includes/teacher-footer.php'; ?>
