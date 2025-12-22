<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
<form method="POST" action="controllers/LoginController.php">
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
</form>
<?php if (isset($_GET['error'])) echo '<p>Invalid credentials</p>'; ?>
</body>
</html>