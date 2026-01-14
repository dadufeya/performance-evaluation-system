<?php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Fetch the logged-in student's ID
$student_id = $_SESSION['user_id'];

try {
    // Fetch the list of teachers for the logged-in student
    $sql = "
        SELECT DISTINCT 
            t.teacher_id, 
            t.full_name, 
            c.course_name, 
            t.course_id,
            (SELECT assignment_id FROM evaluation_assignments 
             WHERE teacher_id = t.teacher_id 
               AND course_id = t.course_id 
               AND (section = s.section_id OR section = (SELECT section_number FROM sections WHERE section_id = s.section_id))
               AND status = 'active' LIMIT 1) as active_eval
        FROM teachers t
        JOIN courses c ON t.course_id = c.course_id
        JOIN students s ON s.section_id = t.section_id
        JOIN student_courses sc ON sc.student_id = s.student_id AND sc.course_id = t.course_id
        WHERE s.user_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// --- VIEW STARTS HERE ---
require_once '../includes/student-header.php';
require_once '../includes/sidebar-student.php';
?>

<main class="main-content">
    <div style="margin-bottom: 30px;">
        <h3 style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin: 0;">My Teachers</h3>
        <p style="color: #64748b; margin-top: 5px;">Below are the teachers assigned to your current courses and section.</p>
    </div>

    <div style="display: grid; gap: 20px;">
        <?php if (empty($teachers)): ?>
            <div style="background: white; padding: 40px; border-radius: 15px; text-align: center; border: 1px solid #e2e8f0;">
                <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                <p style="color: #64748b; font-size: 1.1rem; margin: 0;">No teachers found for your current enrollment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($teachers as $t): ?>
                <div style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div>
                        <div style="font-weight: 800; font-size: 1.2rem; color: #1e293b; margin-bottom: 5px;">
                            <?= htmlspecialchars($t['full_name']); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <div style="color: #64748b; font-size: 0.95rem;">
                                <i class="fas fa-book-open" style="color: #6366f1; margin-right: 5px;"></i>
                                Course: <span style="font-weight: 600; color: #334155;"><?= htmlspecialchars($t['course_name']); ?></span>
                            </div>
                            <div style="font-size: 0.85rem; display: flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 50px; background: <?= $t['active_eval'] ? '#f0fdf4' : '#f8fafc' ?>; color: <?= $t['active_eval'] ? '#166534' : '#64748b' ?>; border: 1px solid <?= $t['active_eval'] ? '#bbf7d0' : '#e2e8f0' ?>;">
                                <i class="fas fa-circle" style="font-size: 6px;"></i>
                                <?= $t['active_eval'] ? 'Evaluation Active' : 'No Active Evaluation' ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="flex-shrink: 0;">
                        <?php if($t['active_eval']): ?>
                            <a href="evaluate-teacher.php?teacher_id=<?= htmlspecialchars($t['teacher_id']); ?>&course_id=<?= $t['course_id']; ?>" 
                               style="background: #6366f1; color: white; padding: 12px 25px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2);">
                                <span>Evaluate Now</span>
                                <i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i>
                            </a>
                        <?php else: ?>
                            <button disabled style="background: #f1f5f9; color: #94a3b8; padding: 12px 25px; border-radius: 10px; border: none; font-weight: 600; font-size: 0.9rem; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-clock"></i>
                                <span>Pending Release</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>