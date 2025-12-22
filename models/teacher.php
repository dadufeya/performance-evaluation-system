<?php
class Teacher {
protected $pdo;
public function __construct($pdo){ $this->pdo = $pdo; }


public function allTeachers(){
return $this->pdo->query("SELECT * FROM teachers")->fetchAll();
}
}