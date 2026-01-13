<?php
require_once 'config/config.php';
echo "<h1>Teacher Account Diagnostics</h1>";
echo "<table border='1' cellpadding='10'><tr><th>Teacher ID</th><th>Name</th><th>User ID (in Teachers)</th><th>Status</th></tr>";

try {
    // 1. Fetch all teachers
    $teachers = $pdo->query("SELECT * FROM teachers")->fetchAll(PDO::FETCH_ASSOC);

    foreach($teachers as $t) {
        $status = "OK";
        $color = "green";

        // Check internal user_id linkage
        if (empty($t['user_id'])) {
            $status = "MISSING LINK (user_id is 0/NULL)";
            $color = "red";
        } else {
            // Check if user actually exists
            $u = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $u->execute([$t['user_id']]);
            $user = $u->fetch();

            if (!$user) {
                $status = "ORPHAN (Linked User ID " . $t['user_id'] . " not found in users table)";
                $color = "red";
            }
        }

        echo "<tr>";
        echo "<td>{$t['teacher_id']}</td>";
        echo "<td>{$t['full_name']}</td>";
        echo "<td>{$t['user_id']}</td>";
        echo "<td style='color:$color; font-weight:bold;'>$status</td>";
        echo "</tr>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
echo "</table>";
?>
