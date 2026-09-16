<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'linemanager') {
    header("Location: index.html");
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'] ?? null;
$msg = $_GET['msg'] ?? '';

// Check if HR
$is_hr = false;
if ($department_id) {
    $dept_q = $conn->query("SELECT name FROM departments WHERE id = $department_id");
    if ($dept_q && $row = $dept_q->fetch_assoc()) {
        if (strtolower($row['name']) === 'hr' || strtolower($row['name']) === 'human resources') {
            $is_hr = true;
        }
    }
}

// Fetch team employees
$team_employees = [];
if ($department_id) {
    $emp_q = $conn->query("SELECT id, fullname, username, email FROM users WHERE manager_id = $user_id OR (department_id = $department_id AND role = 'employee')");
    if ($emp_q) {
        while ($r = $emp_q->fetch_assoc()) {
            $team_employees[] = $r;
        }
    }
}

// Fetch team KPIs
$team_kpis = [];
$kpi_q = $conn->query("
    SELECT k.*, u.fullname 
    FROM kpi_forms k 
    JOIN users u ON k.employee_id = u.id 
    WHERE k.manager_id = $user_id AND k.status != 'draft'
    ORDER BY k.updated_at DESC
");
if ($kpi_q) {
    while ($r = $kpi_q->fetch_assoc()) {
        $team_kpis[] = $r;
    }
}

// Fetch all KPIs for assignment
$all_kpis_q = $conn->query("SELECT id, title FROM documents WHERE category = 'KPI' ORDER BY title ASC");
$all_kpis = [];
if ($all_kpis_q) {
    while ($r = $all_kpis_q->fetch_assoc()) {
        $all_kpis[] = $r;
    }
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manager Dashboard - HR System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
    header { background: #8B0000; color: white; padding: 15px 40px; display: flex; justify-content: space-between; }
    header a { color: white; text-decoration: none; margin-left: 15px; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 4px;}
    .container { max-width: 1200px; margin: 30px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    h2 { color: #8B0000; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 12px; border: 1px solid #eee; text-align: left; }
    th { background: #f4f6f9; }
    .btn { padding: 8px 12px; background: #8B0000; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px;}
    .btn-hr { background: #28a745; }
</style>
</head>
<body>
<header>
    <div style="font-size:20px; font-weight:bold;">Line Manager Portal</div>
    <div>
        Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? '') ?>
        <a href="dashboard.php">My Own KPIs</a>
        <a href="logout.php" style="background:#600000;">Logout</a>
    </div>
</header>
<div class="container">
    <?php if ($msg): ?>
        <div style="padding: 10px; background: #d4edda; color: #155724; margin-bottom: 15px; border-radius: 4px;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($is_hr): ?>
        <div style="margin-bottom: 30px; padding: 15px; background: #e9ecef; border-radius: 8px; border-left: 4px solid #28a745;">
            <h3>HR Administrator Tools</h3>
            <p>You have special access to company-wide reports as the HR Manager.</p>
            <a href="export_reports.php" class="btn btn-hr">Export All Employees KPIs (CSV)</a>
        </div>
    <?php endif; ?>

    <h2>My Team</h2>
    <a href="add_employee.php" class="btn">+ Add Employee to Team</a>
    <table>
        <tr><th>Name</th><th>Username</th><th>Email</th></tr>
        <?php foreach ($team_employees as $emp): ?>
        <tr>
            <td><?= htmlspecialchars($emp['fullname']) ?></td>
            <td><?= htmlspecialchars($emp['username']) ?></td>
            <td><?= htmlspecialchars($emp['email']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($team_employees)): ?>
        <tr><td colspan="3">No employees in your team yet.</td></tr>
        <?php endif; ?>
    </table>

    <h2 style="margin-top: 40px;">Create KPI Workbook for Staff</h2>
    <?php if ($error): ?>
        <div style="padding: 10px; background: #f8d7da; color: #721c24; margin-bottom: 15px; border-radius: 4px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <form action="create_kpi_workbook.php" method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 15px;">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Select Staff Member</label>
            <select name="user_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="" disabled selected>Select Staff</option>
                <?php foreach($team_employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['fullname']) ?> (@<?= htmlspecialchars($emp['username']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Create/Edit KPI Workbook</button>
    </form>

    <h2 style="margin-top: 40px;">Team KPIs to Review</h2>
    <table>
        <tr><th>Employee</th><th>Status</th><th>Last Updated</th><th>Action</th></tr>
        <?php foreach ($team_kpis as $kpi): ?>
        <tr>
            <td><?= htmlspecialchars($kpi['fullname']) ?></td>
            <td><?= htmlspecialchars($kpi['status']) ?></td>
            <td><?= htmlspecialchars($kpi['updated_at']) ?></td>
            <td><a href="review_kpi.php?id=<?= $kpi['id'] ?>" class="btn">Review</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($team_kpis)): ?>
        <tr><td colspan="4">No KPIs submitted for review yet.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
