<?php
require_once '../config/config.php';
session_start();

// Security: Check if student is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/auth.php'; // Optional, but good practice
require_once '../includes/student-header.php';
require_once '../includes/sidebar-student.php';

$student_id = $_SESSION['user_id'];
?>

<style>
    /* Ensure content is pushed correctly */
    .main-content {
        margin-left: 260px;
        padding: 100px 30px 30px 30px; /* Adjust top padding for fixed header */
        min-height: 100vh;
        background-color: #f8fafc;
    }
    /* Responsive adjustment */
    @media (max-width: 768px) {
        .main-content { margin-left: 0; }
    }
</style>

<main class="main-content">
    <div style="background: white; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0; color: #1e293b; font-family: 'Inter', sans-serif;">Teacher Evaluation Form</h2>
        
        <!-- Selection Form -->
        <form method="POST" style="margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight:bold;">Select Teacher</label>
                    <select name="teacher_id" required style="width:100%; padding:10px; margin-top:5px; border-radius:5px; border:1px solid #ccc;">
                        <option value="">-- Choose Teacher --</option>
                        <?php 
                        // Fetch Teachers linked to Student's Year/Section
                        $stmt = $pdo->prepare("
                            SELECT DISTINCT t.teacher_id, t.full_name 
                            FROM teacher_course tc
                            JOIN teachers t ON tc.teacher_id = t.teacher_id
                            JOIN students s ON s.year_id = tc.year_id AND s.section_id = tc.section_id
                            WHERE s.user_id = ?
                            ORDER BY t.full_name
                        ");
                        $stmt->execute([$student_id]);
                        $teachers = $stmt->fetchAll();

                        foreach ($teachers as $t): ?>
                            <option value="<?= $t['teacher_id'] ?>" <?= (($_POST['teacher_id'] ?? '') == $t['teacher_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight:bold;">Select Course</label>
                    <select name="course_id" required style="width:100%; padding:10px; margin-top:5px; border-radius:5px; border:1px solid #ccc;">
                        <option value="">-- Choose Course --</option>
                        <?php 
                        // Fetch Courses linked to Student's Year/Section
                        $stmt = $pdo->prepare("
                            SELECT DISTINCT c.course_id, c.course_name 
                            FROM teacher_course tc
                            JOIN courses c ON tc.course_id = c.course_id
                            JOIN students s ON s.year_id = tc.year_id AND s.section_id = tc.section_id
                            WHERE s.user_id = ?
                            ORDER BY c.course_name
                        ");
                        $stmt->execute([$student_id]);
                        $courses = $stmt->fetchAll();

                        foreach ($courses as $c): ?>
                            <option value="<?= $c['course_id'] ?>" <?= (($_POST['course_id'] ?? '') == $c['course_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" style="background:#6366f1; color:white; border:none; padding:12px 25px; border-radius:5px; cursor:pointer; font-weight:bold;">
                Load Questions
            </button>
        </form>

        <?php 
        if (isset($_POST['teacher_id'], $_POST['course_id'])): 
            $tid = $_POST['teacher_id'];
            $cid = $_POST['course_id'];

            // === CRITICAL CHECK: Has student already evaluated this teacher/course? ===
            $checkStmt = $pdo->prepare("SELECT evaluation_id FROM evaluations WHERE student_id=? AND teacher_id=? AND course_id=?");
            $checkStmt->execute([$student_id, $tid, $cid]);
            
            if ($checkStmt->fetch()) {
                echo "<div style='padding:15px; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:8px; margin-bottom:20px;'>
                        <strong>⚠️ Notice:</strong> You have already submitted an evaluation for this teacher in this course. Duplicate submissions are not accepted.
                      </div>";
            } else {
                // Not evaluated yet? Show the form!
                $questions = $pdo->query("SELECT * FROM evaluation_questions ORDER BY question_id")->fetchAll();
                
                if (!$questions): ?>
                    <p>No questions found in the database.</p>
                <?php else: ?>
                    <form method="POST" action="save-evaluation.php">
                        <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($tid) ?>">
                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($cid) ?>">

                        <?php foreach ($questions as $q): ?>
                            <div style="margin-bottom: 25px; padding: 20px; border: 1px solid #f1f5f9; border-left: 5px solid #6366f1; border-radius: 8px; background:#fff;">
                                <p style="margin-top:0; font-size:1.1rem; color:#1e293b;"><strong><?= htmlspecialchars($q['question_text']) ?></strong></p>
                                
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <?php if ($q['question_type'] === 'scale'): ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <label style="background:#f8fafc; padding:8px 15px; border-radius:5px; cursor:pointer; border:1px solid #e2e8f0;">
                                            <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="<?= $i ?>" required> <?= $i ?>
                                        </label>
                                    <?php endfor; ?>
                                <?php elseif ($q['question_type'] === 'boolean'): ?>
                                    <label style="background:#f8fafc; padding:8px 15px; border-radius:5px; cursor:pointer; border:1px solid #e2e8f0;">
                                        <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="1" required> Yes
                                    </label>
                                    <label style="background:#f8fafc; padding:8px 15px; border-radius:5px; cursor:pointer; border:1px solid #e2e8f0;">
                                        <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="0"> No
                                    </label>
                                <?php else: ?>
                                    <textarea name="answers[<?= $q['question_id'] ?>]" style="width:100%; height:80px; padding:10px; border-radius:5px; border:1px solid #cbd5e1;" required placeholder="Write your feedback here..."></textarea>
                                <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" style="background: #10b981; color: white; padding: 15px 40px; border:none; border-radius:8px; cursor:pointer; font-size:1.1rem; font-weight:bold; width:100%;">
                            Submit Evaluation
                        </button>
                    </form>
                <?php endif; 
            }
        endif; 
        ?>
    </div>
</main>

<?php 
$footer_path = __DIR__ . "/../includes/student-footer.php";
if (file_exists($footer_path)) { include_once($footer_path); }
?>