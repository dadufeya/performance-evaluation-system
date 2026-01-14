<?php
require_once '../config/config.php';
session_start();

// Check access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/teacher-header.php';
require_once '../includes/sidebar-teacher.php';

$msg = "";
$error = "";

// Handle Send
if (isset($_POST['send_complaint'])) {
    $message = trim($_POST['message']);
    $about_teacher_id = $_POST['about_teacher_id'] ?? null; // Teacher being complained about
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO complaints (user_id, message, teacher_id, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$_SESSION['user_id'], $message, $about_teacher_id]);
            $msg = "Complaint submitted successfully. The administration has been notified.";
        } catch (PDOException $e) {
            $error = "Error submitting complaint: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a message.";
    }
}

// Fetch all teachers for the dropdown
$teachers = [];
try {
    $stmt = $pdo->query("SELECT teacher_id, full_name, department_id FROM teachers ORDER BY full_name ASC");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
}

// Fetch my complaints
$my_complaints = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $my_complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching complaints: " . $e->getMessage());
}
?>

<div class="dashboard-wrapper">
    <div style="max-width: 900px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="margin: 0; color: #1e293b;">My Complaints</h2>
                <p style="margin: 5px 0 0; color: #64748b;">Report issues or send feedback to the administration.</p>
            </div>
        </div>

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

        <!-- Submission Form -->
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #0f172a; font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
                <i class="fas fa-pen"></i> Submit New Complaint
            </h3>
            <form method="post">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #475569;">Complaint About (Optional)</label>
                    <select name="about_teacher_id" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: sans-serif;">
                        <option value="">General Complaint (Not about a specific teacher)</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= htmlspecialchars($t['teacher_id']) ?>">
                                <?= htmlspecialchars($t['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; display: block; margin-top: 5px;">
                        If this complaint is about a specific teacher, select their name. Otherwise, leave as "General Complaint".
                    </small>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #475569;">Description of Issue</label>
                    <textarea name="message" required rows="4" 
                              style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: sans-serif; resize: vertical;"
                              placeholder="Describe your concern in detail..."></textarea>
                </div>
                <button type="submit" name="send_complaint" 
                        style="background: #ef4444; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </form>
        </div>

        <!-- Previous Complaints List -->
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #0f172a; font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
                <i class="fas fa-history"></i> Submission History
            </h3>

            <?php if (empty($my_complaints)): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    No complaints found.
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 0.85rem; border-bottom: 2px solid #e2e8f0;">Date</th>
                            <th style="text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 0.85rem; border-bottom: 2px solid #e2e8f0;">Message</th>
                            <th style="text-align: center; padding: 12px; background: #f8fafc; color: #64748b; font-size: 0.85rem; border-bottom: 2px solid #e2e8f0;">Status</th>
                            <th style="text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 0.85rem; border-bottom: 2px solid #e2e8f0;">Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_complaints as $c): ?>
                            <tr>
                                <td style="padding: 15px 12px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 0.9rem; vertical-align: top;">
                                    <?= date('M d, Y', strtotime($c['created_at'])) ?><br>
                                    <small><?= date('h:i A', strtotime($c['created_at'])) ?></small>
                                </td>
                                <td style="padding: 15px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top;">
                                    <?= htmlspecialchars($c['message']) ?>
                                </td>
                                <td style="padding: 15px 12px; border-bottom: 1px solid #f1f5f9; text-align: center; vertical-align: top;">
                                    <?php 
                                        $statusColor = match($c['status']) {
                                            'pending' => '#f59e0b',
                                            'resolved' => '#10b981',
                                            default => '#64748b'
                                        };
                                        $statusBg = match($c['status']) {
                                            'pending' => '#fef3c7',
                                            'resolved' => '#d1fae5',
                                            default => '#f1f5f9'
                                        };
                                    ?>
                                    <span style="background: <?= $statusBg ?>; color: <?= $statusColor ?>; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                        <?= htmlspecialchars($c['status']) ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 12px; border-bottom: 1px solid #f1f5f9; color: #475569; font-size: 0.9rem; vertical-align: top;">
                                    <?php if ($c['feedback']): ?>
                                        <div style="background: #f0fdf4; padding: 10px; border-radius: 6px; border-left: 3px solid #10b981;">
                                            <?= htmlspecialchars($c['feedback']) ?>
                                        </div>
                                    <?php else: ?>
                                        <em style="color: #cbd5e1;">No feedback yet</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/teacher-footer.php'; ?>