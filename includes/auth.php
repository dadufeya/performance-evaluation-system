<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/constants.php';

// Function to check if the user is an admin
function checkAccess($role) {
    if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'], $_SESSION['user_role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}