<?php
require_once '../config/config.php';
session_start();

// Security: Check if student is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/auth.php'; // Optional
require_once '../includes/student-header.php';
require_once '../includes/sidebar-student.php';

$student_id = $_SESSION['user_id'];
$evaluations = []; // Initialize to avoid warning

// Fetch evaluation history
try {
    $stmt = $pdo->prepare("
        SELECT t.full_name AS teacher_name,
               c.course_name,
               q.question_text,
               q.question_type,
               er.answer_value,
               er.submitted_at AS created_at
        FROM evaluation_responses er
        JOIN evaluation_questions q ON er.question_id = q.question_id
        JOIN teachers t ON er.teacher_id = t.teacher_id
        JOIN courses c ON er.course_id = c.course_id
        WHERE er.student_id = ?
        ORDER BY er.submitted_at DESC, t.full_name, q.question_id
    ");
    $stmt->execute([$student_id]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "SQL Error: " . $e->getMessage(); 
}
?>

<style>
    .main-content {
        margin-left: 260px;
        padding: 100px 30px 30px 30px;
        min-height: 100vh;
        background-color: #f8fafc;
        transition: margin-left 0.3s ease;
    }

    .card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .history-table th, .history-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .history-table th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .history-table tr:hover {
        background-color: #f8fafc;
    }

    .badge-score {
        background: #e0f2fe;
        color: #0369a1;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .main-content { margin-left: 0; }
        .history-table { display: block; overflow-x: auto; }
    }
</style>

<main class="main-content">
    <div class="card">
        <h2 style="margin-top:0; color: #1e293b; font-family: 'Inter', sans-serif;">My Evaluation History</h2>
        
        <?php if (isset($error)): ?>
            <div style="padding:15px; background:#fef2f2; color:#991b1b; border-radius:8px; margin-bottom:20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (!$evaluations): ?>
            <p style="color:#64748b; font-style:italic;">You haven't submitted any evaluations yet.</p>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th width="20%">Teacher</th>
                        <th width="20%">Course</th>
                        <th width="30%">Question</th>
                        <th width="15%">Your Answer</th>
                        <th width="15%">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluations as $ev): ?>
                        <tr>
                            <td style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($ev['teacher_name']) ?></td>
                            <td><?= htmlspecialchars($ev['course_name']) ?></td>
                            <td style="color:#475569; font-size:0.95rem;"><?= htmlspecialchars($ev['question_text']) ?></td>
                            <td>
                                <?php
                                    $val = $ev['answer_value'];
                                    // Make sure type exists, defaulting to text if not
                                    $type = $ev['question_type'] ?? 'text';
                                    
                                    if ($type === 'scale' || $type === 'rating') {
                                        echo '<span class="badge-score">'.htmlspecialchars($val).' / 5</span>';
                                    } elseif ($type === 'boolean' || $type === 'yesno') {
                                        echo ($val == '1' || strtolower($val) == 'yes') ? '<b>Yes</b>' : 'No';
                                    } else {
                                        echo '<em style="color:#64748b;">"'.htmlspecialchars(substr($val, 0, 50)).(strlen($val)>50?'...':'').'"</em>';
                                    }
                                ?>
                            </td>
                            <td style="color:#64748b; font-size:0.9rem;">
                                <?= date("M d, Y", strtotime($ev['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<script>
    // Responsive Sidebar Logic (Optional, matches others)
    function fixLayout() {
        const content = document.querySelector('.main-content');
        const sidebar = document.querySelector('.student-sidebar');
        if (content && sidebar) {
            // Logic handled by CSS variable usually, but hardcoded fallback:
            content.style.marginLeft = sidebar.offsetWidth + "px";
        }
    }
    window.addEventListener('resize', fixLayout);
    // window.addEventListener('load', fixLayout); // CSS handles basic case
</script>

<?php 
$footer_path = __DIR__ . "/../includes/student-footer.php";
if (file_exists($footer_path)) { include_once($footer_path); }
?>
