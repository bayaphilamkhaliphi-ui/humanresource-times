<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Times of Eswatini HR Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* (same as original) */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body { background:#f8f8f8; }
        header { background:#b00020; color:white; padding:20px 40px; display:grid; grid-template-columns:1fr auto 1fr; align-items:center; }
        .header-title { grid-column:2; text-align:center; font-size:24px; }
        nav { grid-column:3; justify-self:end; display:flex; align-items:center; gap:20px; }
        nav a { color:white; text-decoration:none; font-size:15px; }
        nav a:hover { color:#ffd6d6; }
        .profile { width:42px; height:42px; border-radius:50%; background:white; color:#b00020; display:flex; justify-content:center; align-items:center; font-weight:600; }
        .container { width:90%; margin:35px auto; }
        .welcome { margin-bottom:30px; }
        .welcome h1 { color:#b00020; margin-bottom:10px; }
        .welcome p { color:#555; margin-bottom:5px; }
        .welcome a { color:#b00020; font-weight:600; text-decoration:underline; }
        .welcome a:hover { color:#800015; }
        .repositories { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:25px; }
        .repository { background:white; padding:30px; border-radius:15px; border-top:5px solid #b00020; box-shadow:0 5px 20px rgba(176,0,32,.15); }
        .repository h2 { color:#b00020; margin-bottom:15px; }
        .repository p { color:#666; margin-bottom:20px; }
        button { width:100%; padding:12px; border:none; border-radius:8px; background:#b00020; color:white; cursor:pointer; font-size:15px; }
        button:hover { background:#800015; }
        footer { text-align:center; margin:40px; color:#777; }
    </style>
</head>
<body>
    <header>
        <div></div>
        <h2 class="header-title">HR Portal</h2>
        <nav>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <div class="welcome">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></h1>
            <p>Access KPIs, SOPs and Policies.</p>
            <p><a href="https://secure.payspace.com/" target="_blank" rel="noopener noreferrer">Click here to visit Payspace.</a></p>
        </div>
       <div class="repositories">
    <div class="repository">
        <h2>KPIs</h2>
        <p>Departmental KPIs for all departments.</p>
        <a href="employee_documents.php?category=KPI">
            <button>View KPIs</button>
        </a>
    </div>

    <div class="repository" style="border-top-color: #28a745; box-shadow:0 5px 20px rgba(40,167,69,.15);">
        <h2 style="color: #28a745;">My KPI Evaluation</h2>
        <p>Fill out and submit your interactive KPI table.</p>
        <a href="my_kpi.php">
            <button style="background: #28a745;">Fill Form</button>
        </a>
    </div>

    <div class="repository">
        <h2>SOPs</h2>
        <p>SOP for every department.</p>
        <a href="employee_documents.php?category=SOP">
            <button>View SOPs</button>
        </a>
    </div>

    <div class="repository">
        <h2>Policy</h2>
        <p>Organisation-wide policies.</p>
        <a href="employee_documents.php?category=Policy">
            <button>View Policies</button>
        </a>
    </div>
</div>
        </div>
        <footer>&copy; 2026 Times of Eswatini. All rights reserved.</footer>
    </div>
</body>
</html>