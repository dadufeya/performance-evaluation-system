<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure only students can access
checkAccess('student');

// Get teacher and course from URL
$tid = $_GET['teacher_id'] ?? null;
$cid = $_GET['course_id'] ?? null;
$student_id = $_SESSION['user_id'];

if (!$tid || !$cid) {
    die("Teacher or Course not specified.");
}

// Fetch teacher and course names for display
try {
    $stmtT = $pdo->prepare("SELECT full_name FROM teachers WHERE teacher_id = ?");
    $stmtT->execute([$tid]);
    $teacher_name = $stmtT->fetchColumn();

    $stmtC = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
    $stmtC->execute([$cid]);
    $course_name = $stmtC->fetchColumn();
    
    // Check if student already evaluated this teacher/course
    // 1. Map user_id to student_id
    $stmtS = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmtS->execute([$student_id]);
    $real_student_id = $stmtS->fetchColumn();
    
    $checkEval = $pdo->prepare("SELECT evaluation_id FROM evaluations WHERE student_id = ? AND teacher_id = ? AND course_id = ?");
    $checkEval->execute([$real_student_id, $tid, $cid]);
    $already_evaluated = $checkEval->fetch();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// --- VIEW STARTS HERE ---
require_once __DIR__ . '/../includes/student-header.php';
require_once __DIR__ . '/../includes/sidebar-student.php';
?>

<main class="main-content">
    <div style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 50px; height: 50px; background: #6366f1; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-edit"></i>
            </div>
            <div>
                <h3 style="margin:0; font-size: 1.5rem; color: #1e293b;">Evaluate Teacher</h3>
                <p style="margin:5px 0 0; color: #64748b;">Providing honest feedback helps improve educational quality.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; padding: 15px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div>
                <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Teacher Name</small>
                <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($teacher_name) ?></div>
            </div>
            <div>
                <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Course Title</small>
                <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($course_name) ?></div>
            </div>
        </div>
    </div>

    <div id="evaluation-form-container">
        <?php 
        if ($already_evaluated) {
            echo "<div style='padding:40px; background:white; border:1px solid #e2e8f0; border-radius:15px; text-align:center;'>
                    <div style='width:60px; height:60px; background:#f0fdf4; color:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px; font-size:2rem;'>
                        <i class='fas fa-check-circle'></i>
                    </div>
                    <h4 style='color:#1e293b; margin:0 0 10px;'>Already Submitted</h4>
                    <p style='color:#64748b; margin:0;'>You have already completed the evaluation for this teacher and course. Thank you for your feedback!</p>
                    <a href='my-teachers.php' style='display:inline-block; margin-top:20px; color:#6366f1; text-decoration:none; font-weight:600;'>Return to My Teachers</a>
                  </div>";
        } else {
            // === FETCH ASSIGNED QUESTIONNAIRE ID ===
            $assignStmt = $pdo->prepare("
                SELECT ea.questionnaire_id 
                FROM evaluation_assignments ea
                JOIN students s ON s.department_id = ea.department_id
                WHERE s.user_id = ? 
                AND ea.teacher_id = ? 
                AND ea.course_id = ?
                AND (ea.section = s.section_id OR ea.section = (SELECT section_number FROM sections WHERE section_id = s.section_id))
                AND ea.status = 'active'
                ORDER BY ea.created_at DESC
                LIMIT 1
            ");
            $assignStmt->execute([$student_id, $tid, $cid]);
            $assignment = $assignStmt->fetch();
            $questionnaire_id = $assignment['questionnaire_id'] ?? null;

            if (!$questionnaire_id) {
                echo "<div style='padding:40px; background:white; border:1px solid #fee2e2; border-radius:15px; text-align:center;'>
                        <div style='width:60px; height:60px; background:#fef2f2; color:#dc2626; border-radius:50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px; font-size:2rem;'>
                            <i class='fas fa-exclamation-triangle'></i>
                        </div>
                        <h4 style='color:#1e293b; margin:0 0 10px;'>Assignment Not Found</h4>
                        <p style='color:#64748b; margin:0;'>No active evaluation has been assigned to you for this teacher/course yet.</p>
                      </div>";
            } else {
                $qStmt = $pdo->prepare("SELECT * FROM questions WHERE questionnaire_id = ? ORDER BY question_id");
                $qStmt->execute([$questionnaire_id]);
                $questions = $qStmt->fetchAll();
                
                if (!$questions) {
                    echo "<p style='text-align:center; padding:40px; color:#64748b;'>This evaluation currently has no questions.</p>";
                } else {
                    ?>
                    <form method="POST" action="save-evaluation.php" style="display: grid; gap: 20px;">
                        <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($tid) ?>">
                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($cid) ?>">
                        <input type="hidden" name="questionnaire_id" value="<?= htmlspecialchars($questionnaire_id) ?>">

                        <?php foreach ($questions as $q): ?>
                            <div style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; border-left: 6px solid #6366f1;">
                                <p style="margin: 0 0 20px; font-size: 1.1rem; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($q['question_text']) ?></p>
                                
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <?php if ($q['question_type'] === 'rating' || $q['question_type'] === 'scale'): ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <label style="background: #f8fafc; padding: 12px 20px; border-radius: 10px; cursor: pointer; border: 2px solid #e2e8f0; display: flex; align-items: center; gap: 8px; transition: 0.3s; font-weight: 600;">
                                            <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="<?= $i ?>" required style="accent-color: #6366f1; width: 18px; height: 18px;">
                                            <span><?= $i ?></span>
                                        </label>
                                    <?php endfor; ?>
                                <?php elseif ($q['question_type'] === 'yesno' || $q['question_type'] === 'boolean'): ?>
                                    <label style="background: #f8fafc; padding: 12px 20px; border-radius: 10px; cursor: pointer; border: 2px solid #e2e8f0; display: flex; align-items: center; gap: 8px; transition: 0.3s; font-weight: 600;">
                                        <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="1" required style="accent-color: #6366f1; width: 18px; height: 18px;">
                                        <span>Yes</span>
                                    </label>
                                    <label style="background: #f8fafc; padding: 12px 20px; border-radius: 10px; cursor: pointer; border: 2px solid #e2e8f0; display: flex; align-items: center; gap: 8px; transition: 0.3s; font-weight: 600;">
                                        <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="0" style="accent-color: #6366f1; width: 18px; height: 18px;">
                                        <span>No</span>
                                    </label>
                                <?php else: ?>
                                    <textarea name="answers[<?= $q['question_id'] ?>]" style="width: 100%; min-height: 120px; padding: 15px; border-radius: 10px; border: 2px solid #e2e8f0; font-family: inherit; font-size: 1rem; outline: none; transition: 0.3s;" required placeholder="Please provide your detailed feedback..."></textarea>
                                <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 10px;">
                            <button type="submit" style="background: #6366f1; color: white; padding: 18px; border: none; border-radius: 12px; cursor: pointer; font-size: 1.1rem; font-weight: 800; width: 100%; transition: 0.3s; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); display: flex; align-items: center; justify-content: center; gap: 10px;">
                                <span>Submit Evaluation</span>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                    <?php 
                } // questions else
            } // questionnaire_id else
        } // already evaluated else
        ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>