<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


if (isset($_POST['add'])) {
$pdo->prepare("INSERT INTO academic_years (year_name) VALUES (?)")
->execute([$_POST['year_name']]);
}
$years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>

<main>
<h3>Manage Academic Years</h3>
<form method="post">
<input name="year_name" placeholder="1st Year" required>
<button name="add">Add</button>
</form>
<ul>
<?php foreach ($years as $y): ?>
<li><?= $y['year_name'] ?></li>
<?php endforeach; ?>
</ul>
</main>
<?php require_once '../includes/footer.php'; ?>