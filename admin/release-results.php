<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


if (isset($_POST['release'])) {
$pdo->query("UPDATE evaluations SET released=1 WHERE released=0");
echo '<p>Results released successfully!</p>';
}
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main>
<h3>Release Evaluation Results</h3>
<form method="post">
<button name="release">Release Now</button>
</form>
</main>
<?php require_once '../includes/footer.php'; ?>