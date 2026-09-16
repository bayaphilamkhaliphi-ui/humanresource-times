<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Get user info and manager
$user_q = $conn->query("SELECT manager_id FROM users WHERE id = $user_id");
$user_info = $user_q->fetch_assoc();
$manager_id = $user_info['manager_id'] ?? 0;

if (!$manager_id) {
    die("You do not have a line manager assigned. Please contact the administrator.");
}

// Get the latest KPI form
$form_q = $conn->query("SELECT * FROM kpi_forms WHERE employee_id = $user_id ORDER BY id DESC LIMIT 1");
$form = $form_q->fetch_assoc();

if (!$form) {
    $form_id = 0;
    $status = 'none';
} else {
    $form_id = $form['id'];
    $status = $form['status'];
}

// Handle Submit Review (Employee self-rating)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // Update tasks
    $t_q = $conn->query("SELECT id FROM kpi_tasks WHERE kpi_form_id = $form_id");
    while ($t = $t_q->fetch_assoc()) {
        $tid = $t['id'];
        if (isset($_POST['rating_' . $tid])) {
            $rating = floatval($_POST['rating_' . $tid]);
            $comment = trim($_POST['comment_' . $tid] ?? '');
            
            $stmt = $conn->prepare("UPDATE kpi_tasks SET employee_rating = ?, employee_comment = ? WHERE id = ?");
            $stmt->bind_param("dsi", $rating, $comment, $tid);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Update form status to submitted
    $conn->query("UPDATE kpi_forms SET status = 'submitted' WHERE id = $form_id");
    header("Location: my_kpi.php?msg=submitted");
    exit;
}

// Fetch tasks and outputs
$tasks = [];
if ($form_id > 0) {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Performance Review</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #8B0000, #D40000); min-height: 100vh; padding: 40px 20px; margin: 0; }
    .container { width: 100%; max-width: 1000px; margin: 0 auto; background: white; padding: 30px 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
    h2 { color: #8B0000; margin-bottom: 5px; }
    .primary-btn { padding: 12px 24px; background: #8B0000; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
    .primary-btn:hover { background: #600000; }
    .alert { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 15px; border-radius: 4px; font-size: 14px; }

    .task-card { border: 1px solid #e0e0e0; border-radius: 10px; margin-bottom: 25px; overflow: hidden; }
    .task-header { background: #f4f6f9; padding: 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
    .task-header h4 { margin: 0; color: #333; font-size: 15px; }
    .task-body { padding: 15px; }
    
    .output-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 15px; }
    .output-table th, .output-table td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; }
    .output-table th { background: #fafafa; font-weight: 500; color: #666; }

    .eval-section { background: #fdfdfd; border: 1px solid #eee; border-radius: 8px; padding: 15px; display: grid; grid-template-columns: 1fr 3fr; gap: 15px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 5px; }
    input[type=number], textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
    textarea { height: 60px; resize: none; }
</style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px;">
        <div>
            <h2>Performance Review & Target Assessment</h2>
            <p style="font-size: 13px; color: #666; margin: 0;">Review your assigned targets and outputs, then rate your performance (0-100) and provide justification comments.</p>
        </div>
        <a href="dashboard.php" class="primary-btn" style="background: #666;">← Back</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'submitted'): ?>
        <div class="alert">Performance review submitted successfully!</div>
    <?php endif; ?>

    <?php if ($status === 'none' || empty($tasks)): ?>
        <p style="text-align:center; color:#666; margin: 40px 0;">No tasks currently published by your manager.</p>
    <?php else: ?>
        <form action="my_kpi.php" method="POST">
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
                            <label>My Rating (0 - 100)</label>
                            <?php if ($status !== 'reviewed'): ?>
                                <input type="number" step="0.01" min="0" max="100" name="rating_<?= $t['id'] ?>" required placeholder="e.g. 85" value="<?= htmlspecialchars($t['employee_rating'] ?? '') ?>">
                            <?php else: ?>
                                <div style="padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 6px; font-weight: bold;"><?= htmlspecialchars($t['employee_rating']) ?> / 100</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Justification Comment</label>
                            <?php if ($status !== 'reviewed'): ?>
                                <textarea name="comment_<?= $t['id'] ?>" required placeholder="Provide your comments here..."><?= htmlspecialchars($t['employee_comment'] ?? '') ?></textarea>
                            <?php else: ?>
                                <div style="padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 6px; min-height: 40px;"><?= nl2br(htmlspecialchars($t['employee_comment'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($status !== 'reviewed'): ?>
            <div style="margin-top: 15px; display: flex; justify-content: flex-end; align-items: center; gap: 15px;">
                <?php if ($status === 'submitted'): ?>
                    <span style="font-size: 13px; color: #d32f2f; font-weight: 500;">(You can still edit your submission until your manager reviews it)</span>
                <?php endif; ?>
                <button type="submit" name="submit_review" class="primary-btn"><?= $status === 'submitted' ? 'Update Performance Review' : 'Submit Performance Review' ?></button>
            </div>
            <?php else: ?>
            <div style="margin-top: 15px; display: flex; justify-content: flex-end;">
                <span style="font-size: 14px; font-weight: 600; color: #8B0000; background: #fff; padding: 10px 20px; border-radius: 20px; border: 1px solid #8B0000;">Status: <?= ucfirst($status) ?></span>
            </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
