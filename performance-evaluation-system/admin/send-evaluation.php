<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('admin');

if (!isset($_GET['teacher_id'])) {
    header("Location: manage-teachers.php");
    exit();
}

$teacher_id = $_GET['teacher_id'];
$course_id = $_GET['course_id'] ?? null;
$section_id = $_GET['section_id'] ?? null;

// Get teacher information for a specific assignment
$sql = "SELECT t.*, d.department_name, y.year_name, s.section_number, c.course_name 
        FROM teachers t 
        JOIN departments d ON t.department_id = d.department_id 
        LEFT JOIN academic_years y ON t.year_id = y.year_id
        LEFT JOIN sections s ON t.section_id = s.section_id
        LEFT JOIN courses c ON t.course_id = c.course_id
        WHERE t.teacher_id = ?";
$params = [$teacher_id];

if ($course_id && $section_id) {
    $sql .= " AND t.course_id = ? AND t.section_id = ?";
    $params[] = $course_id;
    $params[] = $section_id;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teacher = $stmt->fetch();

if (!$teacher) {
    die("Assignment not found for this teacher.");
}

// Fetch available questionnaires
$questionnaires = $pdo->query("SELECT * FROM questionnaires WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();

// Fetch students who will receive this evaluation (Confirmation List)
$target_students = [];
try {
    $stmtS = $pdo->prepare("
        SELECT s.full_name, s.student_id_card 
        FROM students s
        JOIN student_courses sc ON s.student_id = sc.student_id
        WHERE s.section_id = ? AND sc.course_id = ?
        ORDER BY s.full_name ASC
    ");
    $stmtS->execute([$teacher['section_id'], $teacher['course_id']]);
    $target_students = $stmtS->fetchAll();
} catch (PDOException $e) { $error = "Student lookup failed: " . $e->getMessage(); }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionnaire_id = $_POST['questionnaire_id'];
    
    try {
        // Insert into evaluation_assignments using IDs
        $stmt = $pdo->prepare("INSERT INTO evaluation_assignments (questionnaire_id, department_id, year, section, course_id, teacher_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([
            $questionnaire_id,
            $teacher['department_id'],
            $teacher['year_id'],
            $teacher['section_id'],
            $teacher['course_id'],
            $teacher_id
        ]);
        
        $msg = "Evaluation assigned successfully to " . $teacher['full_name'] . " for " . $teacher['course_name'] . " (" . $teacher['year_name'] . " - " . $teacher['section_number'] . ")";
        header("Location: manage-teachers.php?msg=" . urlencode($msg));
        exit();
    } catch (PDOException $e) {
        $error = "Assignment failed: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
.admin-container { margin-left: 260px; padding: 30px; background: #f1f5f9; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
.card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
.btn { padding: 12px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-primary:hover { background: #1d4ed8; }
.input-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 10px; margin-bottom: 20px; box-sizing: border-box; }
</style>

<div class="admin-container">
    <h2 style="text-align:center; font-weight:800; margin-bottom: 30px;">Assign Evaluation</h2>
    
    <div class="card">
        <h3>Target Teacher</h3>
        <p>
            <strong>Name:</strong> <?= htmlspecialchars($teacher['full_name']) ?><br>
            <strong>Department:</strong> <?= htmlspecialchars($teacher['department_name']) ?><br>
            <strong>Course:</strong> <?= htmlspecialchars($teacher['course_name'] ?? $teacher['course_info']) ?><br>
            <strong>Target:</strong> <?= htmlspecialchars($teacher['year_name']) ?>, Section <?= htmlspecialchars($teacher['section_number']) ?>
        </p>
        <hr style="border:0; border-top:1px solid #e2e8f0; margin: 20px 0;">

        <div style="margin-bottom: 25px;">
            <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:10px;">Recipient Students (<?= count($target_students) ?>)</label>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:15px; max-height:200px; overflow-y:auto;">
                <?php if(count($target_students) > 0): ?>
                    <table style="width:100%; font-size:12px; border-collapse:collapse;">
                        <?php foreach($target_students as $std): ?>
                            <tr>
                                <td style="padding:5px 0; border-bottom:1px solid #f1f5f9; color:#334155;"><?= htmlspecialchars($std['full_name']) ?></td>
                                <td style="padding:5px 0; border-bottom:1px solid #f1f5f9; color:#64748b; text-align:right;">ID: <?= htmlspecialchars($std['student_id_card']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p style="color:#ef4444; font-size:12px; margin:0;">⚠️ No students found for this assignment!</p>
                <?php endif; ?>
            </div>
            <p style="font-size:11px; color:#94a3b8; margin-top:8px;">Please confirm the list above before sending the evaluation questionnaire.</p>
        </div>

        <hr style="border:0; border-top:1px solid #e2e8f0; margin: 20px 0;">

        <?php if(isset($error)): ?><div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px;">❌ <?= $error ?></div><?php endif; ?>

        <form method="POST">
            <label><b>Select Questionnaire</b></label>
            <select name="questionnaire_id" class="input-control" required>
                <option value="">-- Choose Questionnaire --</option>
                <?php foreach($questionnaires as $q): ?>
                    <option value="<?= $q['questionnaire_id'] ?>"><?= htmlspecialchars($q['title']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Dispatch to Students</button>
            <a href="manage-teachers.php" style="display:block; text-align:center; margin-top:15px; color:#64748b; text-decoration:none; font-size:14px;">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
