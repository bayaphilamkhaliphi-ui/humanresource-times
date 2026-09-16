<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['linemanager', 'admin'])) {
    header("Location: index.html");
    exit;
}
require_once 'db.php';

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$form_id) {
    die("Invalid KPI form ID.");
}

$user_id = $_SESSION['user_id'];

// Fetch the form and verify the current user is the manager of this form
$form_query = $conn->prepare("
    SELECT f.*, u.fullname AS employee_name, u.username AS employee_username
    FROM kpi_forms f
    JOIN users u ON f.employee_id = u.id
    WHERE f.id = ? AND f.manager_id = ?
");
$form_query->bind_param("ii", $form_id, $user_id);
$form_query->execute();
$form = $form_query->get_result()->fetch_assoc();
$form_query->close();

if (!$form) {
    die("You are not authorized to review this KPI form.");
}

// Fetch tasks and outputs
$tasks = [];
$t_q = $conn->query("SELECT * FROM kpi_tasks WHERE kpi_form_id = $form_id");
if ($t_q) {
    while ($r = $t_q->fetch_assoc()) {
        $r['outputs'] = [];
        $o_q = $conn->query("SELECT * FROM kpi_outputs WHERE kpi_task_id = " . $r['id']);
        while ($o = $o_q->fetch_assoc()) {
            $r['outputs'][] = $o;
        }
        $tasks[] = $r;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    $status = 'reviewed';
    $overall_rating = floatval($_POST['manager_overall_rating']);
    $overall_comment = trim($_POST['manager_overall_comment']);

    // Update form status and overall ratings
    $status_update = $conn->prepare("UPDATE kpi_forms SET status = ?, manager_overall_rating = ?, manager_overall_comment = ? WHERE id = ?");
    $status_update->bind_param("sdsi", $status, $overall_rating, $overall_comment, $form_id);
    $status_update->execute();
    $status_update->close();

    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admindash.php' : 'managerdash.php') . "?msg=" . urlencode("Review saved successfully."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Review KPI – <?php echo htmlspecialchars($form['employee_name']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #8B0000, #D40000); min-height: 100vh; padding: 40px 20px; margin: 0; }
    .container { width: 100%; max-width: 1000px; margin: 0 auto; background: white; padding: 30px 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
    h2 { color: #8B0000; margin-bottom: 5px; }
    .primary-btn { padding: 12px 24px; background: #8B0000; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
    .primary-btn:hover { background: #600000; }
    
    .task-card { border: 1px solid #e0e0e0; border-radius: 10px; margin-bottom: 25px; overflow: hidden; }
    .task-header { background: #f4f6f9; padding: 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
    .task-header h4 { margin: 0; color: #333; font-size: 15px; }
    .task-body { padding: 15px; }
    
    .output-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 15px; }
    .output-table th, .output-table td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; }
    .output-table th { background: #fafafa; font-weight: 500; color: #666; }

    .eval-section { background: #fdfdfd; border: 1px solid #eee; border-radius: 8px; padding: 15px; display: grid; grid-template-columns: 1fr 3fr; gap: 15px; margin-top: 15px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 5px; }
    
    .overall-review-section { background: #fff8f8; border: 2px solid #8B0000; border-radius: 10px; padding: 25px; margin-top: 40px; }
    .overall-review-section h3 { color: #8B0000; margin-top: 0; margin-bottom: 15px; }
    input[type=number], textarea { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
    textarea { height: 80px; resize: none; }
</style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px;">
        <div>
            <h2>Review KPI – <?php echo htmlspecialchars($form['employee_name']); ?></h2>
            <p style="font-size: 13px; color: #666; margin: 0;">Status: <strong style="color: #8B0000;"><?php echo ucfirst($form['status']); ?></strong></p>
        </div>
        <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admindash.php' : 'managerdash.php'; ?>" class="primary-btn" style="background: #666;">← Back</a>
    </div>

    <?php if (empty($tasks)): ?>
        <p style="text-align:center; color:#666; margin: 40px 0;">No tasks found.</p>
    <?php else: ?>
        <?php foreach ($tasks as $i => $t): ?>
        <div class="task-card">
            <div class="task-header">
                <div>
                    <h4>Task <?= $i+1 ?>: <?= htmlspecialchars($t['task_description']) ?></h4>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                        Task Weight: <strong><?= $t['task_weight'] ?>%</strong>
                    </div>
                </div>
            </div>
            <div class="task-body">
                <?php if (!empty($t['outputs'])): ?>
                <table class="output-table">
                    <thead>
                        <tr>
                            <th>Expected Output</th>
                            <th style="width: 150px;">Output Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($t['outputs'] as $o): ?>
                        <tr>
                            <td><?= htmlspecialchars($o['output_description']) ?></td>
                            <td><?= $o['output_weight'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="font-size: 13px; color: #999; margin-top: 0;">No outputs specified.</p>
                <?php endif; ?>

                <div class="eval-section">
                    <div class="form-group">
                        <label>Employee Rating</label>
                        <div style="padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 6px; font-weight: bold;"><?= htmlspecialchars($t['employee_rating'] ?? 'N/A') ?> / 100</div>
                    </div>
                    <div class="form-group">
                        <label>Employee Comment</label>
                        <div style="padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 6px; min-height: 40px;"><?= nl2br(htmlspecialchars($t['employee_comment'] ?? 'N/A')) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <form method="POST">
            <div class="overall-review-section">
                <h3>Manager's Overall Evaluation</h3>
                <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 20px;">
                    <div class="form-group">
                        <label>Overall Rating (0 - 100)</label>
                        <input type="number" step="0.01" min="0" max="100" name="manager_overall_rating" required placeholder="e.g. 90" value="<?= htmlspecialchars($form['manager_overall_rating'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Overall Manager Comments</label>
                        <textarea name="manager_overall_comment" required placeholder="Provide your final feedback on the overall performance..."><?= htmlspecialchars($form['manager_overall_comment'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                    <button type="submit" name="update_review" class="primary-btn">Save Overall Evaluation & Mark as Reviewed</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>