<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';

$msg = "";
$error = "";

if (isset($_POST['send_feedback'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        try {
            // Check if 'feedback' table exists, otherwise reuse 'complaints' logic or creating one
            // Assuming the user meant general feedback or student feedback. 
            // In the original file it was inserting into 'feedback' table.
            
            // NOTE: Inspecting previous logic, it seems 'feedback' table stores generic feedback
            $stmt = $pdo->prepare("INSERT INTO feedback (teacher_id, message, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $message]);
            $msg = "Feedback sent successfully!";
        } catch (PDOException $e) {
            // Fallback if table doesn't exist
            $error = "Error sending feedback: " . $e->getMessage();
        }
    } else {
        $error = "Message cannot be empty.";
    }
}
?>

<div class="dashboard-wrapper">
    <div style="max-width: 700px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px; color: #1e293b;">Student Feedback</h2>
        <p style="margin-bottom: 20px; color: #64748b;">
            This section allows you to record general feedback or notes.
        </p>

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
            <form method="post">
                <label style="display:block; font-weight:bold; margin-bottom:10px; color:#475569;">Your Message</label>
                <textarea name="message" required style="width:100%; height:150px; padding:15px; border:1px solid #cbd5e1; border-radius:8px; margin-bottom:20px; resize:vertical;"></textarea>
                <button name="send_feedback" style="background:#0d9488; color:white; padding:12px 25px; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">
                    Save Feedback
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/teacher-footer.php'; ?>