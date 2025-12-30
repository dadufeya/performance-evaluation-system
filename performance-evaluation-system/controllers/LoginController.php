<?php
// controllers/LoginController.php

require_once '../config/config.php';
session_start();

// 1. Get form data safely
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// 2. Validate input
if ($username === '' || $password === '') {
    header("Location: ../login.php?error=1");
    exit();
}

try {
    // 3. Fetch user with role
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.password,
            r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Verify password
    if ($user && password_verify($password, $user['password'])) {

        // 5. Set session variables
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role_name'];

        // 6. Redirect based on role
        switch ($user['role_name']) {
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;

            case 'teacher':
                header("Location: ../teacher/dashboard.php");
                break;

            case 'student':
                header("Location: ../student/dashboard.php");
                break;

            default:
                // Unknown role → logout for safety
                session_destroy();
                header("Location: ../login.php?error=1");
        }
        exit();

    } else {
        // Invalid credentials
        header("Location: ../login.php?error=1");
        exit();
    }

} catch (PDOException $e) {
    // Database error (log this in real apps)
    header("Location: ../login.php?error=1");
    exit();
}
