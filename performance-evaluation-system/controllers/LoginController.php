<?php
// controllers/LoginController.php
require_once '../config/config.php';
session_start();

$usernameRaw = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($usernameRaw === '' || $password === '') {
    header("Location: ../login.php?error=1");
    exit();
}

try {
    // Function to attempt login
    function attemptLogin($pdo, $u, $p) {
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$u]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($p, $user['password'])) {
            return $user;
        }
        return false;
    }

    // Attempt 1: Raw input
    $user = attemptLogin($pdo, $usernameRaw, $password);

    // Attempt 2: Lowercase input (if different)
    if (!$user && strtolower($usernameRaw) !== $usernameRaw) {
        $user = attemptLogin($pdo, strtolower($usernameRaw), $password);
    }

    if ($user) {
        // Clear old session and start fresh
        session_regenerate_id();
        
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role_name'];

        // Role-based redirection
        if ($user['role_name'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['role_name'] === 'teacher') {
            header("Location: ../teacher/dashboard.php");
        } elseif ($user['role_name'] === 'student') {
            header("Location: ../student/student_dashboard.php");
        } else {
            header("Location: ../login.php?error=1");
        }
        exit();
    } else {
        header("Location: ../login.php?error=1");
        exit();
    }
} catch (PDOException $e) {
    header("Location: ../login.php?error=1");
    exit();
}