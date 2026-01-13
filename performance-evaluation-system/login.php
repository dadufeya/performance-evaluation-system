<?php 
if (session_status() === PHP_SESSION_NONE) session_start(); 
require_once 'config/constants.php'; 

// If user is already logged in, send them to index.php to be redirected to their dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PES Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --dark: #0f172a;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 40px 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .login-header h2 span { color: var(--primary); }
        .login-header p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px; }

        .form-group { text-align: left; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--dark); margin-bottom: 8px; }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .error-message {
            background-color: #fef2f2;
            color: #991b1b;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h2>PES<span>login</span></h2>
        <p>Enter your credentials to access the system</p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <strong>Login Failed!</strong> Invalid username or password.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>controllers/LoginController.php">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="e.g. admin_01" required autofocus>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit">Login to Dashboard</button>
    </form>

    <div style="margin-top: 25px; font-size: 0.8rem; color: var(--text-muted);">
        &copy; 2026 Performance Evaluation System
    </div>
</div>

</body>
</html>