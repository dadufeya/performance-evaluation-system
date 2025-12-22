<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


$results = $pdo->query("SELECT t.full_name AS teacher, AVG(e.score) AS avg_score
FROM evaluations e
JOIN teachers t ON e.teacher_id=t.teacher_id
GROUP BY e.teacher_id")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main>
<h3>Evaluations Summary</h3>
<table>
<tr><th>Teacher</th><th>Average Score</th></tr>
<?php foreach ($results as $r): ?>
<tr><td><?= $r['teacher'] ?></td><td><?= round($r['avg_score'],2) ?></td></tr>
<?php endforeach; ?>
</table>
</main>
<?php require_once '../includes/footer.php'; ?>