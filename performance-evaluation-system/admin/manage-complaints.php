<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $stmt = $pdo->prepare("UPDATE complaints SET feedback = ?, teacher_id = ?, status = 'resolved' WHERE complaint_id = ?");
    $stmt->execute([trim($_POST['feedback']), $_POST['teacher_id'], $_POST['complaint_id']]);
    header("Location: manage-complaints.php?msg=success");
    exit();
}

// Fetch Initial Data
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll();
$query = "SELECT c.*, u.full_name AS student_name, t.full_name AS teacher_name, d.department_name 
          FROM complaints c 
          LEFT JOIN users u ON c.user_id = u.user_id
          LEFT JOIN teachers t ON c.teacher_id = t.teacher_id 
          LEFT JOIN departments d ON t.department_id = d.department_id 
          ORDER BY (c.status = 'pending') DESC, c.created_at DESC";
$complaints = $pdo->query($query)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <h1 class="page-title">Complaint Management</h1>
        <p class="page-subtitle">Assign student messages to faculty for resolution.</p>
    </header>

    <div class="section-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="35%">Student Message</th>
                    <th width="20%">Teacher</th>
                    <th width="10%">Status</th>
                    <th width="35%">Resolution Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $row): ?>
                <tr>
                    <td>
                        <div class="message-bubble" style="background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                            <strong style="color:#2563eb;">From: <?= htmlspecialchars($row['student_name'] ?? 'Student') ?></strong><br>
                            <p style="margin:5px 0; color:#475569;"><?= htmlspecialchars($row['message']) ?></p>
                        </div>
                    </td>
                    <td>
                        <span style="font-weight:600; color:#1e293b;">
                            <?= $row['teacher_name'] ? htmlspecialchars($row['teacher_name']) : '<em style="color:#94a3b8;">Not Assigned</em>' ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?= ($row['status'] == 'resolved') ? 'bg-success' : 'bg-warning' ?>" 
                              style="padding:6px 12px; border-radius:20px; font-size:0.7rem; font-weight:800; text-transform:uppercase;">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="complaint_id" value="<?= $row['complaint_id'] ?>">
                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                <select onchange="loadTeachers(this.value, <?= $row['complaint_id'] ?>)" class="form-select" required style="flex:1;">
                                    <option value="">Select Dept</option>
                                    <?php foreach($departments as $d): ?>
                                        <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="teacher_id" id="teacher_dropdown_<?= $row['complaint_id'] ?>" class="form-select" required style="flex:1;">
                                    <option value="">Select Teacher</option>
                                </select>
                            </div>
                            <textarea name="feedback" class="form-control" placeholder="Resolution notes..." required style="height:70px; margin-bottom:10px; border-radius:8px;"></textarea>
                            <button type="submit" name="submit_feedback" class="btn-publish" style="width:100%; background:linear-gradient(145deg, #2563eb, #1d4ed8); border:none; padding:12px; color:white; border-radius:10px; font-weight:700; box-shadow:0 4px 0 #1e40af;">
                                Resolve Complaint
                            </button>
                        </form>
                        <?php else: ?>
                            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:10px; color:#166534; font-size:0.85rem;">
                                <strong>Resolution Note:</strong><br><?= htmlspecialchars($row['feedback']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>


<script>
function loadTeachers(deptId, complaintId) {
    const dropdown = document.getElementById('teacher_dropdown_' + complaintId);
    
    if (!deptId) {
        dropdown.innerHTML = '<option value="">Select Teacher</option>';
        return;
    }

    dropdown.innerHTML = '<option>Loading...</option>';

    // Use absolute reference to the file in the current directory
    fetch('get_teachers_by_dept.php?dept_id=' + deptId)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            dropdown.innerHTML = '<option value="">Select Teacher</option>';
            if (data.length === 0) {
                dropdown.innerHTML = '<option value="">No Teachers Found</option>';
            } else {
                data.forEach(teacher => {
                    const opt = document.createElement('option');
                    opt.value = teacher.teacher_id;
                    opt.textContent = teacher.full_name;
                    dropdown.appendChild(opt);
                });
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            dropdown.innerHTML = '<option value="">Error Loading</option>';
        });
}
</script>

<?php include '../includes/footer.php'; ?>