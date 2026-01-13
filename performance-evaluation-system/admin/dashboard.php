<?php
// 1. Initialize Session and Constants
if (session_status() === PHP_SESSION_NONE) session_start();

// Use absolute paths to prevent 404/Not Found errors
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/config.php';

// 2. Authentication Check
// We check 'user_role' (which should be set during login)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?error=unauthorized');
    exit();
}

// 3. Fetch Live Stats
$countStudents = 0; $countTeachers = 0; $countDepts = 0; $countEvals = 0; $error = "";

try {
    // Check 'students' table
    $countStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    
    // IMPORTANT: Check if your table uses 'role' or 'role_id'
    // Based on your previous SQL: role_id 3 = Teacher, 1 = Admin
    $countTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3 OR role = 'teacher'")->fetchColumn();
    
    $countDepts    = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    $countEvals    = $pdo->query("SELECT COUNT(*) FROM evaluations")->fetchColumn();
} catch (PDOException $e) {
    $error = "Stats Error: " . $e->getMessage();
}

$displayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator';

// 4. Include UI Elements
require_once __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../includes/sidebar-admin.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content" style="margin-left: 260px; padding: 30px; background: #f8fafc; min-height: 100vh;">
    <header class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div class="page-title-area">
            <h1 class="page-title" style="font-size: 1.8rem; font-weight: 800; color: #0f172a;">Management Dashboard</h1>
            <p class="page-subtitle" style="color: #64748b;">Welcome, <span class="user-highlight" style="color: #3b82f6; font-weight: 600;"><?= htmlspecialchars($displayName); ?></span>. Here is what's happening today.</p>
        </div>
        <div class="header-actions">
            <button class="btn-generate" onclick="window.print()" style="padding: 10px 20px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600;">
                <span class="icon">🖨️</span> Print System Report
            </button>
        </div>
    </header>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <section class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: #64748b; font-size: 0.9rem; font-weight: 600;">Total Students</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a;"><?= number_format($countStudents) ?></div>
            </div>
            <div style="font-size: 2rem; background: #eff6ff; padding: 10px; border-radius: 12px;">👥</div>
        </div>
        
        <div class="stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: #64748b; font-size: 0.9rem; font-weight: 600;">Faculty Members</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a;"><?= number_format($countTeachers) ?></div>
            </div>
            <div style="font-size: 2rem; background: #ecfdf5; padding: 10px; border-radius: 12px;">👨‍🏫</div>
        </div>

        <div class="stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: #64748b; font-size: 0.9rem; font-weight: 600;">Evaluations</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a;"><?= number_format($countEvals) ?></div>
            </div>
            <div style="font-size: 2rem; background: #fff7ed; padding: 10px; border-radius: 12px;">📝</div>
        </div>

        <div class="stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: #64748b; font-size: 0.9rem; font-weight: 600;">Departments</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a;"><?= number_format($countDepts) ?></div>
            </div>
            <div style="font-size: 2rem; background: #f5f3ff; padding: 10px; border-radius: 12px;">🏢</div>
        </div>
    </section>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <section class="section-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 20px; color: #0f172a;">Recent Evaluations</h3>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #f1f5f9;">
                        <th style="padding: 12px; color: #64748b; font-size: 0.85rem;">Student</th>
                        <th style="padding: 12px; color: #64748b; font-size: 0.85rem;">Teacher</th>
                        <th style="padding: 12px; color: #64748b; font-size: 0.85rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-weight: 500;">Abebe Bikila</td>
                        <td style="padding: 12px;">Dr. Smith</td>
                        <td style="padding: 12px;"><span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Completed</span></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="action-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 15px; color: #0f172a;">Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="<?= BASE_URL ?>admin/manage-students.php" style="text-decoration: none; color: #3b82f6; font-weight: 600; padding: 10px; background: #eff6ff; border-radius: 8px;">Manage Students</a>
                <a href="<?= BASE_URL ?>admin/manage-teachers.php" style="text-decoration: none; color: #3b82f6; font-weight: 600; padding: 10px; background: #eff6ff; border-radius: 8px;">Manage Teachers</a>
                <a href="<?= BASE_URL ?>admin/create-questionnaire.php" style="text-decoration: none; color: #3b82f6; font-weight: 600; padding: 10px; background: #eff6ff; border-radius: 8px;">Create Questionnaire</a>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>