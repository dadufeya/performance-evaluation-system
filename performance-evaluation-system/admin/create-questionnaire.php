<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

// --- HANDLE ADD QUESTION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $text = trim($_POST['question_text']);
    $type = $_POST['question_type'];
    
    if (!empty($text) && !empty($type)) {
        $stmt = $pdo->prepare("INSERT INTO questions (question_text, question_type) VALUES (?, ?)");
        $stmt->execute([$text, $type]);
        header("Location: manage-questions.php?msg=added");
        exit();
    }
}

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage-questions.php?msg=deleted");
    exit();
}

$questions = $pdo->query("SELECT * FROM questions ORDER BY question_id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Questionnaire Builder</h1>
            <p class="page-subtitle">Configure the criteria used for faculty performance evaluations.</p>
        </div>
    </header>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            <?= $_GET['msg'] == 'added' ? '✅ Question added to the pool.' : '🗑️ Question removed successfully.' ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Create New Question</h3>
                <form method="post" class="admin-form">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Question Type</label>
                        <select name="question_type" class="form-select" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                            <option value="">-- Select Type --</option>
                            <option value="rating">Rating (1-5 Scale)</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="short_answer">Short Answer / Feedback</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Question Text</label>
                        <textarea name="question_text" class="form-control" rows="3" placeholder="e.g. How effective is the instructor in explaining complex topics?" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1; font-family:inherit;"></textarea>
                    </div>

                    <button type="submit" name="add_question" class="btn-publish" style="width: 100%; border:none; cursor:pointer;">
                        Add to Questionnaire
                    </button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Active Question Pool</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="20%">Type</th>
                            <th width="65%">Question</th>
                            <th width="15%" style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($questions): ?>
                            <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            $badgeColor = ($q['question_type'] == 'rating') ? '#e0f2fe; color:#0369a1;' : '#f1f5f9; color:#475569;';
                                        ?>
                                        <span class="badge-pending" style="background:<?= $badgeColor ?> font-size: 0.7rem; text-transform: uppercase;">
                                            <?= str_replace('_', ' ', $q['question_type']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.9rem; color:#0f172a; line-height: 1.4;">
                                        <?= htmlspecialchars($q['question_text']) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="?delete=<?= $q['question_id'] ?>" 
                                           style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.85rem;"
                                           onclick="return confirm('Remove this question from all future evaluations?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 40px; color:#94a3b8;">No questions created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>