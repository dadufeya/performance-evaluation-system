<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
checkAccess('admin');

$msg = ""; $error = "";

// --- 1. ACTION: DELETE ---
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM sections WHERE year_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM academic_years WHERE year_id = ?")->execute([$id]);
        $pdo->commit();
        header("Location: manage-years.php?msg=Configuration+Removed+Successfully");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Error: This configuration is currently in use.";
    }
}

// --- 2. ACTION: FULL UPDATE ---
if (isset($_POST['update_config'])) {
    $yid = $_POST['edit_year_id'];
    $new_dept = trim($_POST['edit_dept_name']);
    $new_year = trim($_POST['edit_year_name']);
    $new_sec  = (int)$_POST['edit_section_count'];

    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("UPDATE academic_years SET year_name = ? WHERE year_id = ?")->execute([$new_year, $yid]);

        $stmt = $pdo->prepare("SELECT department_id FROM departments WHERE department_name = ?");
        $stmt->execute([$new_dept]);
        $did = $stmt->fetchColumn();
        if (!$did) {
            $pdo->prepare("INSERT INTO departments (department_name) VALUES (?)")->execute([$new_dept]);
            $did = $pdo->lastInsertId();
        }
        
        $pdo->prepare("UPDATE academic_years SET department_id = ? WHERE year_id = ?")->execute([$did, $yid]);

        $pdo->prepare("DELETE FROM sections WHERE year_id = ?")->execute([$yid]);
        $secStmt = $pdo->prepare("INSERT INTO sections (year_id, section_number) VALUES (?, ?)");
        for ($i = 1; $i <= $new_sec; $i++) { $secStmt->execute([$yid, $i]); }

        $pdo->commit();
        $msg = "Configuration updated successfully.";
    } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $error = "Update failed: " . $e->getMessage(); }
}

// --- 3. ACTION: ADD NEW ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $dept_name = trim($_POST['dept_name']);
    $year_label = trim($_POST['year_name']);
    $sec_count = (int)$_POST['section_count'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT department_id FROM departments WHERE department_name = ?");
        $stmt->execute([$dept_name]);
        $dept_id = $stmt->fetchColumn();
        if (!$dept_id) {
            $pdo->prepare("INSERT INTO departments (department_name) VALUES (?)")->execute([$dept_name]);
            $dept_id = $pdo->lastInsertId();
        }
        
        $pdo->prepare("INSERT INTO academic_years (year_name, department_id) VALUES (?, ?)")->execute([$year_label, $dept_id]);
        $new_year_id = $pdo->lastInsertId();
        
        $secStmt = $pdo->prepare("INSERT INTO sections (year_id, section_number) VALUES (?, ?)");
        for ($i = 1; $i <= $sec_count; $i++) { $secStmt->execute([$new_year_id, $i]); }
        
        $pdo->commit();
        $msg = "Success: Added $year_label.";
    } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $error = "Error: " . $e->getMessage(); }
}

// --- 4. DATA FETCH (Updated to fetch department_id) ---
$registry = $pdo->query("
    SELECT y.*, d.department_name, d.department_id,
    (SELECT COUNT(*) FROM sections s WHERE s.year_id = y.year_id) as total_sections 
    FROM academic_years y 
    LEFT JOIN departments d ON y.department_id = d.department_id
    ORDER BY y.year_id DESC
")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
    :root { --primary: #2563eb; --slate: #1e293b; --bg: #f8fafc; }
    .main-content { margin-left: 280px; padding: 40px; background: var(--bg); min-height: 100vh; font-family: 'Inter', sans-serif; }
    .setup-card { background: white; border-radius: 12px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .input-flow-row { display: grid; grid-template-columns: 1.2fr 1.2fr 0.6fr auto; gap: 15px; align-items: flex-end; background: #f1f5f9; padding: 25px; border-radius: 12px; margin-bottom: 35px; border: 1px solid #cbd5e1; }
    .form-ctrl { padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; font-size: 14px; }
    .btn-submit { background: var(--primary); color: white; border: none; padding: 0 25px; border-radius: 8px; font-weight: 700; height: 48px; cursor: pointer; }
    .modern-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .modern-table th { text-align: left; padding: 15px; font-size: 11px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #f1f5f9; }
    .modern-table td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .id-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 12px; border: 1px solid #e2e8f0; }
    #editModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; backdrop-filter: blur(4px); }
    .modal-content { background:white; width:450px; margin:8% auto; padding:35px; border-radius:16px; }
</style>

<div class="main-content">
    <div class="setup-card">
        <h1 style="font-weight:800; color:var(--slate);">Academic Infrastructure</h1>
        
        <?php if($msg || isset($_GET['msg'])): ?>
            <div class="alert alert-success" style="padding:15px; background:#dcfce7; color:#166534; border-radius:8px; margin-bottom:20px;">
                ✅ <?= htmlspecialchars($msg ?: $_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="input-flow-row">
            <div><label style="font-size:11px; font-weight:800;">DEPARTMENT</label>
            <input type="text" name="dept_name" class="form-ctrl" placeholder="Computer Science" required></div>
            <div><label style="font-size:11px; font-weight:800;">YEAR</label>
            <input type="text" name="year_name" class="form-ctrl" placeholder="3rd Year" required></div>
            <div><label style="font-size:11px; font-weight:800;">SECTIONS</label>
            <input type="number" name="section_count" class="form-ctrl" min="1" value="1"></div>
            <button type="submit" name="save_config" class="btn-submit">Add Configuration</button>
        </form>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>Dept ID</th>
                    <th>Department</th>
                    <th>Year ID</th>
                    <th>Year Label</th>
                    <th>Capacity</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($registry as $r): ?>
                <tr>
                    <td><span class="id-badge"><?= $r['department_id'] ?></span></td>
                    <td><b><?= htmlspecialchars($r['department_name']) ?></b></td>
                    <td><span class="id-badge" style="color:var(--primary);"><?= $r['year_id'] ?></span></td>
                    <td style="color:var(--primary); font-weight:700;"><?= htmlspecialchars($r['year_name']) ?></td>
                    <td><span style="background:#dbeafe; padding:5px 12px; border-radius:20px; font-size:11px; color:#1e40af;"><?= $r['total_sections'] ?> Sections</span></td>
                    <td style="text-align:right;">
                        <button style="color:#f59e0b; font-weight:700; border:none; background:none; cursor:pointer;" onclick="openEdit(<?= $r['year_id'] ?>, '<?= addslashes($r['year_name']) ?>', <?= $r['total_sections'] ?>, '<?= addslashes($r['department_name']) ?>')">Edit</button>
                        <a href="?delete_id=<?= $r['year_id'] ?>" style="color:#ef4444; font-weight:700; text-decoration:none; margin-left:10px;" onclick="return confirm('Delete this?')">Remove</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editModal">
    <div class="modal-content">
        <h2 style="margin-top:0;">Edit Configuration</h2>
        <form method="POST">
            <input type="hidden" name="edit_year_id" id="edit_year_id">
            <label style="font-size:12px; font-weight:bold; display:block; margin-bottom:5px;">Department Name</label>
            <input type="text" name="edit_dept_name" id="edit_dept_name" class="form-ctrl" required style="margin-bottom:15px;">
            
            <label style="font-size:12px; font-weight:bold; display:block; margin-bottom:5px;">Year Label</label>
            <input type="text" name="edit_year_name" id="edit_year_name" class="form-ctrl" required style="margin-bottom:15px;">
            
            <label style="font-size:12px; font-weight:bold; display:block; margin-bottom:5px;">Total Sections</label>
            <input type="number" name="edit_section_count" id="edit_section_count" class="form-ctrl" min="1" required style="margin-bottom:20px;">
            
            <button type="submit" name="update_config" class="btn-submit" style="width:100%;">Update Changes</button>
            <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer; color:#64748b;">Cancel</button>
        </form>
    </div>
</div>

<script>
function openEdit(id, year, count, dept) {
    document.getElementById('edit_year_id').value = id;
    document.getElementById('edit_year_name').value = year;
    document.getElementById('edit_section_count').value = count;
    document.getElementById('edit_dept_name').value = dept;
    document.getElementById('editModal').style.display = 'block';
}
</script>

<?php require_once '../includes/footer.php'; ?>