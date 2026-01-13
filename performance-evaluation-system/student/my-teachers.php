<?php
require_once '../includes/auth.php';
require_once '../config/database.php'; // Ensure the database connection file is included
require_once '../includes/student-header.php'; // Include the student header
require_once '../includes/sidebar-student.php'; // Include the student sidebar

// Fetch the logged-in student's ID
$student_id = $_SESSION['user_id'];

try {
    // Fetch the list of teachers for the logged-in student
    $sql = "
        SELECT DISTINCT t.teacher_id, t.full_name
        FROM teacher_course tc
        JOIN teachers t ON tc.teacher_id = t.teacher_id
        JOIN students s ON s.year_id = tc.year_id AND s.section_id = tc.section_id
        WHERE s.user_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching teachers: ' . $e->getMessage());
    die('<p>Failed to load teachers. Please try again later.</p>');
}
?>

<main class="main-content">
    <h3>My Teachers</h3>
    <ul style="list-style: none; padding: 0;">
        <?php foreach ($teachers as $t): ?>
            <li style="margin-bottom: 10px;">
                <?= htmlspecialchars($t['full_name']); ?>
                <a href="evaluate-teacher.php?teacher_id=<?= htmlspecialchars($t['teacher_id']); ?>" style="margin-left: 10px; color: #3b82f6; text-decoration: none;">Evaluate</a>
            </li>
        <?php endforeach; ?>
    </ul>
</main>
<?php require_once '../includes/footer.php'; ?>