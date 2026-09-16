<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'linemanager') {
    header("Location: index.html");
    exit;
}
require_once 'db.php';

// Verify the user is actually the HR manager (department name = 'HR')
$user_id = $_SESSION['user_id'];
$dept_check = $conn->query("
    SELECT d.name 
    FROM users u
    JOIN departments d ON u.department_id = d.id
    WHERE u.id = $user_id
");
$dept = $dept_check->fetch_assoc();
if (!$dept || strtolower($dept['name']) !== 'hr') {
    die("Access denied. Only HR managers can export reports.");
}

// Fetch all submitted/reviewed KPI forms with employee details and tasks
$query = "
    SELECT 
        u.fullname AS employee_name,
        u.username,
        d.name AS department,
        f.status,
        f.created_at AS submitted_at,
        t.task_description,
        t.task_weight,
        t.task_output,
        t.output_weight,
        t.employee_rating,
        t.employee_comment,
        t.manager_rating,
        t.manager_comment
    FROM kpi_forms f
    JOIN users u ON f.employee_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    JOIN kpi_tasks t ON t.kpi_form_id = f.id
    WHERE f.status IN ('submitted', 'reviewed')
    ORDER BY u.fullname, f.created_at, t.id
";

$result = $conn->query($query);
if (!$result) {
    die("Database error: " . $conn->error);
}

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="kpi_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'Employee Name', 'Username', 'Department', 'Form Status', 'Submitted At',
    'Task Description', 'Task Weight (%)', 'Task Output', 'Output Weight (%)',
    'Employee Rating (%)', 'Employee Comment', 'Manager Rating (%)', 'Manager Comment'
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>