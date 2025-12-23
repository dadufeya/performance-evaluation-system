<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM sections WHERE section_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage-sections.php?msg=deleted");
    exit();
}

// --- HANDLE ADD ---
if (isset($_POST['add'])) {
    $year_id = $_POST['year_id'];
    $section_number = $_POST['section_number'];
    
    $stmt = $pdo->prepare("INSERT INTO sections (year_id, section_number) VALUES (?,?)");
    $stmt->execute([$year_id, $section_number]);
    header("Location: manage-sections.php?msg=added");
    exit();
}

$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll();
$sections = $pdo->query("SELECT s.*, y.year_name FROM sections s JOIN academic_years y ON s.year_id=y.year_id ORDER BY y.year_name, s.section_number")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Manage Sections</h1>
            <p class="page-subtitle">Assign sections to specific academic years.</p>
        </div>
    </header>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">Action completed successfully!</div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Add New Section</h3>
                <form method="post" class="admin-form">
                    <div class="form-group">
                        <label for="year_id">Select Academic Year</label>
                        <select id="year_id" name="year_id" required>
                            <option value="">-- Choose Year --</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y['year_id'] ?>"><?= htmlspecialchars($y['year_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section_number">Section Number/Name</label>
                        <input type="text" id="section_number" name="section_number" placeholder="e.g. Section A or 101" required>
                    </div>

                    <button type="submit" name="add" class="btn-primary">Create Section</button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Active Sections</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Section</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($sections): ?>
                            <?php foreach ($sections as $s): ?>
                                <tr>
                                    <td><span class="badge-year"><?= htmlspecialchars($s['year_name']) ?></span></td>
                                    <td class="fw-bold">Section <?= htmlspecialchars($s['section_number']) ?></td>
                                    <td style="text-align:right;">
                                        <a href="?delete=<?= $s['section_id'] ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Delete this section?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No sections created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>