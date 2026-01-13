<?php
require_once '../config/config.php';

$course_id = $_GET['course_id'];

// Get course name first since your teacher table likely uses the name string
$stmtC = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
$stmtC->execute([$course_id]);
$course_name = $stmtC->fetchColumn();

// Find teachers where course_info matches the course name
$stmtT = $pdo->prepare("SELECT teacher_id, full_name FROM teachers WHERE course_info = ?");
$stmtT->execute([$course_name]);
$teachers = $stmtT->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($teachers);