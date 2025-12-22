<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';


if (isset($_POST['add'])) {
$stmt = $pdo->prepare("INSERT INTO courses (course_name, department_id) VALUES (?, ?)");
$stmt->execute([$_POST['course_name'], $_POST['department_id']]);
}


$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$courses = $pdo->query("SELECT c.*, d.department_name FROM courses c JOIN departments d ON c.department_id=d.department_id")->fetchAll();
?>
<main class="content" style="margin-top: 60px;">
    <header class="page-header">
        <h3>Manage Courses</h3>
    </header>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
    <section class="form-section">
        <form method="post" class="course-form">
            <label for="department_id">Select Department</label>
            <select id="department_id" name="department_id" required>
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="course_name">Course Name</label>
            <input type="text" id="course_name" name="course_name" placeholder="Enter course name" required>

            <button type="submit" name="add" class="btn-submit">Add Course</button>
        </form>
    </section>

    <section class="table-section">
        <h4>Course List</h4>
        <table class="course-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Department</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['course_name']) ?></td>
                        <td><?= htmlspecialchars($c['department_name']) ?></td>
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

    .course-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .course-form label {
        font-weight: bold;
    }

    .course-form input,
    .course-form select {
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

    .course-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .course-table th,
    .course-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .course-table th {
        background-color: #007bff;
        color: #fff;
    }

    .course-table tr:hover {
        background-color: #f1f1f1;
    }
</style>
<?php require_once '../includes/footer.php'; ?>