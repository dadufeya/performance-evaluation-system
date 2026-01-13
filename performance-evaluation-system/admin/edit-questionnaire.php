<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

if (!isset($_GET['category'])) {
    die("Invalid questionnaire.");
}

$category = $_GET['category'];
$msg = "";

// Update questions
if (isset($_POST['update'])) {
    foreach ($_POST['questions'] as $qid => $q) {
        $stmt = $pdo->prepare("
            UPDATE evaluation_questions 
            SET question_text=?, question_type=?, weight=?
            WHERE question_id=?
        ");
        $stmt->execute([
            $q['text'],
            $q['type'],
            $q['weight'],
            $qid
        ]);
    }
    $msg = "✅ Questionnaire updated successfully!";
}

// Fetch questions
$stmt = $pdo->prepare("
    SELECT * FROM evaluation_questions 
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
.question-box { border:1px solid #e5e7eb; padding:15px; margin-bottom:15px; border-radius:8px; background:#f9fafb; }
input, select, textarea { width:100%; padding:10px; margin-bottom:8px; }
</style>

<div class="admin-container">
    <div class="card">
        <h2>✏ Edit Questionnaire: <strong><?= htmlspecialchars($category) ?></strong></h2>

        <?php if ($msg): ?>
            <div style="background:#dcfce7;padding:12px;border-radius:6px;margin-bottom:15px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($questions as $q): ?>
                <div class="question-box">
                    <label>Question</label>
                    <textarea name="questions[<?= $q['question_id'] ?>][text]" required><?= htmlspecialchars($q['question_text']) ?></textarea>

                    <label>Type</label>
                    <select name="questions[<?= $q['question_id'] ?>][type]" required>
                        <option value="scale" <?= $q['question_type']=='scale'?'selected':'' ?>>Scale (1–5)</option>
                        <option value="boolean" <?= $q['question_type']=='boolean'?'selected':'' ?>>Yes / No</option>
                        <option value="text" <?= $q['question_type']=='text'?'selected':'' ?>>Text</option>
                    </select>

                    <label>Weight</label>
                    <input type="number" name="questions[<?= $q['question_id'] ?>][weight]" value="<?= $q['weight'] ?>" min="1">
                </div>
            <?php endforeach; ?>

            <button type="submit" name="update" class="btn btn-green">💾 Update Questionnaire</button>
            <a href="create-questionnaire.php" class="btn btn-blue">⬅ Back</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
