<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure constants are loaded to get BASE_URL
if (file_exists(__DIR__ . '/../config/constants.php')) {
    require_once __DIR__ . '/../config/constants.php';
} 

// Fallback: If BASE_URL failed to load, define it manually to prevent 404s
if (!defined('BASE_URL')) {
    define('BASE_URL', '/performance-evaluation-system/');
}

/**
 * Function to check if the user has the required role
 * Usage: checkAccess('admin');
 */
function checkAccess($requiredRole) {
    // Check both common session naming conventions
    $currentRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
    
    if (!isset($_SESSION['user_id']) || $currentRole !== $requiredRole) {
        header('Location: ' . BASE_URL . 'login.php?error=unauthorized');
        exit();
    }
}

// Global protection: Redirect to login if user is not authenticated at all
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php?error=login_required');
    exit();
}