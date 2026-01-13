<?php
require_once 'config/config.php';

$username = '131';

// 1. Get the correct User ID for username '131'
$u = $pdo->prepare("SELECT user_id, username FROM users WHERE username = ?");
$u->execute([$username]);
$newUser = $u->fetch();

if ($newUser) {
    echo "Found correct User ID for '131': " . $newUser['user_id'] . "\n";
    
    // 2. Update the Teacher record to point to this User ID
    $update = $pdo->prepare("UPDATE teachers SET user_id = ? WHERE teacher_id = ?");
    $update->execute([$newUser['user_id'], $username]);
    
    if ($update->rowCount() > 0) {
        echo "✅ SUCCESS: Teacher 131 is now linked to User ID " . $newUser['user_id'] . "\n";
    } else {
        echo "⚠️ No changes made (maybe already linked?).\n";
    }
} else {
    echo "❌ ERROR: User '131' not found. Did fix_login_131.php run?\n";
}
?>
