<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';

// Fetch admin profile details
$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ? WHERE admin_id = ?");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['email'],
        $_SESSION['user_id']
    ]);
    header("Location: profile.php?success=1");
    exit();
}
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main class="content" style="margin-top: 60px;">
    <header class="page-header">
        <h3>Admin Profile</h3>
    </header>

    <section class="profile-section">
        <?php if (isset($_GET['success'])): ?>
            <p class="success-message">Profile updated successfully!</p>
        <?php endif; ?>

        <form method="post" class="profile-form">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>

            <button type="submit" name="update_profile" class="btn-submit">Update Profile</button>
        </form>
    </section>
</main>

<style>
    .content {
        padding: 20px;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .page-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .profile-section {
        max-width: 600px;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .success-message {
        color: #28a745;
        text-align: center;
        margin-bottom: 15px;
    }

    .profile-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .profile-form label {
        font-weight: bold;
    }

    .profile-form input {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
    }

    .btn-submit {
        padding: 10px 15px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        transition: background 0.3s;
    }

    .btn-submit:hover {
        background-color: #0056b3;
    }
</style>

<?php require_once '../includes/footer.php'; ?>