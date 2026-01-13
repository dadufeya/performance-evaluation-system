<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('student');

$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    die("Student not logged in.");
}

// Fetch teachers and courses
$teachers = $pdo->query("SELECT teacher_id, full_name FROM teachers ORDER BY full_name")->fetchAll();
$courses = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name")->fetchAll();

// Initialize variables
$questions = [];
$selected_teacher = $_POST['teacher_id'] ?? null;
$selected_course = $_POST['course_id'] ?? null;

// Load questions if teacher & course are selected
if ($selected_teacher && $selected_course) {
    $stmt = $pdo->prepare("
        SELECT * FROM evaluation_questions ORDER BY question_id
    ");
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php
// Include header & sidebar
$header_path = __DIR__ . "/../includes/student-header.php";
$sidebar_path = __DIR__ . "/../includes/sidebar-student.php";

if (file_exists($header_path)) include_once($header_path);
if (file_exists($sidebar_path)) include_once($sidebar_path);
?>

<div id="main-content-area">
    <div style="background: white; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0; color: #1e293b; font-family: 'Inter', sans-serif;">Teacher Evaluation Form</h2>
        
        <!-- Teacher & Course Selection -->
        <form method="POST" style="margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight:bold;">Select Teacher</label>
                    <select name="teacher_id" required style="width:100%; padding:10px; margin-top:5px; border-radius:5px; border:1px solid #ccc;">
                        <option value="">-- Choose Teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['teacher_id'] ?>" <?= ($selected_teacher == $t['teacher_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight:bold;">Select Course</label>
                    <select name="course_id" required style="width:100%; padding:10px; margin-top:5px; border-radius:5px; border:1px solid #ccc;">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['course_id'] ?>" <?= ($selected_course == $c['course_id']) ? 'selected' : '' ?>>
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

        <!-- Questions Form -->
        <?php if ($questions): ?>
        <form method="POST" action="submit-evaluation.php">
            <input type="hidden" name="teacher_id" value="<?= $selected_teacher ?>">
            <input type="hidden" name="course_id" value="<?= $selected_course ?>">

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

                        <!-- Optional: specify “weight/amount” -->
                        <input type="number" name="weights[<?= $q['question_id'] ?>]" min="0" max="100" placeholder="Amount / Weight" style="width:120px; padding:5px; border-radius:5px; border:1px solid #ccc; margin-left:10px;">

                    <?php elseif ($q['question_type'] === 'boolean'): ?>
                        <label style="background:#f8fafc; padding:8px 15px; border-radius:5px; cursor:pointer; border:1px solid #e2e8f0;">
                            <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="1" required> Yes
                        </label>
                        <label style="background:#f8fafc; padding:8px 15px; border-radius:5px; cursor:pointer; border:1px solid #e2e8f0;">
                            <input type="radio" name="answers[<?= $q['question_id'] ?>]" value="0"> No
                        </label>

                        <input type="number" name="weights[<?= $q['question_id'] ?>]" min="0" max="100" placeholder="Amount / Weight" style="width:120px; padding:5px; border-radius:5px; border:1px solid #ccc; margin-left:10px;">

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
        <?php endif; ?>
    </div>
</div>

<script>
    function fixLayout() {
        const content = document.getElementById('main-content-area');
        const sidebar = document.querySelector('.student-sidebar');
        const header = document.querySelector('.student-header');

        if (content) {
            content.style.padding = "30px";
            content.style.transition = "all 0.3s ease";
            
            if (sidebar) content.style.marginLeft = sidebar.offsetWidth + "px";
            else content.style.marginLeft = "260px"; 

            if (header) content.style.marginTop = header.offsetHeight + "px";
            else content.style.marginTop = "70px"; 
        }
    }
    window.addEventListener('load', fixLayout);
    window.addEventListener('resize', fixLayout);
</script>

<?php 
$footer_path = __DIR__ . "/../includes/student-footer.php";
if (file_exists($footer_path)) { include_once($footer_path); }
?>
