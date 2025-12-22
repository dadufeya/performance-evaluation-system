<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';

// Handle adding a new questionnaire
if (isset($_POST['add_questionnaire'])) {
    $stmt = $pdo->prepare("INSERT INTO questionnaires (title, description) VALUES (?, ?)");
    $stmt->execute([
        $_POST['title'],
        $_POST['description']
    ]);
    header("Location: manage-questionnaire.php?success=1");
    exit();
}

// Handle deleting a questionnaire
if (isset($_POST['delete_questionnaire'])) {
    $stmt = $pdo->prepare("DELETE FROM questionnaires WHERE id = ?");
    $stmt->execute([$_POST['questionnaire_id']]);
    header("Location: manage-questionnaire.php?deleted=1");
    exit();
}

// Fetch all questionnaires
$questionnaires = $pdo->query("SELECT * FROM questionnaires")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main class="content" style="margin-top: 60px;">
    <header class="page-header">
        <h3>Manage Questionnaires</h3>
    </header>

    <section class="form-section">
        <?php if (isset($_GET['success'])): ?>
            <p class="success-message">Questionnaire added successfully!</p>
        <?php elseif (isset($_GET['deleted'])): ?>
            <p class="success-message">Questionnaire deleted successfully!</p>
        <?php endif; ?>

        <form method="post" class="questionnaire-form">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="Enter questionnaire title" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Enter questionnaire description" required></textarea>

            <button type="submit" name="add_questionnaire" class="btn-submit">Add Questionnaire</button>
        </form>
    </section>

    <section class="table-section">
        <h4>Questionnaire List</h4>
        <table class="questionnaire-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questionnaires as $q): ?>
                    <tr>
                        <td><?= htmlspecialchars($q['id']) ?></td>
                        <td><?= htmlspecialchars($q['title']) ?></td>
                        <td><?= htmlspecialchars($q['description']) ?></td>
                        <td>
                            <form method="post" class="delete-form">
                                <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                                <button type="submit" name="delete_questionnaire" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<style>
    .content {
        padding: 20px;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .page-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .form-section {
        max-width: 600px;
        margin: 0 auto 40px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .success-message {
        color: #28a745;
        text-align: center;
        margin-bottom: 15px;
    }

    .questionnaire-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .questionnaire-form label {
        font-weight: bold;
    }

    .questionnaire-form input,
    .questionnaire-form textarea {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
    }

    .btn-submit {
        padding: 10px 15px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        transition: background 0.3s;
    }

    .btn-submit:hover {
        background-color: #0056b3;
    }

    .table-section {
        max-width: 800px;
        margin: 0 auto;
    }

    .questionnaire-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .questionnaire-table th,
    .questionnaire-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .questionnaire-table th {
        background-color: #007bff;
        color: #fff;
    }

    .questionnaire-table tr:hover {
        background-color: #f1f1f1;
    }

    .delete-form {
        display: inline;
    }

    .btn-delete {
        padding: 5px 10px;
        background-color: #dc3545;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.3s;
    }

    .btn-delete:hover {
        background-color: #c82333;
    }
</style>

<?php require_once '../includes/footer.php'; ?>