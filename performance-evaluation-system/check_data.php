<?php
require_once 'config/config.php';
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
echo "ROLES:\n";
foreach($roles as $r) {
    echo "ID: " . $r['role_id'] . " Name: '" . $r['role_name'] . "'\n";
}

echo "\nUSERS (first 5):\n";
$users = $pdo->query("SELECT user_id, username, password, role_id FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach($users as $u) {
    echo "User: " . $u['username'] . " RoleID: " . $u['role_id'] . "\n";
}
?>
