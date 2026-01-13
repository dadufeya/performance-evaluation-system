<?php
require_once 'config/config.php';

$username = '131';
$newPassword = 'password123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// 1. Check if user exists
$stmt = $pdo->prepare("SELECT user_id, username, status FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found: " . $user['username'] . " (Status: " . $user['status'] . ")\n";
    
    // 2. Update password and status
    $update = $pdo->prepare("UPDATE users SET password = ?, status = 'active' WHERE user_id = ?");
    $update->execute([$hash, $user['user_id']]);
    
    echo "✅ Password has been reset to: '$newPassword'\n";
    echo "✅ Status enforced to: 'active'\n";
    
} else {
    echo "❌ User '$username' not found in database.\n";
    // Try to find if it exists in teachers table but not users table (orphaned?)
    $tCheck = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
    $tCheck->execute([$username]);
    $teacher = $tCheck->fetch();
    if($teacher) {
        echo "⚠️ Found in teachers table but not users table. Creating user record...\n";
        // Create user record
        $ins = $pdo->prepare("INSERT INTO users (username, password, full_name, role_id, status) VALUES (?, ?, ?, 2, 'active')");
        $ins->execute([$username, $hash, $teacher['full_name']]);
        echo "✅ User record created with password: '$newPassword'\n";
    }
}
?>
