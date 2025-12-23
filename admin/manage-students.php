<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';

checkAccess('admin');

$msg = "";
$error = "";
$students = [];

// --- 1. MANUAL REGISTRATION (Post-Redirect-Get) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO students (user_id, full_name, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?)");
        $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
        
        $stmt->execute([
            $user_id, 
            $_POST['full_name'], 
            $_POST['gender'], 
            $_POST['batch'], 
            $_POST['semester'], 
            $_POST['department_id'], 
            $_POST['year_id'], 
            $_POST['section_id']
        ]);
        
        // Success redirect to prevent duplicate entry on refresh
        header("Location: manage-students.php?msg=" . urlencode("Student registered successfully!"));
        exit();
    } catch (PDOException $e) {
        $error = "Registration Error: " . $e->getMessage();
    }
}

// --- 2. THE PERFECTED CSV IMPORT LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        try {
            $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
            fgetcsv($handle); // Skip header row
            
            $pdo->beginTransaction();
            
            // Validation queries
            $checkDept = $pdo->prepare("SELECT department_id FROM departments WHERE department_id = ?");
            $checkYear = $pdo->prepare("SELECT year_id FROM academic_years WHERE year_id = ?");
            $checkSec  = $pdo->prepare("SELECT section_id FROM sections WHERE section_id = ?");
            $insertStmt = $pdo->prepare("INSERT INTO students (user_id, full_name, gender, batch, semester, department_id, year_id, section_id) VALUES (?,?,?,?,?,?,?,?)");
            
            $importedCount = 0;
            $skippedRows = [];
            $rowIdx = 1;

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) >= 8) {
                    $rowIdx++;
                    $u_id = !empty($row[0]) ? $row[0] : null;
                    $name = $row[1]; $gen = $row[2]; $batch = $row[3]; $sem = $row[4];
                    $d_id = $row[5]; $y_id = $row[6]; $s_id = $row[7];

                    // Validate existence in DB
                    $checkDept->execute([$d_id]);
                    $checkYear->execute([$y_id]);
                    $checkSec->execute([$s_id]);

                    if ($checkDept->fetch() && $checkYear->fetch() && $checkSec->fetch()) {
                        $insertStmt->execute([$u_id, $name, $gen, $batch, $sem, $d_id, $y_id, $s_id]);
                        $importedCount++;
                    } else {
                        $skippedRows[] = "Row $rowIdx ($name)";
                    }
                }
            }
            $pdo->commit();
            fclose($handle);
            
            $resMsg = "Import Complete: $importedCount students added.";
            if(count($skippedRows) > 0) $resMsg .= " (Skipped " . count($skippedRows) . " rows due to invalid IDs)";
            
            header("Location: manage-students.php?msg=" . urlencode($resMsg));
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Import Failed: " . $e->getMessage();
        }
    }
}

// Catch URL messages
if (isset($_GET['msg'])) { $msg = $_GET['msg']; }

// --- 3. FETCH DATA ---
try {
    $years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
    $sections = $pdo->query("SELECT * FROM sections")->fetchAll();
    $departments = $pdo->query("SELECT * FROM departments")->fetchAll();

    $query = "SELECT s.*, y.year_name, sec.section_number, d.department_name,
              COALESCE((SELECT AVG(ea.rating) * 20 FROM evaluations e 
              JOIN evaluation_answers ea ON e.evaluation_id = ea.evaluation_id 
              WHERE e.student_id = s.student_id), 0) AS avg_score
              FROM students s
              LEFT JOIN academic_years y ON s.year_id = y.year_id
              LEFT JOIN sections sec ON s.section_id = sec.section_id
              LEFT JOIN departments d ON s.department_id = d.department_id
              ORDER BY s.student_id DESC";
    $students = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "System Error: " . $e->getMessage();
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

<main class="main-content">
    <div class="page-header">
        <h1>Student Management</h1>
    </div>

    <?php if($msg): ?> 
        <div style="background:#d4edda; color:#155724; padding:15px; border:1px solid #c3e6cb; border-radius:5px; margin-bottom:20px;">
            <?= htmlspecialchars($msg) ?>
        </div> 
    <?php endif; ?>

    <?php if($error): ?> 
        <div style="background:#f8d7da; color:#721c24; padding:15px; border:1px solid #f5c6cb; border-radius:5px; margin-bottom:20px;">
            <?= $error ?>
        </div> 
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <div class="card" style="padding:20px; border: 1px solid #ddd; border-radius:8px;">
            <h3>Register New Student</h3>
            <form method="POST" action="manage-students.php">
                <div style="display:grid; grid-template-columns: 1fr 2fr; gap:10px; margin-bottom:10px;">
                    <input type="number" name="user_id" placeholder="User ID (Optional)">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:10px;">
                    <select name="gender" required style="flex:1;"><option value="Male">Male</option><option value="Female">Female</option></select>
                    <input type="text" name="batch" placeholder="Batch" required style="flex:1;">
                    <input type="number" name="semester" placeholder="Sem" required style="flex:1;">
                </div>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <select name="department_id" required style="flex:1;">
                        <option value="">-- Dept --</option>
                        <?php foreach($departments as $d): ?><option value="<?= $d['department_id'] ?>"><?= $d['department_name'] ?></option><?php endforeach; ?>
                    </select>
                    <select name="year_id" required style="flex:1;">
                        <?php foreach($years as $y): ?><option value="<?= $y['year_id'] ?>"><?= $y['year_name'] ?></option><?php endforeach; ?>
                    </select>
                    <select name="section_id" required style="flex:1;">
                        <?php foreach($sections as $s): ?><option value="<?= $s['section_id'] ?>">Sec <?= $s['section_number'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add" style="width:100%; padding:10px; background:#4e73df; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Add Student</button>
            </form>
        </div>

        <div class="card" style="padding:20px; border: 1px solid #ddd; border-radius:8px;">
            <h3>CSV Bulk Import</h3>
            <p style="font-size:11px; color:#666;">Format: user_id, name, gender, batch, sem, dept_id, year_id, sec_id</p>
            <form method="POST" enctype="multipart/form-data" action="manage-students.php">
                <input type="file" name="csv_file" accept=".csv" required style="margin:15px 0;">
                <button type="submit" name="import_csv" style="width:100%; padding:10px; background:#1cc88a; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Upload & Import</button>
            </form>
        </div>
    </div>

    <div class="card" style="padding:15px; border: 1px solid #ddd; border-radius:8px;">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background:#f8f9fc; text-align:left; border-bottom:2px solid #e3e6f0;">
                    <th style="padding:10px;">Student Name</th>
                    <th>Dept / Batch</th>
                    <th>Score</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($students)): ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px;">No student records found.</td></tr>
                <?php else: ?>
                    <?php foreach($students as $s): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;">
                            <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
                            <small>User ID: <?= $s['user_id'] ?? 'None' ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($s['department_name']) ?><br>
                            <small><?= $s['batch'] ?> (Sem <?= $s['semester'] ?>)</small>
                        </td>
                        <td><?= number_format($s['avg_score'], 1) ?>%</td>
                        <td>
                            <a href="edit_student.php?id=<?= $s['student_id'] ?>" style="color:#4e73df; text-decoration:none; font-weight:bold;">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>