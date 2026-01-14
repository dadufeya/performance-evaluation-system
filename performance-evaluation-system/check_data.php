<?php
require_once 'config/config.php';
$res = $pdo->query('SELECT teacher_id, user_id, full_name, section_id FROM teachers LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($res);
echo "</pre>";
?>
