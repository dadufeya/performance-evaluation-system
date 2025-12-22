<?php
session_start();
require_once 'config/constants.php';


if (!isset($_SESSION['user_role'])) {
header('Location: login.php'); exit();
}


switch ($_SESSION['user_role']) {
case 'admin': header('Location: admin/dashboard.php'); break;
case 'teacher': header('Location: teacher/dashboard.php'); break;
case 'student': header('Location: student/dashboard.php'); break;
}
exit();