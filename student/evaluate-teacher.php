<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-student.php';


$teacher_id = $_GET['teacher_id'];
$questions = $pdo->query("SELECT * FROM questions")->fetchAll();


if (isset($_POST['submit'])) {
foreach ($_POST['answer'] as $qid => $score) {
$pdo->prepare("INSERT INTO evaluations (student_id, teacher_id, question_id, score) VALUES (?,?,?,?)")
->execute([$_SESSION['user_id'], $teacher_id, $qid, $score]);
}
echo '<p>Evaluation submitted</p>';
}
?>


<main>
<h3>Evaluate Teacher</h3>
<form method="post">
<?php foreach ($questions as $q): ?>
<p><?= $q['question_text'] ?></p>
<select name="answer[<?= $q['question_id'] ?>]">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
</select>
<?php endforeach; ?>
<button name="submit">Submit</button>
</form>
</main>
<?php require_once '../includes/footer.php'; ?>