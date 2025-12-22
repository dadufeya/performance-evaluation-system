<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $stmt = $pdo->prepare("UPDATE complaints SET feedback = ?, teacher_id = ? WHERE complaint_id = ?");
    $stmt->execute([$_POST['feedback'], $_POST['teacher_id'], $_POST['complaint_id']]);
}

// Fetch complaints
$complaints = $pdo->query("SELECT * FROM complaints")->fetchAll();

// Fetch teachers
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main class="content" style="margin-top: 60px;">
    <header class="page-header">
        <h3>Manage Complaints</h3>
    </header>

    <section class="complaints-section">
        <h4>Complaints List</h4>
        <table class="complaints-table">
            <thead>
                <tr>
                    <th>Complaint ID</th>
                    <th>Complaint</th>
                    <th>Feedback</th>
                    <th>Teacher</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?= htmlspecialchars($complaint['complaint_id']) ?></td>
                        <td><?= htmlspecialchars($complaint['complaint_text']) ?></td>
                        <td><?= htmlspecialchars($complaint['feedback']) ?></td>
                        <td>
                            <?php
                            if (!empty($complaint['teacher_id'])) {
                                $teacher = $pdo->prepare("SELECT full_name FROM teachers WHERE teacher_id = ?");
                                $teacher->execute([$complaint['teacher_id']]);
                                echo htmlspecialchars($teacher->fetchColumn());
                            } else {
                                echo "N/A";
                            }
                            ?>
                        </td>
                        <td>
                            <form method="post" class="feedback-form">
                                <input type="hidden" name="complaint_id" value="<?= $complaint['complaint_id'] ?>">
                                <input type="text" name="feedback" placeholder="Enter feedback" required>
                                <select name="teacher_id" required>
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= $teacher['teacher_id'] ?>">
                                            <?= htmlspecialchars($teacher['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="submit_feedback" class="btn-submit">Submit</button>
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

    .complaints-section {
        max-width: 800px;
        margin: 0 auto;
    }

    .complaints-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .complaints-table th,
    .complaints-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .complaints-table th {
        background-color: #007bff;
        color: #fff;
    }

    .complaints-table tr:hover {
        background-color: #f1f1f1;
    }

    .feedback-form {
        display: flex;
        gap: 10px;
    }

    .feedback-form input,
    .feedback-form select {
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 0.9rem;
    }

    .btn-submit {
        padding: 5px 10px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.3s;
    }

    .btn-submit:hover {
        background-color: #0056b3;
    }
</style>

<?php require_once '../includes/footer.php'; ?>