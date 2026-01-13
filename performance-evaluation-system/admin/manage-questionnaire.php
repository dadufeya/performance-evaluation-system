<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$questions = $pdo->query("
    SELECT * FROM evaluation_questions 
    ORDER BY created_at DESC
")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
.main-content { margin-left: 260px; padding: 25px; background: #f1f5f9; min-height: 100vh; font-family: sans-serif; }
.card { background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
table { width: 100%; border-collapse: collapse; }
th { background:#f8fafc; text-align: left; font-size:12px; color:#64748b; border-bottom:1px solid #e2e8f0; padding:12px 15px; }
td { padding:12px 15px; border-bottom: 1px solid #f1f5f9; font-size:13px; }
.btn { padding: 5px 10px; border-radius: 4px; font-size:11px; font-weight:bold; border:none; cursor:pointer; }
.btn-blue { background: #2563eb; color: white; }
.btn-red { background: #ef4444; color: white; }
</style>

<main class="main-content">
    <h2 style="margin-top:0;">Manage Evaluation Questions</h2>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Weight</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($questions) == 0): ?>
                    <tr><td colspan="6" style="text-align:center;">No questions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?= $q['question_id'] ?></td>
                        <td><?= htmlspecialchars($q['category']) ?></td>
                        <td><?= htmlspecialchars($q['question_text']) ?></td>
                        <td><?= strtoupper($q['question_type']) ?></td>
                        <td><?= $q['weight'] ?></td>
                        <td style="text-align:right;">
                            <a href="edit-question.php?id=<?= $q['question_id'] ?>" class="btn btn-blue">Edit</a>
                            <a href="delete-question.php?id=<?= $q['question_id'] ?>" class="btn btn-red" onclick="return confirm('Delete this question permanently?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
