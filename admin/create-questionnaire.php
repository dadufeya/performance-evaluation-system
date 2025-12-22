<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


if (isset($_POST['add_question'])) {
$stmt = $pdo->prepare("INSERT INTO questions (question_text, question_type) VALUES (?, ?)");
$stmt->execute([$_POST['question_text'], $_POST['question_type']]);
}
$questions = $pdo->query("SELECT * FROM questions")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main class="content">
    <header class="page-header">
        <h3>Create Questionnaire</h3>
    </header>

    <section class="form-section">
        <form method="post" class="questionnaire-form">
            <label for="question_type">Select Questionnaire Type</label>
            <select id="question_type" name="question_type" required>
                <option value="">-- Select Type --</option>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="short_answer">Short Answer</option>
                <option value="rating">Rating</option>
            </select>

            <label for="question_text">Enter Question</label>
            <input type="text" id="question_text" name="question_text" placeholder="Enter your question here" required>

            <button type="submit" name="add_question" class="btn-submit">Add Question</button>
        </form>
    </section>

    <section class="questions-list">
        <h4>Existing Questions</h4>
        <ul>
            <?php foreach ($questions as $q): ?>
                <li class="question-item">
                    <strong>Type:</strong> <?= htmlspecialchars($q['question_type']) ?> <br>
                    <strong>Question:</strong> <?= htmlspecialchars($q['question_text']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
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

    .questionnaire-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .questionnaire-form label {
        font-weight: bold;
    }

    .questionnaire-form input,
    .questionnaire-form select {
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

    .questions-list {
        max-width: 600px;
        margin: 0 auto;
    }

    .questions-list ul {
        list-style: none;
        padding: 0;
    }

    .question-item {
        background: #fff;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
</style>
<?php require_once '../includes/footer.php'; ?>