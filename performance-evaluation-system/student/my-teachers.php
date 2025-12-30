<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-student.php';


$student_id = $_SESSION['user_id'];


$sql = "SELECT DISTINCT t.teacher_id, t.full_name
FROM teacher_course tc
JOIN teachers t ON tc.teacher_id=t.teacher_id
JOIN students s ON s.year_id=tc.year_id AND s.section_id=tc.section_id
WHERE s.user_id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);
$teachers = $stmt->fetchAll();
?>


<main>
<h3>My Teachers</h3>
<ul>
<?php foreach ($teachers as $t): ?>
<li>
<?= $t['full_name'] ?>
<a href="evaluate-teacher.php?teacher_id=<?= $t['teacher_id'] ?>">Evaluate</a>
</li>
<?php endforeach; ?>
</ul>
</main>
<?php require_once '../includes/footer.php'; ?>