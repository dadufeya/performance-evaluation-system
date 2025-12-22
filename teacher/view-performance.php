<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-teacher.php';


$sql = "SELECT q.question_text, AVG(e.score) avg_score
FROM evaluations e JOIN questions q ON e.question_id=q.question_id
WHERE e.teacher_id=? GROUP BY q.question_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll();
?>


<main>
<h3>My Performance</h3>
<table>
<tr><th>Question</th><th>Average Score</th></tr>
<?php foreach ($results as $r): ?>
<tr><td><?= $r['question_text'] ?></td><td><?= round($r['avg_score'],2) ?></td></tr>
<?php endforeach; ?>
</table>
</main>
<?php require_once '../includes/footer.php'; ?>