<?php
require_once 'config/config.php';

echo "<h2>Complaints Raw Data</h2>";
$q = $pdo->query("SELECT * FROM complaints LIMIT 5");
echo "<pre>"; print_r($q->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";

echo "<h2>Joined Data (What the admin page sees)</h2>";
$query = "SELECT c.complaint_id, c.user_id, u.user_id AS join_uid, u.full_name AS u_fullname, u.username
          FROM complaints c 
          LEFT JOIN users u ON c.user_id = u.user_id
          LIMIT 5";
$test = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>"; print_r($test); echo "</pre>";
?>
