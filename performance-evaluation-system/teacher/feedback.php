<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-teacher.php';


if(isset($_POST['send_feedback'])){
$stmt = $pdo->prepare("INSERT INTO feedback (teacher_id, message) VALUES (?,?)");
$stmt->execute([$_SESSION['user_id'], $_POST['message']]);
echo '<p>Feedback sent successfully!</p>';
}
?>
<main>
<h3>Send Feedback</h3>
<form method="post">
<textarea name="message" required></textarea>
<button name="send_feedback">Send Feedback</button>
</form>
</main>
<?php require_once '../includes/footer.php'; ?>