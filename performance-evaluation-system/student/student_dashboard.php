<?php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Fetch student-specific data
$student_id = $_SESSION['user_id'] ?? null;
$student_name = $_SESSION['username'] ?? 'Student';

// Initialize variables
$total_teachers = 0;
$completed_evaluations = 0;
$pending_evaluations = 0;

if ($student_id) {
    try {
        // Fetch student's section and course context
        $stmtS = $pdo->prepare("SELECT student_id, section_id FROM students WHERE user_id = ?");
        $stmtS->execute([$student_id]);
        $student = $stmtS->fetch();

        if ($student) {
            $sid = $student['student_id'];
            $sec_id = $student['section_id'];

            // 1. Total Relevant Teachers
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT t.teacher_id) 
                FROM teachers t
                JOIN student_courses sc ON sc.course_id = t.course_id
                WHERE sc.student_id = ? AND t.section_id = ?
            ");
            $stmt->execute([$sid, $sec_id]);
            $total_teachers = $stmt->fetchColumn();

            // 2. Completed Evaluations
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT teacher_id) FROM evaluations WHERE student_id = ?");
            $stmt->execute([$sid]);
            $completed_evaluations = $stmt->fetchColumn();

            // 3. Pending Evaluations
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT ea.assignment_id)
                FROM evaluation_assignments ea
                JOIN student_courses sc ON sc.course_id = ea.course_id
                WHERE sc.student_id = ? 
                AND (ea.section = ? OR ea.section = (SELECT section_number FROM sections WHERE section_id = ?))
                AND ea.status = 'active'
                AND ea.teacher_id NOT IN (SELECT teacher_id FROM evaluations WHERE student_id = ?)
            ");
            $stmt->execute([$sid, $sec_id, $sec_id, $sid]);
            $pending_evaluations = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log('Error fetching student dashboard data: ' . $e->getMessage());
    }
}

// --- VIEW STARTS HERE ---
require_once __DIR__ . '/../includes/student-header.php';
require_once __DIR__ . '/../includes/sidebar-student.php';
?>

<style>
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

<?php require_once '../includes/footer.php'; ?>