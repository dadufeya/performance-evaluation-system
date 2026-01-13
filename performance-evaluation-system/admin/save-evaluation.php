<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO evaluation_assignments 
            (questionnaire_id, department_id, year, section, course_id, teacher_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute([
            $_POST['questionnaire_id'],
            $_POST['department_id'],
            $_POST['year'],
            $_POST['section'],
            $_POST['course_id'],
            $_POST['teacher_id']
        ]);

        header("Location: create-questionnaire.php?msg=Evaluation Published Successfully");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}