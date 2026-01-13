<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

if (!isset($_GET['category'])) {
    die("Invalid questionnaire.");
}

$category = $_GET['category'];

// Fetch questions
$stmt = $pdo->prepare("
    SELECT question_text, question_type, weight 
    FROM evaluation_questions 
    WHERE category = ?
    ORDER BY question_id ASC
");
$stmt->execute([$category]);
$questions = $stmt->fetchAll();
?>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar-admin.php'; ?>

<style>
.admin-container { margin-left:260px; padding:30px; background:#f1f5f9; min-height:100vh; }
.card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { padding:12px; border-bottom:1px solid #e5e7eb; }
th { background:#f8fafc; }
</style>

<div class="admin-container">
    <div class="card">
        <h2>📋 Questionnaire: <strong><?= htmlspecialchars($category) ?></strong></h2>

        <?php if (empty($questions)): ?>
            <p>No questions found.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Weight</th>
                </tr>
                <?php foreach ($questions as $i => $q): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($q['question_text']) ?></td>
                    <td><?= strtoupper($q['question_type']) ?></td>
                    <td><?= $q['weight'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <br>
        <a href="create-questionnaire.php" class="btn btn-blue">⬅ Back</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
