<?php
require_once '../config/config.php';
require_once '../models/Evaluation.php';


$evaluation = new Evaluation($pdo);
if ($_SERVER['REQUEST_METHOD']==='POST'){
$evaluation->submit($_SESSION['user_id'], $_POST['teacher_id'], $_POST['answers']);
header('Location: ../student/evaluation-history.php');
exit();
}