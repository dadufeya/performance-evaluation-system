<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$msg = "";
$error = "";

// --- HANDLE RELEASE ACTION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['release'])) {
    try {
        $stmt = $pdo->prepare("UPDATE evaluations SET released = 1 WHERE released = 0");
        $stmt->execute();
        $count = $stmt->rowCount();
        header("Location: release-results.php?msg=success&count=$count");
        exit();
    } catch (PDOException $e) {
        $error = "Update Failed: " . $e->getMessage();
    }
}

// --- FETCH STATS (With Error Handling for missing columns) ---
$pending = 0;
$released = 0;

try {
    $pending = $pdo->query("SELECT COUNT(*) FROM evaluations WHERE released = 0")->fetchColumn();
    $released = $pdo->query("SELECT COUNT(*) FROM evaluations WHERE released = 1")->fetchColumn();
} catch (PDOException $e) {
    $error = "Database Error: The 'released' column is missing. Please run the SQL ALTER TABLE command.";
}

require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-style.css">

<main class="main-content">
    <header class="page-header">
        <div class="page-title-area">
            <h1 class="page-title">Publication Control</h1>
            <p class="page-subtitle">Manage the visibility of evaluation results for faculty and students.</p>
        </div>
    </header>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            ✅ Publication Successful! <strong><?= htmlspecialchars($_GET['count'] ?? 0) ?></strong> evaluations are now visible.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-top: 20px;">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-secondary-grid" style="grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">
        
        <section class="form-section">
            <div class="section-card" style="border-top: 4px solid var(--primary-blue);">
                <h3 class="section-heading">Release Evaluation Results</h3>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
                    Releasing results allows faculty members to see their performance scores and student feedback in their respective dashboards.
                </p>

                <div style="background: #fff9f0; border: 1px solid #ffecad; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <h4 style="color: #92400e; margin: 0 0 5px 0; font-size: 0.85rem;">⚠️ Important Notice</h4>
                    <p style="color: #b45309; margin: 0; font-size: 0.8rem;">
                        Check the stats on the right. Ensure all departments have completed their evaluations before publishing.
                    </p>
                </div>

                <form method="post" onsubmit="return confirm('Are you sure you want to release all pending results?');">
                    <button type="submit" name="release" class="btn-publish" 
                            style="width: 100%; border:none; cursor:pointer; padding: 15px; font-size: 1rem; <?= ($pending == 0 || $error) ? 'opacity:0.5; cursor:not-allowed;' : '' ?>"
                            <?= ($pending == 0 || $error) ? 'disabled' : '' ?>>
                        <?= ($pending == 0) ? 'All Results Already Released' : 'Release Results Now' ?>
                    </button>
                </form>
            </div>
        </section>

        <section class="list-section">
            <div class="section-card">
                <h3 class="section-heading">Current Status</h3>
                
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                        <div>
                            <span style="display: block; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Pending Release</span>
                            <span style="font-size: 1.5rem; font-weight: 700; color: #0f172a;"><?= $pending ?></span>
                        </div>
                        <span class="badge-pending" style="background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2;">Hidden</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                        <div>
                            <span style="display: block; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Published Results</span>
                            <span style="font-size: 1.5rem; font-weight: 700; color: #0f172a;"><?= $released ?></span>
                        </div>
                        <span class="badge-pending" style="background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7;">Visible</span>
                    </div>
                </div>

                <div style="margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <p style="font-size: 0.8rem; color: #94a3b8;">
                        System Control Panel <br>
                        <strong>Last Sync: <?= date('H:i:s') ?></strong>
                    </p>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>