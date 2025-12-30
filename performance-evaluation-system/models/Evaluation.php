<?php
class Evaluation {
protected $pdo;
public function __construct($pdo){ $this->pdo = $pdo; }


public function submit($student_id,$teacher_id,$answers){
$stmt = $this->pdo->prepare("INSERT INTO evaluations (student_id, teacher_id, question_id, score) VALUES (?,?,?,?)");
foreach($answers as $qid => $score){
$stmt->execute([$student_id, $teacher_id, $qid, $score]);
}
}


public function getTeacherAverage($teacher_id){
$stmt = $this->pdo->prepare("SELECT AVG(score) as avg_score FROM evaluations WHERE teacher_id=?");
$stmt->execute([$teacher_id]);
return $stmt->fetch();
}
}