<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
checkAccess('admin');

$msg = ""; $error = "";
?>

<?php
require_once '../includes/header.php';
require_once '../includes/sidebar-admin.php';
?>

<style>
.main-content { margin-left: 260px; padding: 25px; background: #f1f5f9; min-height: 100vh; font-family: sans-serif; }
.card { background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.btn { padding: 7px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; font-weight: bold; border: none; display: inline-block; }
.btn-blue { background: #2563eb; color: white; } .btn-green { background: #10b981; color: white; }
.btn-red { background: #ef4444; color: white; } .btn-gray { background: #64748b; color: white; }
input, select, textarea { padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
#questions div { margin-bottom: 10px; }
hr { border: 0; border-top: 1px solid #e2e8f0; margin: 10px 0; }
</style>

<main class="main-content">
    <h2 style="margin-top:0;">Create Questionnaire</h2>

    <?php if($msg): ?><div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:15px; border:1px solid #bbf7d0;"><?= $msg ?></div><?php endif; ?>
    <?php if($error): ?><div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; margin-bottom:15px;"><?= $error ?></div><?php endif; ?>

    <div class="card">
        <form method="POST" action="save-questionnaire.php">
            <label>Category</label>
            <input type="text" name="category" required placeholder="Enter category name">

            <div id="questions"></div>

            <button type="button" onclick="addQ()" class="btn btn-green">Add Question</button>
            <button type="submit" class="btn btn-blue" style="float:right;">Save Questionnaire</button>
        </form>
    </div>
</main>

<script>
let i = 0;

function addQ() {
    const d = document.createElement('div');
    d.innerHTML = `
        <label>Question ${i + 1}</label>
        <textarea name="q[${i}][text]" required placeholder="Enter question text"></textarea>
        <select name="q[${i}][type]" required>
            <option value="scale">Scale (1–5)</option>
            <option value="boolean">Yes / No</option>
            <option value="text">Text</option>
        </select>
        <input type="number" name="q[${i}][weight]" value="1" placeholder="Weight">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-red" style="margin-top:5px;">Remove</button>
        <hr>
    `;
    document.getElementById('questions').appendChild(d);
    i++;
}
</script>

<?php include '../includes/footer.php'; ?>
