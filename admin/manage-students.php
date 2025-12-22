<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


if (isset($_POST['add'])) {
$stmt = $pdo->prepare("INSERT INTO students (full_name, year_id, section_id) VALUES (?,?,?)");
$stmt->execute([$_POST['full_name'], $_POST['year_id'], $_POST['section_id']]);
}
$years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
$sections = $pdo->query("SELECT * FROM sections")->fetchAll();
$students = $pdo->query("SELECT s.*, y.year_name, sec.section_number FROM students s JOIN academic_years y ON s.year_id=y.year_id JOIN sections sec ON s.section_id=sec.section_id")->fetchAll();
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main class="content" style="margin-top: 60px;">
    <header class="page-header">
        <h3>Manage Students</h3>
    </header>

    <section class="form-section">
        <form method="post" class="student-form">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" placeholder="Enter full name" required>

            <label for="year_id">Select Year</label>
            <select id="year_id" name="year_id" required>
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y['year_id'] ?>"><?= $y['year_name'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="section_id">Select Section</label>
            <select id="section_id" name="section_id" required>
                <option value="">-- Select Section --</option>
                <?php foreach ($sections as $s): ?>
                    <option value="<?= $s['section_id'] ?>">Section <?= $s['section_number'] ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="add" class="btn-submit">Add Student</button>
        </form>
    </section>

    <section class="table-section">
        <h4>Student List</h4>
        <table class="student-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Year</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><?= htmlspecialchars($s['year_name']) ?></td>
                        <td><?= htmlspecialchars($s['section_number']) ?></td>
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

    .student-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .student-form label {
        font-weight: bold;
    }

    .student-form input,
    .student-form select {
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

    .student-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .student-table th,
    .student-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .student-table th {
        background-color: #007bff;
        color: #fff;
    }

    .student-table tr:hover {
        background-color: #f1f1f1;
    }
</style>
<?php require_once '../includes/footer.php'; ?>