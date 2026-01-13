<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * TEACHER SECURITY GATE
 * This ensures only logged-in teachers can access teacher pages.
 */
function checkTeacherAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        // Redirect to login if not authorized
        header('Location: ' . BASE_URL . 'login.php?error=unauthorized');
        exit();
    }
}

/**
 * TEACHER DATA HANDLER
 * Use this to fetch data specific to the logged-in teacher
 */
class TeacherController {
    private $pdo;
    private $teacher_id;

    public function __construct($db) {
        $this->pdo = $db;
        // The username (first name) is stored in the teacher_id column in your teachers table
        $this->teacher_id = $_SESSION['username']; 
    }

    // Get basic stats for the dashboard
    public function getDashboardStats() {
        try {
            // Count total evaluations received by this teacher
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM evaluations WHERE teacher_id = ?");
            $stmt->execute([$this->teacher_id]);
            $count = $stmt->fetchColumn();

            // Calculate average rating
            $stmt2 = $this->pdo->prepare("SELECT AVG(rating) FROM evaluations WHERE teacher_id = ?");
            $stmt2->execute([$this->teacher_id]);
            $avg = number_format($stmt2->fetchColumn(), 1);

            return [
                'total_evals' => $count,
                'avg_rating' => $avg ?: "0.0"
            ];
        } catch (Exception $e) {
            return ['total_evals' => 0, 'avg_rating' => "0.0"];
        }
    }

    // Get the courses assigned to this teacher
    public function getAssignedCourses() {
        $stmt = $this->pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$this->teacher_id]);
        return $stmt->fetchAll();
    }
}

// Initialize the controller for use in the dashboard
$teacherCtrl = new TeacherController($pdo);
?>