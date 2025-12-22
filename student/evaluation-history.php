<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-student.php';


$stmt = $pdo->prepare("SELECT t.full_name, q.question_text, e.score
FROM evaluations e
JOIN teachers t ON e.teacher_id=t.teacher_id
JOIN questions q ON e.question_id=q.question_id
WHERE e.student_id=?");
$stmt->execute([$_SESSION['user_id']]);
$evaluations = $stmt->fetchAll();
?>
<main>
<h3>My Evaluation History</h3>
<table>
<tr><th>Teacher</th><th>Question</th><th>Score</th></tr>
<?php foreach($evaluations as $ev): ?>
<tr>
<td><?= $ev['full_name'] ?></td>
<td><?= $ev['question_text'] ?></td>
<td><?= $ev['score'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</main>
<?php require_once '../includes/footer.php'; ?>