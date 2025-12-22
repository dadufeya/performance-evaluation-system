<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


if (isset($_POST['add'])) {
$pdo->prepare("INSERT INTO sections (year_id, section_number) VALUES (?,?)")
->execute([$_POST['year_id'], $_POST['section_number']]);
}
$years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
$sections = $pdo->query("SELECT s.*, y.year_name FROM sections s JOIN academic_years y ON s.year_id=y.year_id")->fetchAll();
?>

<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<main class="content" style="margin-top: 60px;">
    <header class="page-header">
        <h3>Manage Sections</h3>
    </header>

    <section class="form-section">
        <form method="post" class="section-form">
            <label for="year_id">Select Year</label>
            <select id="year_id" name="year_id" required>
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y['year_id'] ?>"><?= htmlspecialchars($y['year_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="section_number">Section Number</label>
            <input type="number" id="section_number" name="section_number" placeholder="Enter section number" required>

            <button type="submit" name="add" class="btn-submit">Add Section</button>
        </form>
    </section>

    <section class="table-section">
        <h4>Section List</h4>
        <table class="section-table">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $s): ?>
                    <tr>
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

    .section-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .section-form label {
        font-weight: bold;
    }

    .section-form input,
    .section-form select {
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

    .section-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .section-table th,
    .section-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .section-table th {
        background-color: #007bff;
        color: #fff;
    }

    .section-table tr:hover {
        background-color: #f1f1f1;
    }
</style>
<?php require_once '../includes/footer.php'; ?>