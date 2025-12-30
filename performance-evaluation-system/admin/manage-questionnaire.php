<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

// Handle adding a new questionnaire
if (isset($_POST['add_questionnaire'])) {
    $stmt = $pdo->prepare("INSERT INTO questionnaires (title, description) VALUES (?, ?)");
    $stmt->execute([
        trim($_POST['title']),
        trim($_POST['description'])
    ]);
    header("Location: manage-questionnaire.php?msg=added");
    exit();
}

// Handle deleting a questionnaire
if (isset($_POST['delete_questionnaire'])) {
    $stmt = $pdo->prepare("DELETE FROM questionnaires WHERE id = ?");
    $stmt->execute([$_POST['questionnaire_id']]);
    header("Location: manage-questionnaire.php?msg=deleted");
    exit();
}

// Fetch all questionnaires
$questionnaires = $pdo->query("SELECT * FROM questionnaires ORDER BY id DESC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Questionnaire Management</h1>
            <p class="page-subtitle">Create and manage evaluation forms for student feedback.</p>
        </div>
    </header>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            <?php 
                if($_GET['msg'] == 'added') echo "✅ Questionnaire created successfully!";
                if($_GET['msg'] == 'deleted') echo "🗑️ Questionnaire removed successfully!";
            ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Create New Form</h3>
                <form method="post" class="admin-form">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Form Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. End of Semester Faculty Review" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Description/Instructions</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Briefly describe the purpose of this evaluation..." required></textarea>
                    </div>

                    <button type="submit" name="add_questionnaire" class="btn-publish" style="width: 100%; border:none; cursor:pointer;">
                        Save Questionnaire
                    </button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Available Questionnaires</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="10%">ID</th>
                            <th width="30%">Title</th>
                            <th width="40%">Description</th>
                            <th width="20%" style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($questionnaires): ?>
                            <?php foreach ($questionnaires as $q): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($q['id']) ?></td>
                                <td><strong style="color:#0f172a;"><?= htmlspecialchars($q['title']) ?></strong></td>
                                <td style="font-size: 0.85rem; color: #64748b; line-height: 1.4;">
                                    <?= htmlspecialchars($q['description']) ?>
                                </td>
                                <td style="text-align:right;">
                                    <form method="post" onsubmit="return confirm('Permanently delete this questionnaire?');" style="display:inline;">
                                        <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                                        <button type="submit" name="delete_questionnaire" 
                                                style="background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer; font-size:0.85rem;">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color:#94a3b8;">No questionnaires found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>