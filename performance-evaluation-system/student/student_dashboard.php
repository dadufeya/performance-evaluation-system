<?php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php'; // Ensure the user is authenticated
require_once __DIR__ . '/../includes/sidebar-student.php'; // Include the sidebar
require_once __DIR__ . '/../includes/student-header.php'; // Include the header
require_once __DIR__ . '/../config/database.php'; // Ensure this path is correct

// Fetch student-specific data
$student_id = $_SESSION['user_id'] ?? null;
$student_name = $_SESSION['username'] ?? 'Student';

// Initialize variables
$total_teachers = 0;
$completed_evaluations = 0;
$pending_evaluations = 0;

if ($student_id) {
    try {
        // Fetch total teachers
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $total_teachers = $stmt->fetchColumn();

        // Fetch completed evaluations
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM evaluations WHERE student_id = ? AND status = 'completed'");
        $stmt->execute([$student_id]);
        $completed_evaluations = $stmt->fetchColumn();

        // Calculate pending evaluations
        $pending_evaluations = $total_teachers - $completed_evaluations;
    } catch (PDOException $e) {
        error_log('Error fetching student dashboard data: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | PES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content {
            margin-left: 260px; /* Space for the sidebar */
            padding: 100px 30px 30px 30px; /* Space for the fixed header */
        }

        .welcome-card {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .bg-blue { background: #eff6ff; color: #3b82f6; }
        .bg-green { background: #f0fdf4; color: #22c55e; }
    </style>
</head>
<body>
    <main class="main-content">
        <div class="welcome-card">
            <h1>Welcome back, <?php echo htmlspecialchars($student_name); ?>!</h1>
            <p>You have <?php echo $pending_evaluations; ?> teachers pending for evaluation. Let's get started!</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <h3 style="margin:0;"><?php echo $total_teachers; ?></h3>
                    <p style="margin:0; color:#64748b; font-size:0.9rem;">Total Teachers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-check-double"></i></div>
                <div>
                    <h3 style="margin:0;"><?php echo $completed_evaluations; ?></h3>
                    <p style="margin:0; color:#64748b; font-size:0.9rem;">Completed Evals</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>