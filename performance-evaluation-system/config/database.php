<?php
// Database configuration
$host = '127.0.0.1'; // Update with your database host
$dbname = 'performance_evaluation'; // Corrected database name
$username = 'root'; // Update with your database username
$password = ''; // Update with your database password

try {
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check if the database exists
    $pdo->exec("USE $dbname");
} catch (PDOException $e) {
    // Log the error with detailed information
    error_log('Database connection failed: ' . $e->getMessage());

    // Display a user-friendly error message
    die('Database connection failed. Please ensure the database "performance_evaluation" exists.');
}
