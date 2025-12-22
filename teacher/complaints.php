<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-teacher.php';


if(isset($_POST['send'])){
$stmt = $pdo->prepare("INSERT INTO complaints (user_id, message) VALUES (?,?)");
$stmt->execute([$_SESSION['user_id'], $_POST['message']]);
}
?>
<main>
<h3>Submit Complaint</h3>
<form method="post">
<textarea name="message" required></textarea>
<button name="send">Send</button>
</form>
</main>
<?php require_once '../includes/footer.php'; ?>