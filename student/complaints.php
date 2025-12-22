<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';


if (isset($_POST['send'])) {
$pdo->prepare("INSERT INTO complaints (user_id, message) VALUES (?,?)")
->execute([$_SESSION['user_id'], $_POST['message']]);
}
?>
<main>
<h3>Complaints</h3>
<form method="post">
<textarea name="message" required></textarea>
<button name="send">Send</button>
</form>
</main>
<?php require_once '../includes/footer.php'; ?>