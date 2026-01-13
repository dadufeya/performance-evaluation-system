<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('student'); // Only students can evaluate

if(!isset($_GET['teacher_id'])) die("Teacher not specified");

$teacher_id = $_GET['teacher_id'];

// Get teacher info
$teacher = $pdo->prepare("SELECT t.*, d.department_name FROM teachers t JOIN departments d ON t.department_id = d.department_id WHERE t.teacher_id=?");
$teacher->execute([$teacher_id]);
$teacher = $teacher->fetch();
if(!$teacher) die("Teacher not found");

// Fetch evaluation questions (from your evaluation_questions table)
$questions = $pdo->query("SELECT * FROM evaluation_questions ORDER BY created_at ASC")->fetchAll();

// Handle submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $student_id = $_SESSION['user_id']; // current student
    $stmt = $pdo->prepare("INSERT INTO teacher_evaluations (teacher_id, student_id, question_id, answer) VALUES (?,?,?,?)");
    foreach($_POST['answers'] as $qid => $ans){
        $stmt->execute([$teacher_id, $student_id, $qid, $ans]);
    }
    echo "<div style='padding:20px; background:#dcfce7; color:#166534; border-radius:8px;'>✅ Evaluation submitted successfully!</div>";
}
?>

<div class="admin-container">
    <h2>Evaluate Teacher: <?= htmlspecialchars($teacher['full_name']) ?></h2>
    <p>Department: <?= htmlspecialchars($teacher['department_name']) ?><br>Course: <?= htmlspecialchars($teacher['course_info']) ?></p>

    <form method="POST">
        <?php foreach($questions as $q): ?>
            <div style="margin-bottom:15px; padding:10px; border:1px solid #ccc; border-radius:8px;">
                <label><b><?= htmlspecialchars($q['question_text']) ?></b></label><br>
                <?php if($q['question_type'] == 'scale'): ?>
                    <select name="answers[<?= $q['question_id'] ?>]" required>
                        <option value="">--Select--</option>
                        <?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                    </select>
                <?php elseif($q['question_type'] == 'boolean'): ?>
                    <select name="answers[<?= $q['question_id'] ?>]" required>
                        <option value="">--Select--</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                <?php else: ?>
                    <textarea name="answers[<?= $q['question_id'] ?>]" required style="width:100%; height:60px;"></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Submit Evaluation</button>
    </form>
</div>
