<?php
require_once 'config/config.php';

$username = '131';

echo "Checking user: '$username'\n";

// Check in users table
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User NOT FOUND in users table.\n";
    // Check if it exists with different case?
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([strtolower($username)]);
    $userLower = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userLower) {
        echo "But found lowercased: " . $userLower['username'] . "\n";
    }
} else {
    echo "User FOUND:\n";
    print_r($user);
    
    // Check password verify
    // I can't check the password the user typed (masked), but I can see if the hash is valid format.
    echo "Password Hash Start: " . substr($user['password'], 0, 10) . "...\n";
}

// Check in teachers table
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt->execute([$username]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($teacher) {
    echo "Teacher Record FOUND:\n";
    print_r($teacher);
} else {
    echo "Teacher Record NOT FOUND for id '$username'.\n";
}
?>
