<?php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

// Security: Check if student is logged in
checkAccess('student');

$student_id = $_SESSION['user_id'];
$evaluations = [];

// Fetch evaluation history
try {
    // We prioritize joining with 'questions' (Unified System A)
    // but fallback to 'evaluation_questions' (System B) if necessary for legacy data
    $sql = "
        SELECT 
            t.full_name AS teacher_name,
            c.course_name,
            COALESCE(q.question_text, eq.question_text) as question_text,
            COALESCE(q.question_type, eq.question_type) as question_type,
            er.answer_value,
            er.submitted_at
        FROM evaluation_responses er
        LEFT JOIN questions q ON er.question_id = q.question_id
        LEFT JOIN evaluation_questions eq ON er.question_id = eq.question_id
        JOIN teachers t ON er.teacher_id = t.teacher_id
        JOIN courses c ON er.course_id = c.course_id
        WHERE er.student_id = (SELECT student_id FROM students WHERE user_id = ?)
        ORDER BY er.submitted_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

// --- VIEW STARTS HERE ---
require_once __DIR__ . '/../includes/student-header.php';
require_once __DIR__ . '/../includes/sidebar-student.php';
?>

<style>
    .history-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .history-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
    }

    .history-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }

    .history-table td {
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .history-table tr:last-child td { border-bottom: none; }

    .badge-score {
        background: #eef2ff;
        color: #4f46e5;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.85rem;
        border: 1px solid #e0e7ff;
    }

    .badge-yes { background: #f0fdf4; color: #166534; padding: 4px 10px; border-radius: 6px; font-weight: 700; border: 1px solid #bbf7d0; }
    .badge-no { background: #fef2f2; color: #991b1b; padding: 4px 10px; border-radius: 6px; font-weight: 700; border: 1px solid #fecaca; }
</style>

<main class="main-content">
    <div style="margin-bottom: 30px;">
        <h3 style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin: 0;">Evaluation History</h3>
        <p style="color: #64748b; margin-top: 5px;">A record of all your submitted feedback and evaluations.</p>
    </div>

    <div class="history-card">
        <?php if (isset($error)): ?>
            <div style="padding:15px; background:#fef2f2; color:#dc2626; border-radius:10px; border:1px solid #fee2e2; margin-bottom:20px;">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (empty($evaluations)): ?>
            <div style="text-align:center; padding:40px;">
                <i class="fas fa-history" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                <p style="color:#64748b; font-size: 1.1rem; margin:0;">You haven't submitted any evaluations yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Course</th>
                            <th>Question</th>
                            <th>Response</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $ev): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($ev['teacher_name']) ?></div>
                                </td>
                                <td>
                                    <div style="color:#475569; font-size:0.9rem;"><?= htmlspecialchars($ev['course_name']) ?></div>
                                </td>
                                <td>
                                    <div style="color:#64748b; font-size:0.9rem; max-width: 300px;"><?= htmlspecialchars($ev['question_text']) ?></div>
                                </td>
                                <td>
                                    <?php
                                        $val = $ev['answer_value'];
                                        $type = $ev['question_type'] ?? 'text';
                                        
                                        if (in_array($type, ['scale', 'rating'])) {
                                            echo '<span class="badge-score">'.htmlspecialchars($val).' / 5</span>';
                                        } elseif (in_array($type, ['boolean', 'yesno'])) {
                                            if ($val == '1' || strtolower($val) == 'yes') {
                                                echo '<span class="badge-yes">Yes</span>';
                                            } else {
                                                echo '<span class="badge-no">No</span>';
                                            }
                                        } else {
                                            echo '<div style="font-style:italic; color:#64748b; font-size:0.9rem;">"'.htmlspecialchars($val).'"</div>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div style="color:#64748b; font-size:0.85rem;">
                                        <?= date("M d, Y", strtotime($ev['submitted_at'])) ?>
                                        <br>
                                        <small><?= date("h:i A", strtotime($ev['submitted_at'])) ?></small>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
