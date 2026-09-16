<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.html");
    exit;
}
require_once 'db.php';

$status = $_GET['status'] ?? '';
$count  = $_GET['count'] ?? 0;
$msg    = $_GET['msg'] ?? '';

// Metric Summaries
$metric_docs = $conn->query("SELECT COUNT(*) AS total FROM documents")->fetch_assoc()['total'] ?? 0;
$metric_agreed = $conn->query("SELECT COUNT(*) AS total FROM employee_documents WHERE status = 'agreed'")->fetch_assoc()['total'] ?? 0;
$metric_viewed = $conn->query("SELECT COUNT(*) AS total FROM employee_documents WHERE status = 'viewed'")->fetch_assoc()['total'] ?? 0;

// Fetch all uploaded documents
$existing_docs = $conn->query("
    SELECT d.*, u.fullname AS uploader_name,
    (SELECT COUNT(*) FROM employee_documents ed WHERE ed.document_id = d.id AND ed.status = 'agreed') AS agreed_count,
    (SELECT COUNT(*) FROM employee_documents ed WHERE ed.document_id = d.id AND ed.status = 'viewed') AS viewed_count
    FROM documents d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    ORDER BY d.upload_date DESC
");

// Fetch detailed staff agreement reports
$staff_reports = $conn->query("
    SELECT ed.*, u.fullname AS staff_name, u.username AS staff_username, d.title AS doc_title, d.category AS doc_category
    FROM employee_documents ed
    JOIN users u ON ed.employee_id = u.id
    JOIN documents d ON ed.document_id = d.id
    ORDER BY ed.agreed_at DESC, ed.viewed_at DESC
");

$all_kpis_q = $conn->query("SELECT id, title FROM documents WHERE category = 'KPI' ORDER BY title ASC");
$all_kpis = [];
if ($all_kpis_q) {
    while ($r = $all_kpis_q->fetch_assoc()) {
        $all_kpis[] = $r;
    }
}

$all_managers_q = $conn->query("SELECT id, fullname, username FROM users WHERE role = 'linemanager' ORDER BY fullname ASC");
$all_managers = [];
if ($all_managers_q) {
    while ($r = $all_managers_q->fetch_assoc()) {
        $all_managers[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Times of Eswatini - Admin HR Dashboard & Compliance Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root { 
        --primary-color: #8B0000; 
        --primary-hover: #600000; 
        --text-dark: #333333; 
        --text-light: #666666; 
        --border-color: #e0e0e0; 
        --bg-light: #f4f6f9; 
        --success-bg: #d4edda;
        --success-color: #155724;
        --info-bg: #d1ecf1;
        --info-color: #0c5460;
        --warning-bg: #fff3cd;
        --warning-color: #856404;
    }
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background: var(--bg-light); color: var(--text-dark); min-height:100vh; display:flex; flex-direction:column; }
    
    header { width:100%; background: var(--primary-color); padding:15px 40px; display:flex; justify-content:space-between; align-items:center; color:white; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    header .brand { font-size:20px; font-weight:700; display:flex; align-items:center; gap:10px; }
    header nav { display:flex; align-items:center; gap:20px; }
    header nav a { color:white; text-decoration:none; font-size:14px; font-weight:500; transition:opacity 0.3s; }
    header nav a:hover { opacity:0.8; text-decoration:underline; }
    header .user-info { font-size:13px; background:rgba(255,255,255,0.15); padding:6px 14px; border-radius:20px; }

    .main-wrapper { max-width:1200px; width:90%; margin:30px auto; display:flex; flex-direction:column; gap:30px; }
    
    /* Summary Cards */
    .metrics-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; }
    .metric-card { background:white; padding:20px 25px; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,0.04); border-left:5px solid var(--primary-color); display:flex; justify-content:space-between; align-items:center; }
    .metric-card.agreed { border-left-color: #28a745; }
    .metric-card.viewed { border-left-color: #17a2b8; }
    .metric-title { font-size:13px; color:var(--text-light); font-weight:500; text-transform:uppercase; letter-spacing:0.5px; }
    .metric-value { font-size:28px; font-weight:700; color:var(--text-dark); margin-top:4px; }
    .metric-icon { font-size:32px; }

    .card { background:white; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,0.05); padding:30px; }
    .portal-header { border-bottom:2px solid #f0f0f0; padding-bottom:12px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
    .portal-header h2 { color:var(--primary-color); font-size:22px; font-weight:700; }
    .portal-header span { font-size:12px; color:var(--text-light); background:var(--bg-light); padding:4px 12px; border-radius:20px; }

    .alert { padding:12px 18px; border-radius:8px; margin-bottom:15px; font-size:14px; font-weight:500; text-align:center; }
    .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .alert-info { background:#d1ecf1; color:#0c5460; border:1px solid #bee5eb; }

    /* Form Styling */
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .form-group { margin-bottom:15px; }
    label { display:block; font-weight:500; color:var(--text-dark); margin-bottom:5px; font-size:13px; }
    input[type=text], textarea, select { width:100%; padding:10px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:14px; color:var(--text-dark); transition:all 0.3s ease; background:#fff; }
    input[type=text]:focus, textarea:focus, select:focus { border-color:var(--primary-color); outline:none; box-shadow:0 0 0 3px rgba(139,0,0,0.1); }
    textarea { resize:none; height:60px; }
    .drop-zone { border:2px dashed var(--border-color); border-radius:8px; padding:15px; text-align:center; background:var(--bg-light); cursor:pointer; transition:all 0.3s ease; position:relative; }
    .drop-zone:hover, .drop-zone.dragover { border-color:var(--primary-color); background:rgba(139,0,0,0.03); }
    .drop-zone input[type="file"] { display:none; }
    .drop-zone-icon svg { width:28px; height:28px; stroke:var(--primary-color); margin-bottom:4px; }
    .drop-zone-text { font-size:13px; color:var(--text-dark); font-weight:500; }
    .drop-zone-subtext { font-size:11px; color:var(--text-light); margin-top:2px; }
    .file-list-container { margin-top:8px; max-height:85px; overflow-y:auto; padding-right:2px; }
    .file-item { display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #eee; padding:6px 10px; border-radius:6px; margin-bottom:4px; font-size:11px; }
    .remove-file-btn { background:rgba(255,0,0,0.1); color:#d32f2f; border:none; width:20px; height:20px; border-radius:50%; cursor:pointer; display:flex; justify-content:center; align-items:center; font-size:12px; }
    .submit-btn { width:100%; padding:12px; background:var(--primary-color); color:white; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:all 0.3s ease; display:flex; justify-content:center; align-items:center; gap:10px; margin-top:10px; }
    .submit-btn:hover { background:var(--primary-hover); }

    /* Tables */
    .custom-table { width:100%; border-collapse:collapse; margin-top:15px; font-size:13px; }
    .custom-table th { background:#f4f6f9; text-align:left; padding:12px 14px; font-weight:600; color:var(--text-dark); border-bottom:2px solid #e0e0e0; }
    .custom-table td { padding:12px 14px; border-bottom:1px solid #eee; color:var(--text-dark); vertical-align:middle; }
    .custom-table tr:hover { background:#fafafa; }
    .category-tag { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; text-transform:uppercase; background:rgba(139,0,0,0.1); color:var(--primary-color); }
    
    /* Status Badges */
    .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; text-align:center; }
    .badge-agreed { background:var(--success-bg); color:var(--success-color); border:1px solid #c3e6cb; }
    .badge-viewed { background:var(--info-bg); color:var(--info-color); border:1px solid #bee5eb; }
    .badge-pending { background:var(--warning-bg); color:var(--warning-color); border:1px solid #ffeeba; }
    
    .btn-delete { background:#dc3545; color:white; text-decoration:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:500; transition:background 0.2s; }
    .btn-delete:hover { background:#bd2130; }
    
    @media(max-width:768px) { .form-row { grid-template-columns:1fr; gap:0; } header { flex-direction:column; gap:10px; padding:15px; } .custom-table { display:block; overflow-x:auto; } }
</style>
</head>
<body>
<header>
    <div class="brand">
        <span>Times of Eswatini HR Portal</span>
    </div>
    <nav>
        <span class="user-info">Logged in as: <strong><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></strong></span>
        <a href="dashboard.php" target="_blank" style="background:rgba(255,255,255,0.2); padding:5px 12px; border-radius:6px;">Staff Portal View ↗</a>
        <a href="logout.php" style="background:#600000; padding:5px 12px; border-radius:6px;">Logout</a>
    </nav>
</header>

<div class="main-wrapper">

   

    <!-- Section 1: Upload Document Card -->
    <div class="card">
        <div class="portal-header">
            <h2>📤 Admin Document Upload</h2>
            <span>Upload KPIs, SOPs, and Policies</span>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                ✓ Successfully uploaded <?= intval($count) ?> document(s) to the system!
            </div>
        <?php elseif ($status === 'deleted'): ?>
            <div class="alert alert-info">
                🗑️ Document successfully removed from the system.
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($msg ?: 'Operation failed. Please try again.') ?>
            </div>
        <?php endif; ?>

        <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Document Category</label>
                    <select name="category" required>
                        <option value="" disabled selected>Select Category</option>
                        <option value="KPI">KPI (Key Performance Indicators)</option>
                        <option value="SOP">SOP (Standard Operating Procedure)</option>
                        <option value="Policy">Policy (Company Policies)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Document Title</label>
                    <input type="text" name="title" placeholder="e.g. Q3 Performance Metrics / IT Policy" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Brief outline of the document content..."></textarea>
            </div>
            <div class="form-group">
                <label>Select Document File(s)</label>
                <div class="drop-zone" id="dropZone">
                    <div class="drop-zone-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                        </svg>
                    </div>
                    <div class="drop-zone-text">Drag & Drop files here or click to browse</div>
                    <div class="drop-zone-subtext">PDF, DOCX, XLSX allowed (max 10MB each)</div>
                    <input type="file" id="fileInput" name="documents[]" multiple required>
                </div>
                <div class="file-list-container" id="fileListContainer"></div>
            </div>
            <button type="submit" class="submit-btn" id="submitBtn">
                <span>Upload Documents</span>
            </button>
        </form>
    </div>

    <!-- Section 1.5: Assign KPI -->
    <div class="card">
        <div class="portal-header">
            <h2>🎯 Assign KPI to Line Manager</h2>
            <span>Delegate specific KPIs to managers</span>
        </div>
        
        <form action="create_kpi_workbook.php" method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label>Select Line Manager</label>
                    <select name="user_id" required>
                        <option value="" disabled selected>Select Manager</option>
                        <?php foreach($all_managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>"><?= htmlspecialchars($mgr['fullname']) ?> (@<?= htmlspecialchars($mgr['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="submit-btn">Create/Edit KPI Workbook</button>
        </form>
    </div>

    <!-- Section 2: Staff Document Reading & Agreement Reports -->
    <div class="card">
        <div class="portal-header">
            <h2>📋 Staff Reading & Agreement Compliance Report</h2>
            <span>Real-time tracking of staff acknowledgments</span>
        </div>

        <?php if ($staff_reports && $staff_reports->num_rows > 0): ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Document Title</th>
                        <th>Category</th>
                        <th>Agreement Status</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rep = $staff_reports->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($rep['staff_name']) ?></strong><br>
                                <small style="color:#777;">@<?= htmlspecialchars($rep['staff_username']) ?></small>
                            </td>
                            <td><strong><?= htmlspecialchars($rep['doc_title']) ?></strong></td>
                            <td><span class="category-tag"><?= htmlspecialchars($rep['doc_category']) ?></span></td>
                            <td>
                                <?php if ($rep['status'] === 'agreed'): ?>
                                    <span class="badge badge-agreed">✓ Agreed</span>
                                <?php elseif ($rep['status'] === 'viewed'): ?>
                                    <span class="badge badge-viewed">👁 Viewed</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($rep['status'] === 'agreed' && $rep['agreed_at']) {
                                    echo date('M d, Y \a\t h:i A', strtotime($rep['agreed_at']));
                                } elseif ($rep['viewed_at']) {
                                    echo date('M d, Y \a\t h:i A', strtotime($rep['viewed_at']));
                                } else {
                                    echo "—";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:#777; font-size:14px; text-align:center; padding:20px;">No staff reading or agreement logs recorded yet. When staff members read and agree to documents, their responses will appear here in real-time.</p>
        <?php endif; ?>
    </div>


    <!-- Section 2.5: Line Manager KPIs to Review -->
    <div class="card">
        <div class="portal-header">
            <h2>📊 Line Manager KPIs to Review</h2>
            <span>Review KPIs submitted by your department managers</span>
        </div>
        
        <?php
        $admin_id = $_SESSION['user_id'];
        $manager_kpis = [];
        $kpi_q = $conn->query("
            SELECT k.*, u.fullname, d.name AS dept_name 
            FROM kpi_forms k 
            JOIN users u ON k.employee_id = u.id 
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE k.manager_id = $admin_id AND k.status != 'draft'
            ORDER BY k.updated_at DESC
        ");
        if ($kpi_q) {
            while ($r = $kpi_q->fetch_assoc()) {
                $manager_kpis[] = $r;
            }
        }
        ?>

        <table class="custom-table">
            <thead>
                <tr>
                    <th>Manager Name</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manager_kpis as $kpi): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($kpi['fullname']) ?></strong></td>
                    <td><?= htmlspecialchars($kpi['dept_name']) ?></td>
                    <td>
                        <?php if ($kpi['status'] === 'submitted'): ?>
                            <span class="badge badge-pending">Submitted</span>
                        <?php elseif ($kpi['status'] === 'reviewed'): ?>
                            <span class="badge badge-agreed">Reviewed</span>
                        <?php else: ?>
                            <span class="badge"><?= htmlspecialchars($kpi['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($kpi['updated_at']) ?></td>
                    <td><a href="review_kpi.php?id=<?= $kpi['id'] ?>" class="btn" style="background:#8B0000; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px;">Review</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($manager_kpis)): ?>
                <tr><td colspan="5" style="text-align:center; color:#777;">No manager KPIs to review yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Section 3: Create Line Manager -->
    <div class="card">
        <div class="portal-header">
            <h2>👥 Create Line Manager Account</h2>
            <span>Assign department managers (MD Role)</span>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <?php 
        $dept_q = $conn->query("SELECT * FROM departments");
        $departments = [];
        if ($dept_q) {
            while ($row = $dept_q->fetch_assoc()) $departments[] = $row;
        }
        ?>

        <form action="add_manager.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" required>
                </div>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" required>
                    <option value="" disabled selected>Select Department</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="submit-btn">Create Manager Account</button>
        </form>
    </div>

</div>

<script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileListContainer = document.getElementById('fileListContainer');
    const uploadForm = document.getElementById('uploadForm');

    let dataTransfer = new DataTransfer();

    dropZone.addEventListener('click', () => fileInput.click());

    ['dragenter','dragover','dragleave','drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => { e.preventDefault(); e.stopPropagation(); }, false);
    });
    dropZone.addEventListener('dragenter', () => dropZone.classList.add('dragover'));
    dropZone.addEventListener('dragover', () => dropZone.classList.add('dragover'));
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => { dropZone.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });

    fileInput.addEventListener('change', function() { handleFiles(this.files); });

    function handleFiles(files) {
        for (let i = 0; i < files.length; i++) {
            dataTransfer.items.add(files[i]);
        }
        fileInput.files = dataTransfer.files;
        renderFileList();
    }

    function renderFileList() {
        fileListContainer.innerHTML = '';
        if (dataTransfer.files.length > 0) {
            fileInput.removeAttribute('required');
        } else {
            fileInput.setAttribute('required', 'required');
        }
        Array.from(dataTransfer.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.classList.add('file-item');
            fileItem.innerHTML = `
                <div class="file-item-info">
                    <span>📄 <strong>${file.name}</strong> (${(file.size / 1024).toFixed(1)} KB)</span>
                </div>
                <button type="button" class="remove-file-btn" onclick="removeFile(${index})" title="Remove">×</button>
            `;
            fileListContainer.appendChild(fileItem);
        });
    }

    window.removeFile = function(index) {
        const dt = new DataTransfer();
        for (let i = 0; i < dataTransfer.files.length; i++) {
            if (i !== index) dt.items.add(dataTransfer.files[i]);
        }
        dataTransfer = dt;
        fileInput.files = dataTransfer.files;
        renderFileList();
    }

    uploadForm.addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = `<span>Uploading...</span>`;
        btn.style.opacity = '0.8';
    });
</script>
</body>
</html>