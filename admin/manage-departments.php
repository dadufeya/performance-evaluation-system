<?php
require_once('../config/config.php');
require_once('../includes/auth.php');
checkAccess('admin');

include('../includes/header.php');
include('../includes/sidebar-admin.php');
?>
<head>
    <meta charset="UTF-8">
    <title>Performance Evaluation System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>

<div class="admin-layout">
    <h1 class="page-title">Manage Departments</h1>

    <!-- ADD DEPARTMENT -->
    <div class="card-box">
        <form method="POST" class="form-group">
            <input type="text" name="department_name" placeholder="Department Name" required>
            <button type="submit" name="add_department">Add</button>
        </form>
    </div>

    <!-- DEPARTMENTS TABLE -->
    <div class="card-box table-wrapper">
        <table>
            <tr>
                <th>#</th>
                <th>Department Name</th>
                <th>Actions</th>
            </tr>

            <!-- Example row -->
            <tr>
                <td>1</td>
                <td>Computer Engineering</td>
                <td>
                    <a href="#" class="action-btn btn-edit">Edit</a>
                    <a href="#" class="action-btn btn-delete"
                       onclick="return confirm('Delete this department?')">
                        Delete
                    </a>
                </td>
            </tr>

        </table>
    </div>

</div>

<?php include('../includes/footer.php'); ?>
