<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$msg = "";
$error = "";
$editCategory = $_GET['edit'] ?? null; // Currently editing category

/* -------------------------------
   FETCH EXISTING CATEGORIES
-------------------------------- */
$categories = $pdo->query("
    SELECT DISTINCT category 
    FROM evaluation_questions 
    ORDER BY category ASC
")->fetchAll(PDO::FETCH_COLUMN);

/* -------------------------------
   FETCH ALL QUESTIONNAIRES
-------------------------------- */
$questionnaires = $pdo->query("
    SELECT 
        category,
        COUNT(*) AS total_questions,
        MAX(created_at) AS created_at
    FROM evaluation_questions
    GROUP BY category
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
   FETCH QUESTIONS IF EDITING
-------------------------------- */
$editQuestions = [];
if($editCategory) {
    $stmt = $pdo->prepare("SELECT * FROM evaluation_questions WHERE category=:category ORDER BY created_at ASC");
    $stmt->execute(['category'=>$editCategory]);
    $editQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* -------------------------------
   SHOW SUCCESS MESSAGE
-------------------------------- */
if (isset($_GET['created'])) {
    $msg = "✅ Questionnaire saved successfully!";
}
?>

<?php
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
.admin-container {
    margin-left:260px;
    padding:30px;
    background:#f1f5f9;
    min-height:100vh;
    font-family:'Segoe UI', sans-serif;
}
.card {
    background:#fff;
    border-radius:12px;
    padding:25px;
    margin-bottom:25px;
    box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);
}
.btn {
    padding:10px 18px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    font-weight:bold;
    font-size:12px;
}
.btn-blue { background:#2563eb; color:#fff; }
.btn-green { background:#10b981; color:#fff; }
.btn-yellow { background:#f59e0b; color:#fff; }
.btn-sm { padding:6px 12px; font-size:11px; }

input, select, textarea {
    padding:12px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    width:100%;
    margin-bottom:10px;
}
textarea { resize:vertical; min-height:60px; }

.question-box {
    border:1px solid #e2e8f0;
    padding:15px;
    margin-bottom:15px;
    border-radius:8px;
    background:#f9fafb;
    position:relative;
}
.remove-btn {
    color:#ef4444;
    cursor:pointer;
    position:absolute;
    top:10px;
    right:10px;
    font-size:14px;
}

table {
    width:100%;
    border-collapse:collapse;
}
th, td {
    padding:12px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
}
th {
    background:#f8fafc;
    font-size:13px;
}

.back-btn {
    display:inline-block;
    margin-bottom:15px;
    padding:8px 14px;
    background:#6b7280;
    color:#fff;
    border-radius:8px;
    text-decoration:none;
}
</style>

<div class="admin-container">

    <h2 style="font-weight:800;">Evaluation Questionnaire Management</h2>

    <?php if($msg): ?>
        <div style="background:#dcfce7;color:#166534;padding:15px;border-radius:8px;margin:20px 0;">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <!-- ================= CREATE / EDIT QUESTIONNAIRE ================= -->
    <div class="card">
        <h3><?= $editCategory ? "Edit Questionnaire: $editCategory" : "Create New Questionnaire" ?></h3>

        <?php if($editCategory): ?>
            <a href="create-evaluation.php" class="back-btn">⬅ Back to Create New</a>
        <?php endif; ?>

        <form method="POST" action="save-evaluation.php" enctype="multipart/form-data">
            <label>Question Category</label>
            <input type="text"
                   name="category"
                   value="<?= htmlspecialchars($editCategory ?? '') ?>"
                   list="category-list"
                   required
                   placeholder="e.g Teaching Performance"
                   <?= $editCategory ? 'readonly' : '' ?>>

            <datalist id="category-list">
                <?php foreach($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
            </datalist>

            <div id="questions-container">
                <?php
                if($editQuestions):
                    foreach($editQuestions as $q):
                        $qid = $q['question_id'];
                        $text = $q['question_text'] ?? '';
                        $type = $q['question_type'] ?? '';
                        $weight = $q['weight'] ?? 1;
                ?>
                <div class="question-box" id="question-<?= $qid ?>">
                    <label>Question Text</label>
                    <textarea name="questions[<?= $qid ?>][text]" required><?= htmlspecialchars($text) ?></textarea>

                    <label>Question Type</label>
                    <select name="questions[<?= $qid ?>][type]" required>
                        <option value="">-- Select --</option>
                        <option value="scale" <?= $type==='scale'?'selected':'' ?>>Scale (1–5)</option>
                        <option value="boolean" <?= $type==='boolean'?'selected':'' ?>>Yes / No</option>
                        <option value="text" <?= $type==='text'?'selected':'' ?>>Text</option>
                    </select>

                    <label>Weight</label>
                    <input type="number" name="questions[<?= $qid ?>][weight]" value="<?= htmlspecialchars($weight) ?>" min="1">

                    <span class="remove-btn" onclick="deleteQuestion(<?= $qid ?>)">❌ Remove</span>
                </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>

            <?php if(!$editCategory): // Only show add new / CSV if NOT editing ?>
                <button type="button"
                        onclick="addQuestion()"
                        class="btn btn-green">
                    ➕ Add Question
                </button>

                <hr>

                <label>Import Questions (CSV)</label>
                <input type="file" name="csv_file" accept=".csv">
                <small>Format: question_text,type,weight</small>
            <?php endif; ?>

            <br><br>
            <button type="submit" class="btn btn-blue">
                💾 <?= $editCategory ? "Update Questionnaire" : "Save Questionnaire" ?>
            </button>
        </form>
    </div>

    <?php if(!$editCategory): // Only show list if NOT editing ?>
    <!-- ================= LIST QUESTIONNAIRES ================= -->
    <div class="card">
        <h3>Previously Created Questionnaires</h3>

        <?php if(empty($questionnaires)): ?>
            <p>No questionnaires created yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Category</th>
                    <th>Total Questions</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>

                <?php foreach($questionnaires as $q): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($q['category']) ?></strong></td>
                    <td><?= $q['total_questions'] ?></td>
                    <td><?= date("d M Y", strtotime($q['created_at'])) ?></td>
                    <td>
                        <a href="?edit=<?= urlencode($q['category']) ?>" class="btn btn-sm btn-yellow">✏ Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<script>
let questionIndex = 0;

// Add new question dynamically
function addQuestion() {
    const div = document.createElement("div");
    div.className = "question-box";

    div.innerHTML = `
        <label>Question Text</label>
        <textarea name="questions[new_${questionIndex}][text]" required></textarea>

        <label>Question Type</label>
        <select name="questions[new_${questionIndex}][type]" required>
            <option value="">-- Select --</option>
            <option value="scale">Scale (1–5)</option>
            <option value="boolean">Yes / No</option>
            <option value="text">Text</option>
        </select>

        <label>Weight</label>
        <input type="number" name="questions[new_${questionIndex}][weight]" value="1" min="1">

        <span class="remove-btn" onclick="this.parentElement.remove()">❌ Remove</span>
    `;

    document.getElementById("questions-container").appendChild(div);
    questionIndex++;
}

// Remove existing question (mark for deletion)
function deleteQuestion(qid) {
    if(confirm("Are you sure you want to remove this question?")) {
        const q = document.getElementById("question-" + qid);
        if(q) q.remove();

        // Add hidden input to mark for deletion
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "deleted_questions[]";
        input.value = qid;
        document.querySelector("form").appendChild(input);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
