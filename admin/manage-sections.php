<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin'); // Ensure only admins can access

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
    $section_number = trim($_POST['section_number']);
    
    if (!empty($year_id) && !empty($section_number)) {
        $stmt = $pdo->prepare("INSERT INTO sections (year_id, section_number) VALUES (?,?)");
        $stmt->execute([$year_id, $section_number]);
        header("Location: manage-sections.php?msg=added");
        exit();
    }
}

$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name DESC")->fetchAll();
$sections = $pdo->query("SELECT s.*, y.year_name FROM sections s JOIN academic_years y ON s.year_id=y.year_id ORDER BY y.year_name DESC, s.section_number ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Section Management</h1>
            <p class="page-subtitle">Organize and assign student sections to specific academic years.</p>
        </div>
    </header>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            <?= $_GET['msg'] == 'added' ? '✅ New section created successfully!' : '🗑️ Section removed successfully.' ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid">
        <section class="form-section">
            <div class="section-card">
                <h3 class="section-heading">Add New Section</h3>
                <form method="post" class="admin-form">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Academic Year</label>
                        <select name="year_id" class="form-select" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                            <option value="">-- Choose Year --</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y['year_id'] ?>"><?= htmlspecialchars($y['year_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Section Name/Number</label>
                        <input type="text" name="section_number" class="form-control" placeholder="e.g. Section A, 101, or Morning Shift" required>
                    </div>

                    <button type="submit" name="add" class="btn-publish" style="width: 100%; border:none; cursor:pointer;">
                        Create Section
                    </button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Active Sections</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40%">Academic Year</th>
                            <th width="40%">Section Designation</th>
                            <th width="20%" style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($sections): ?>
                            <?php foreach ($sections as $s): ?>
                                <tr>
                                    <td><span class="badge-pending" style="background:#e0f2fe; color:#0369a1;"><?= htmlspecialchars($s['year_name']) ?></span></td>
                                    <td><strong style="color:#0f172a;">Section <?= htmlspecialchars($s['section_number']) ?></strong></td>
                                    <td style="text-align:right;">
                                        <a href="?delete=<?= $s['section_id'] ?>" 
                                           style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.85rem;"
                                           onclick="return confirm('Are you sure you want to delete this section?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 40px; color:#94a3b8;">No sections created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>