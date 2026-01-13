<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$msg = ""; $error = "";

// --- 1. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage-courses.php?msg=deleted");
    exit();
}

// --- 2. HANDLE MANUAL ADD ---
if (isset($_POST['add'])) {
    $course_name = trim($_POST['course_name']);
    $department_id = $_POST['department_id'];
    $year_id = $_POST['year_id']; 
    
    if (!empty($course_name) && !empty($department_id) && !empty($year_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, department_id, year_id) VALUES (?, ?, ?)");
            $stmt->execute([$course_name, $department_id, $year_id]);
            header("Location: manage-courses.php?msg=added");
            exit();
        } catch (PDOException $e) { $error = "Error: " . $e->getMessage(); }
    }
}

// --- 3. HANDLE EDIT (UPDATE) ---
if (isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_name = trim($_POST['course_name']);
    $department_id = $_POST['department_id'];
    $year_id = $_POST['year_id'];

    try {
        $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, department_id = ?, year_id = ? WHERE course_id = ?");
        $stmt->execute([$course_name, $department_id, $year_id, $course_id]);
        header("Location: manage-courses.php?msg=updated");
        exit();
    } catch (PDOException $e) { $error = "Update Failed: " . $e->getMessage(); }
}

// --- 4. HANDLE BULK CSV UPLOAD ---
if (isset($_POST['bulk_upload'])) {
    if ($_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($filename, "r");
        $first_row = true;
        $count = 0;

        try {
            $pdo->beginTransaction();
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($first_row) { $first_row = false; continue; } 
                $stmt = $pdo->prepare("INSERT INTO courses (course_name, department_id, year_id) VALUES (?, ?, ?)");
                $stmt->execute([$data[0], $data[1], $data[2]]);
                $count++;
            }
            $pdo->commit();
            fclose($handle);
            header("Location: manage-courses.php?msg=bulk_added&count=$count");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Bulk Import Failed: Check your CSV IDs.";
        }
    }
}

// Fetch Data
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$years_data = $pdo->query("SELECT * FROM academic_years ORDER BY year_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT c.*, d.department_name, y.year_name 
                        FROM courses c 
                        JOIN departments d ON c.department_id = d.department_id 
                        JOIN academic_years y ON c.year_id = y.year_id
                        ORDER BY d.department_name ASC, y.year_name ASC, c.course_name ASC")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Course Management</h1>
        </div>
    </header>

    <?php if ($error): ?> <div class="alert alert-danger" style="margin-bottom:15px;"><?= $error ?></div> <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" style="margin-bottom:15px;">
            <?php 
                if($_GET['msg'] == 'added') echo "✅ Course Registered!";
                elseif($_GET['msg'] == 'updated') echo "📝 Course Updated!";
                elseif($_GET['msg'] == 'deleted') echo "🗑️ Course Removed.";
                elseif($_GET['msg'] == 'bulk_added') echo "🚀 Imported " . $_GET['count'] . " courses!";
            ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid" style="display: grid; grid-template-columns: 320px 1fr; gap: 20px;">
        <aside>
            <div class="section-card" style="padding: 15px; margin-bottom: 20px;">
                <h3 class="section-heading" style="font-size: 1rem; margin-bottom:12px;">Add New Course</h3>
                <form method="post" class="admin-form">
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size:0.8rem; font-weight:600;">Department</label>
                        <select name="department_id" class="form-select dept-selector" data-target="year_select_add" onchange="filterYears(this)" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size:0.8rem; font-weight:600;">Year Level</label>
                        <select name="year_id" id="year_select_add" class="form-select" required disabled></select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size:0.8rem; font-weight:600;">Course Title</label>
                        <input type="text" name="course_name" class="form-control" placeholder="e.g. Programming" required>
                    </div>
                    <button type="submit" name="add" class="btn-publish" style="width:100%; border:none; cursor:pointer;">Save Course</button>
                </form>
            </div>

            <div class="section-card" style="padding: 15px;">
                <h3 class="section-heading" style="font-size: 1rem; margin-bottom:10px;">Bulk Import</h3>
                <form method="post" enctype="multipart/form-data">
                    <p style="font-size: 0.7rem; color: #64748b; margin-bottom: 10px;">CSV columns: Name, DeptID, YearID</p>
                    <input type="file" name="csv_file" accept=".csv" class="form-control" required style="margin-bottom: 10px; font-size: 0.8rem;">
                    <button type="submit" name="bulk_upload" class="btn-publish" style="width: 100%; background: #10b981; border:none; cursor:pointer;">Upload CSV</button>
                </form>
            </div>
        </aside>

        <section class="list-section">
            <div class="section-card" style="padding: 10px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>CID</th>
                            <th>Course Name</th>
                            <th>Dept (ID)</th>
                            <th>Year (ID)</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 600;"><?= $c['course_id'] ?></td>
                                <td style="font-weight:700; color: #1e293b;"><?= htmlspecialchars($c['course_name']) ?></td>
                                <td><?= htmlspecialchars($c['department_name']) ?> <br><small>ID: <?= $c['department_id'] ?></small></td>
                                <td><?= htmlspecialchars($c['year_name']) ?> <br><small>ID: <?= $c['year_id'] ?></small></td>
                                <td style="text-align:right;">
                                    <button onclick="openEditModal('<?= $c['course_id'] ?>', '<?= addslashes($c['course_name']) ?>', '<?= $c['department_id'] ?>', '<?= $c['year_id'] ?>')"
                                            style="background:none; border:none; color:#4f46e5; cursor:pointer; font-weight:700; font-size:0.8rem; margin-right:10px;">EDIT</button>
                                    <a href="?delete=<?= $c['course_id'] ?>" style="color:#ef4444; font-weight:700; text-decoration:none; font-size:0.8rem;" onclick="return confirm('Delete?')">DEL</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="width: 340px; background:white; padding: 20px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <h3 style="margin: 0 0 15px 0; font-size: 1.1rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Edit Course</h3>
        <form method="post">
            <input type="hidden" name="course_id" id="edit_course_id">
            
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 5px;">Course Title</label>
                <input type="text" name="course_name" id="edit_course_name" class="form-control" style="height: 40px !important; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 5px;">Department</label>
                <select name="department_id" id="edit_dept_select" class="form-select dept-selector" data-target="edit_year_select" onchange="filterYears(this)" style="height: 40px !important; font-size: 0.9rem;" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 5px;">Academic Year</label>
                <select name="year_id" id="edit_year_select" class="form-select" style="height: 40px !important; font-size: 0.9rem;" required></select>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" name="update_course" class="btn-publish" style="flex:1; border:none; height:40px; font-weight:600;">Update</button>
                <button type="button" onclick="closeModal()" style="flex:1; background:#f1f5f9; color:#475569; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const yearsData = <?= json_encode($years_data) ?>;

function filterYears(selectElement) {
    const deptId = selectElement.value;
    const targetId = selectElement.getAttribute('data-target');
    const yearBox = document.getElementById(targetId);
    yearBox.innerHTML = '';
    
    if(deptId) {
        const filtered = yearsData.filter(y => y.department_id == deptId);
        filtered.forEach(y => {
            let opt = document.createElement('option');
            opt.value = y.year_id;
            opt.textContent = y.year_name;
            yearBox.appendChild(opt);
        });
        yearBox.disabled = false;
    } else {
        yearBox.disabled = true;
    }
}

function openEditModal(id, name, deptId, yearId) {
    document.getElementById('edit_course_id').value = id;
    document.getElementById('edit_course_name').value = name;
    document.getElementById('edit_dept_select').value = deptId;
    
    const deptSelect = document.getElementById('edit_dept_select');
    filterYears(deptSelect);
    
    setTimeout(() => {
        document.getElementById('edit_year_select').value = yearId;
    }, 10);
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal if user clicks outside of the card
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>