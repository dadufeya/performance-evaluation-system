<?php
class Complaint {
protected $pdo;
public function __construct($pdo){ $this->pdo = $pdo; }


public function submit($user_id, $message){
$stmt = $this->pdo->prepare("INSERT INTO complaints (user_id, message) VALUES (?,?)");
$stmt->execute([$user_id, $message]);
}


public function all(){
return $this->pdo->query("SELECT c.*, u.username FROM complaints c JOIN users u ON c.user_id=u.user_id")->fetchAll();
}
}