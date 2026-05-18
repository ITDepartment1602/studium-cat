<?php
include("count.php");
$count = new count;
$userd = $count->show_users();
?>
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
  header('Location: ../');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <title>Studium Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" type="image/svg+xml" href="../img/logo1.svg">

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- Styles -->
  <link rel="stylesheet" href="adminstyles.css" />
  <link rel="stylesheet" href="../table css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Scripts (head) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
/* ============================================================
   BASE & FONT
   ============================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  background: #f0f2f8;
  color: #1a1f36;
}

/* ============================================================
   SIDEBAR OVERRIDES (works on top of adminstyles.css)
   ============================================================ */
.sidebar {
  background: #ffffff !important;
  border-right: 1px solid #e8eaf2 !important;
  box-shadow: 2px 0 20px rgba(27,73,101,0.07) !important;
}
.sidebar .logo-details {
  background: #ffffff !important;
  border-bottom: 1px solid #e8eaf2 !important;
  padding: 18px 0 !important;
}
.sidebar .nav-links li a {
  color: #6b7280 !important;
  font-size: 0.82rem !important;
  font-weight: 500 !important;
  transition: background 0.18s, color 0.18s !important;
  border-radius: 10px !important;
  margin: 2px 10px !important;
}
.sidebar .nav-links li a:hover,
.sidebar .nav-links li a.active {
  background: linear-gradient(135deg, #1B4965 0%, #2a6f96 100%) !important;
  color: #ffffff !important;
}
.sidebar .nav-links li a:hover i,
.sidebar .nav-links li a.active i {
  color: #ffffff !important;
}
.sidebar .nav-links li a i { color: #9ca3af !important; font-size: 1.1rem !important; }
.sidebar .nav-links .log_out a { color: #ef4444 !important; }
.sidebar .nav-links .log_out a i { color: #ef4444 !important; }
.sidebar .nav-links .log_out a:hover {
  background: #fff5f5 !important;
  color: #dc2626 !important;
}

/* ============================================================
   HOME SECTION
   ============================================================ */
.home-section { background: #f0f2f8 !important; }

/* ============================================================
   GLASS NAV BAR
   ============================================================ */
.home-section nav {
  position: sticky !important;
  top: 0 !important;
  z-index: 100 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 0 24px !important;
  height: 64px !important;
  backdrop-filter: blur(24px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
  background: rgba(255, 255, 255, 0.80) !important;
  border-bottom: 1px solid rgba(232, 234, 242, 0.8) !important;
  box-shadow: 0 2px 24px rgba(27,73,101,0.08) !important;
}
.nav-left { display: flex; align-items: center; gap: 12px; }
.nav-right { display: flex; align-items: center; gap: 10px; }

.nav-greeting {
  display: flex;
  flex-direction: column;
}
.nav-greeting .nav-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1f36;
  line-height: 1.2;
}
.nav-greeting .nav-subtitle {
  font-size: 0.72rem;
  color: #9ca3b0;
  font-weight: 400;
}

/* Glass pill buttons in nav */
.nav-glass-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  border-radius: 50px;
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none !important;
  border: none;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.nav-glass-btn.primary {
  background: linear-gradient(135deg, #1B4965, #2a6f96);
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(27,73,101,0.25);
}
.nav-glass-btn.primary:hover {
  background: linear-gradient(135deg, #153c54, #1B4965);
  box-shadow: 0 4px 16px rgba(27,73,101,0.35);
  transform: translateY(-1px);
  color: #fff !important;
}
.nav-glass-btn.ghost {
  background: rgba(27,73,101,0.07);
  color: #1B4965 !important;
  border: 1px solid rgba(27,73,101,0.15);
}
.nav-glass-btn.ghost:hover {
  background: rgba(27,73,101,0.14);
  transform: translateY(-1px);
  color: #1B4965 !important;
}
.nav-icon-btn {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: rgba(240,242,248,0.9);
  border: 1px solid rgba(232,234,242,0.8);
  color: #6b7280;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.18s;
  text-decoration: none !important;
}
.nav-icon-btn:hover {
  background: #1B4965;
  color: #fff;
  border-color: #1B4965;
}
.nav-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1B4965, #2a6f96);
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
  border: 2px solid rgba(255,255,255,0.8);
  box-shadow: 0 2px 8px rgba(27,73,101,0.2);
}

/* ============================================================
   STAT CARDS  (InsightHub style)
   ============================================================ */
.cards-section {
  padding: 20px 24px 0;
}
.stat-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 20px 22px;
  border: 1px solid #e8eaf2;
  box-shadow: 0 1px 4px rgba(60,72,100,0.05), 0 4px 20px rgba(60,72,100,0.04);
  transition: box-shadow 0.22s ease, transform 0.22s ease;
  position: relative;
  overflow: hidden;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 16px;
}
.stat-card::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--card-accent, #e2e8f0);
  border-radius: 16px 16px 0 0;
}
.stat-card:hover {
  box-shadow: 0 8px 32px rgba(60,72,100,0.12);
  transform: translateY(-2px);
}
.stat-icon-box {
  width: 52px; height: 52px;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.stat-body { flex: 1; min-width: 0; }
.stat-number {
  font-size: 1.9rem;
  font-weight: 800;
  line-height: 1;
  color: #1a1f36;
  letter-spacing: -1px;
  margin-bottom: 5px;
}
.stat-label {
  font-size: 0.69rem;
  font-weight: 600;
  color: #9ca3b0;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-label a { color: inherit !important; text-decoration: none !important; }
.stat-label a:hover { color: #1B4965 !important; }
.stat-expand {
  color: #d1d5db;
  font-size: 0.85rem;
  position: absolute;
  top: 14px; right: 14px;
  cursor: pointer;
  transition: color 0.15s;
}
.stat-expand:hover { color: #1B4965; }

/* ============================================================
   STUDENT LIST PANEL
   ============================================================ */
.content-section { padding: 20px 24px 24px; }
.panel {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e8eaf2;
  box-shadow: 0 1px 4px rgba(60,72,100,0.05), 0 4px 20px rgba(60,72,100,0.04);
  overflow: hidden;
}
.panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #f0f3fb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  background: #ffffff;
}
.panel-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1a1f36;
  display: flex;
  align-items: center;
  gap: 8px;
}
.panel-title-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: linear-gradient(135deg, #1B4965, #2a6f96);
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  font-size: 0.85rem;
}
.panel-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

/* ============================================================
   TABLE
   ============================================================ */
.modern-table { font-size: 0.79rem; width: 100%; border-collapse: collapse; }
.modern-table thead th {
  background: #fafbff;
  color: #9ca3b0;
  font-weight: 700;
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.9px;
  padding: 12px 16px;
  border-bottom: 1.5px solid #e8eaf2;
  white-space: nowrap;
}
.modern-table tbody tr { border-bottom: 1px solid #f4f6fb; transition: background 0.13s; }
.modern-table tbody tr:last-child { border-bottom: none; }
.modern-table tbody tr:hover { background: #f7f9ff; }
.modern-table tbody td { padding: 10px 16px; color: #374151; vertical-align: middle; }

/* Table badges */
.tbl-badge {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 0.68rem; font-weight: 600;
  border-radius: 6px; padding: 3px 9px;
  text-decoration: none !important;
  transition: opacity 0.15s, transform 0.15s;
  white-space: nowrap;
}
.tbl-badge:hover { opacity: 0.8; transform: translateY(-1px); }
.tbl-badge-update  { color: #1B4965; background: #e8f0f8; }
.tbl-badge-delete  { color: #c53030; background: #fff5f5; }
.tbl-badge-enable  { color: #276749; background: #f0fff4; }
.tbl-badge-disable { color: #c53030; background: #fff5f5; }

/* DataTables overrides */
.dataTables_wrapper .dataTables_filter input {
  border: 1px solid #e2e8f0 !important; border-radius: 10px !important;
  padding: 6px 12px !important; font-size: 0.79rem !important; outline: none !important;
  font-family: 'Inter', sans-serif !important;
  transition: border-color 0.18s, box-shadow 0.18s;
}
.dataTables_wrapper .dataTables_filter input:focus {
  border-color: #1B4965 !important;
  box-shadow: 0 0 0 3px rgba(27,73,101,0.1) !important;
}
.dataTables_wrapper select {
  border: 1px solid #e2e8f0 !important; border-radius: 8px !important;
  padding: 4px 8px !important; font-size: 0.79rem !important;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate { font-size: 0.77rem; padding: 12px 20px; color: #9ca3b0; }
.dataTables_wrapper .paginate_button { border-radius: 8px !important; font-size: 0.77rem !important; }
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
  background: linear-gradient(135deg,#1B4965,#2a6f96) !important;
  border-color: #1B4965 !important; color: white !important;
}
.dataTables_wrapper .paginate_button:hover {
  background: #f0f5fa !important; border-color: #e2e8f0 !important; color: #1B4965 !important;
}
div.dataTables_wrapper div.dataTables_filter,
div.dataTables_wrapper div.dataTables_length,
div.dataTables_wrapper div.dataTables_info,
div.dataTables_wrapper div.dataTables_paginate { padding: 10px 20px; }

/* ============================================================
   LEGACY #id01 MODAL
   ============================================================ */
#id01 form input[type=text],
#id01 form input[type=number],
#id01 form input[type=email] {
  width: 100%; padding: 10px 14px; margin: 6px 0;
  display: inline-block; border: 1px solid #d1d5db;
  border-radius: 8px; box-sizing: border-box; font-size: 0.875rem;
  font-family: 'Inter', sans-serif;
}
#id01 form button[type="submit"] {
  background: linear-gradient(135deg, #1B4965, #2a6f96); color: white;
  padding: 12px 20px; margin: 10px 0; border: none;
  cursor: pointer; width: 100%; border-radius: 10px;
  font-weight: 600; font-size: 0.9rem; font-family: 'Inter', sans-serif;
}
#id01 form button[type="submit"]:hover { background: linear-gradient(135deg, #153c54, #1B4965); }
.cancelbtn { width: auto !important; padding: 9px 18px; background-color: #ef4444 !important;
  border-radius: 8px !important; font-size: 0.85rem !important; }
.imgcontainer { text-align: center; margin: 20px 0 10px 0; position: relative; }
#id01 .container { padding: 16px; }
#id01.modal {
  display: none; position: fixed; z-index: 999;
  left: 0; top: 0; width: 100%; height: 100%;
  overflow: auto; background-color: rgba(15,23,42,0.5); padding-top: 60px;
  backdrop-filter: blur(4px);
}
#id01 .modal-content {
  background-color: #fff; margin: 4% auto 12% auto;
  border: none; border-radius: 16px;
  box-shadow: 0 25px 60px rgba(0,0,0,0.2); width: 34%;
}
.close { position: absolute; right: 20px; top: 2px; color: #6b7280; font-size: 30px; font-weight: bold; }
.close:hover, .close:focus { color: #ef4444; cursor: pointer; }
.animate { -webkit-animation: animatezoom 0.25s; animation: animatezoom 0.25s }
@-webkit-keyframes animatezoom { from{-webkit-transform:scale(0.85)} to{-webkit-transform:scale(1)} }
@keyframes animatezoom { from{transform:scale(0.85)} to{transform:scale(1)} }
@media screen and (max-width:300px) { .cancelbtn { width:100% !important; } }

/* ============================================================
   TALLY MODAL
   ============================================================ */
#tallyModal .modal-content {
  border-radius: 1rem !important;
  border: none !important;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18) !important;
  font-family: 'Inter', sans-serif !important;
}
</style>
</head>

<body>
<!-- ===================== SIDEBAR ===================== -->
<div class="sidebar">
  <div class="logo-details">
    <center><img src="../img/logo1.svg" width="28%"></center>
  </div>
  <ul class="nav-links">
    <li><a href="#" class="active">
      <i class="bx bx-grid-alt"></i><span class="links_name">Dashboard</span>
    </a></li>
    <li><a href="manage topics/">
      <i class="bx bx-box"></i><span class="links_name">Manage Topics</span>
    </a></li>
    <li><a href="manage question/">
      <i class="bx bx-list-ul"></i><span class="links_name">Manage Question</span>
    </a></li>
    <li><a href="manage bundle/">
      <i class="bx bx-pie-chart-alt-2"></i><span class="links_name">Manage Bundle</span>
    </a></li>
    <li><a href="manage group/">
      <i class="bx bx-user"></i><span class="links_name">Manage Group</span>
    </a></li>
    <li><a href="manage result/">
      <i class="bx bx-coin-stack"></i><span class="links_name">Manage Result</span>
    </a></li>
    <li><a href="manage feedback">
      <i class="bx bx-heart"></i><span class="links_name">Feedback</span>
    </a></li>
    <li class="log_out"><a href="../index.php">
      <i class="bx bx-log-out"></i><span class="links_name">Log out</span>
    </a></li>
  </ul>
</div>

<!-- ===================== MAIN SECTION ===================== -->
<section class="home-section">

  <!-- GLASS NAV -->
  <nav>
    <div class="nav-left">
      <div class="sidebar-button">
        <i class="bx bx-menu sidebarBtn" style="font-size:1.4rem;color:#1B4965;cursor:pointer;"></i>
      </div>
      <div class="nav-greeting">
        <span class="nav-title">Dashboard</span>
        <span class="nav-subtitle" id="navDate"></span>
      </div>
    </div>

    <div class="nav-right">
      <a href="import_csv.php" class="nav-glass-btn primary">
        <i class="bi bi-file-earmark-arrow-up"></i> Import CSV
      </a>
      <button type="button" class="nav-glass-btn primary"
        data-bs-toggle="modal" data-bs-target="#tallyModal" id="tallyBtn">
        <i class="bi bi-clipboard-data"></i> Tally
      </button>
      <a href="access_history.php" class="nav-glass-btn ghost">
        <i class="bi bi-clock-history"></i> History
      </a>
      <div class="nav-avatar" title="Admin">A</div>
    </div>
  </nav>

  <div class="home-content" style="padding:0;">

    <!-- ========== STAT CARDS ========== -->
    <div class="cards-section">
      <div class="row g-3" id="statsCards">

        <div class="col-xl-2 col-md-4 col-sm-6">
          <div class="stat-card" style="--card-accent:#1F73B7;">
            <div class="stat-icon-box" style="background:#EBF5FF;color:#1F73B7;">
              <i class="bi bi-patch-question-fill"></i>
            </div>
            <div class="stat-body">
              <div class="stat-number"><?php echo $count->questions(); ?></div>
              <div class="stat-label">Total Questions</div>
            </div>
            <i class="bi bi-arrows-angle-expand stat-expand"></i>
          </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
          <div class="stat-card" style="--card-accent:#38A169;">
            <div class="stat-icon-box" style="background:#F0FFF4;color:#276749;">
              <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-body">
              <div class="stat-number"><?php echo $count->concept(); ?></div>
              <div class="stat-label">Total Students</div>
            </div>
            <i class="bi bi-arrows-angle-expand stat-expand"></i>
          </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
          <div class="stat-card" style="--card-accent:#DD6B20;">
            <div class="stat-icon-box" style="background:#FFFAF0;color:#C05621;">
              <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="stat-body">
              <div class="stat-number"><?php echo $count->user(); ?></div>
              <div class="stat-label">Activated</div>
            </div>
            <i class="bi bi-arrows-angle-expand stat-expand"></i>
          </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
          <div class="stat-card" style="--card-accent:#E53E3E;">
            <div class="stat-icon-box" style="background:#FFF5F5;color:#C53030;">
              <i class="bi bi-person-x-fill"></i>
            </div>
            <div class="stat-body">
              <div class="stat-number"><?php echo $count->expired(); ?></div>
              <div class="stat-label">
                <a href="admin_expired.php" target="_blank" rel="noopener noreferrer">Expired</a>
              </div>
            </div>
            <i class="bi bi-arrows-angle-expand stat-expand"></i>
          </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
          <div class="stat-card" style="--card-accent:#805AD5;">
            <div class="stat-icon-box" style="background:#FAF5FF;color:#6B46C1;">
              <i class="bi bi-person-dash-fill"></i>
            </div>
            <div class="stat-body">
              <div class="stat-number"><?php echo $count->bundles(); ?></div>
              <div class="stat-label">
                <a href="admin_not_activated.php" target="_blank" rel="noopener noreferrer">Not Activated</a>
              </div>
            </div>
            <i class="bi bi-arrows-angle-expand stat-expand"></i>
          </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
          <div class="stat-card" style="--card-accent:#2C7A7B;">
            <div class="stat-icon-box" style="background:#E6FFFA;color:#2C7A7B;">
              <i class="bi bi-activity"></i>
            </div>
            <div class="stat-body">
              <div class="stat-number"><?php echo $count->countActiveStudents(); ?></div>
              <div class="stat-label">Active Now</div>
            </div>
            <div style="width:8px;height:8px;border-radius:50%;background:#2C7A7B;
                        box-shadow:0 0 0 3px rgba(44,122,123,0.25);
                        animation:pulse 1.8s infinite;position:absolute;top:16px;right:16px;"></div>
          </div>
        </div>

      </div>
    </div>

    <!-- ========== STUDENT LIST PANEL ========== -->
    <div class="content-section">
      <div class="panel">

        <div class="panel-header">
          <div class="panel-title">
            <div class="panel-title-icon"><i class="bi bi-person-lines-fill"></i></div>
            Student List
          </div>
          <!-- (action buttons moved to nav; kept here as secondary inline group) -->
          <div class="panel-actions">
            <a href="import_csv.php" class="nav-glass-btn primary" style="font-size:0.72rem;padding:6px 12px;">
              <i class="bi bi-file-earmark-arrow-up"></i> Import CSV
            </a>
            <button type="button" class="nav-glass-btn primary" style="font-size:0.72rem;padding:6px 12px;"
              data-bs-toggle="modal" data-bs-target="#tallyModal">
              <i class="bi bi-clipboard-data"></i> Tally
            </button>
            <a href="access_history.php" class="nav-glass-btn ghost" style="font-size:0.72rem;padding:6px 12px;">
              <i class="bi bi-clock-history"></i> History
            </a>
          </div>
        </div>

        <!-- Legacy add-student modal -->
        <div id="id01" class="modal">
          <form class="modal-content animate" action="action.php" method="post" enctype="multipart/form-data">
            <div class="imgcontainer">
              <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close">&times;</span>
            </div>
            <div class="container">
              <label><b>Student Number:</b></label>
              <input type="number" class="form-control" placeholder="Input Student Number" name="studentnumber">
              <label><b>Full Name:</b></label>
              <input type="text" class="form-control" placeholder="Input Full Name" name="fullname">
              <label><b>Bundle:</b></label>
              <select name="bundle_name" class="form-control" style="width:100%;height:auto;">
                <option value="">Select one. . .</option>
                <?php
                  include("../config.php");
                  $q = mysqli_query($con, "SELECT * FROM bundle");
                  foreach ($q as $r) echo '<option value="'.$r['bundle_name'].'">'.$r['bundle_name'].'</option>';
                ?>
              </select>
              <label><b>Group:</b></label>
              <select name="groupname" class="form-control" style="width:100%;height:auto;">
                <option value="">Select one. . .</option>
                <?php
                  $q = mysqli_query($con, "SELECT * FROM grouplist");
                  foreach ($q as $r) echo '<option value="'.$r['groupname'].'">'.$r['groupname'].'</option>';
                ?>
              </select>
              <label><b>Date Enrolled:</b></label>
              <input type="datetime-local" class="form-control" name="dateenrolled">
              <label><b>Date Expired:</b></label>
              <input type="datetime-local" class="form-control" name="dateexpired">
              <label><b>Gmail:</b></label>
              <input type="email" class="form-control" placeholder="Input Gmail" name="email">
              <label><b>Password:</b></label>
              <input type="text" class="form-control" placeholder="Input Password" name="password">
              <input type="hidden" name="status" value="user">
              <button type="submit" name="submit">Submit</button>
            </div>
            <div class="container" style="background:#f1f1f1;">
              <button type="button" onclick="document.getElementById('id01').style.display='none'"
                class="cancelbtn">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Student Table -->
        <div style="padding:0 4px;">
          <table class="table modern-table data-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Student No.</th>
                <th>Full Name</th>
                <th>Bundle</th>
                <th>Group</th>
                <th>Date Expired</th>
                <th>Type</th>
                <th>Gmail</th>
                <th>Password</th>
                <th>Update</th>
                <th>Delete</th>
                <th>Last Active</th>
              </tr>
            </thead>
            <tbody>
              <?php
                include('../config.php');
                date_default_timezone_set('Asia/Manila');
                $current_date = date('Y-m-d H:i:s');
                $query = "SELECT * FROM `login` WHERE status='user' AND groupname!='Admin' AND dateexpired>'$current_date'";
                $data  = mysqli_query($con, $query);

                while ($rows = mysqli_fetch_array($data)):
                  $lastLogin       = strtotime($rows['lastlogin']);
                  $time_difference = time() - $lastLogin;

                  if (empty($rows['lastlogin'])) {
                    $dot = '<span style="display:inline-block;width:10px;height:10px;background:#3b82f6;border-radius:50%;box-shadow:0 0 0 2px rgba(59,130,246,0.25);"></span>';
                  } elseif ($time_difference < 600) {
                    $dot = '<span style="display:inline-block;width:10px;height:10px;background:#22c55e;border-radius:50%;box-shadow:0 0 0 2px rgba(34,197,94,0.25);"></span>';
                  } else {
                    $dot = '<span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:50%;box-shadow:0 0 0 2px rgba(239,68,68,0.15);"></span>';
                  }

                  $fn = addslashes($rows['fullname']);
                  $id = $rows['id'];
              ?>
              <tr>
                <td><?= $dot ?></td>
                <td><?= $rows['studentnumber'] ?></td>
                <td style="font-weight:500;"><?= $rows['fullname'] ?></td>
                <td><?= str_replace('Packege', 'Package', $rows['bundle_name']) ?></td>
                <td><?= $rows['groupname'] ?></td>
                <td style="white-space:nowrap;"><?= $rows['dateexpired'] ?></td>
                <td>
                  <?php if ($rows['type'] == 0): ?>
                    <a href="#" class="tbl-badge tbl-badge-enable"
                       onclick="confirmAction('disable','<?= $fn ?>','<?= $id ?>')">
                      <i class="bi bi-check-circle"></i>Enable
                    </a>
                  <?php else: ?>
                    <a href="#" class="tbl-badge tbl-badge-disable"
                       onclick="confirmAction('enable','<?= $fn ?>','<?= $id ?>')">
                      <i class="bi bi-x-circle"></i>Disable
                    </a>
                  <?php endif; ?>
                </td>
                <td><?= $rows['email'] ?></td>
                <td><?= $rows['password'] ?></td>
                <td>
                  <a href="#" onclick="confirmAction('update','<?= $fn ?>','<?= $id ?>')"
                     class="tbl-badge tbl-badge-update"><i class="bi bi-pencil"></i>Edit</a>
                </td>
                <td>
                  <a href="#" onclick="confirmAction('delete','<?= $fn ?>','<?= $id ?>')"
                     class="tbl-badge tbl-badge-delete"><i class="bi bi-trash"></i>Delete</a>
                </td>
                <td>
                  <?php
                    if (function_exists('get_time_ago') === false) {
                      function get_time_ago($time) {
                        if (empty($time)) return '<span style="color:#3b82f6;">Never</span>';
                        $diff = time() - $time;
                        if ($diff < 600) return '<span style="color:#22c55e;font-weight:500;">Active Now</span>';
                        $c = [12*30*24*60*60=>'year',30*24*60*60=>'month',24*60*60=>'day',60*60=>'hour',60=>'minute',1=>'second'];
                        foreach ($c as $sec => $str) {
                          $d = $diff / $sec;
                          if ($d >= 1) { $t = round($d); return $t.' '.$str.($t>1?'s ':' ').'ago'; }
                        }
                      }
                    }
                    echo get_time_ago(strtotime($rows['lastlogin']));
                  ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

      </div><!-- /panel -->
    </div><!-- /content-section -->

  </div><!-- /home-content -->
</section>

<!-- ===================== TALLY MODAL ===================== -->
<div class="modal fade" id="tallyModal" tabindex="-1" aria-labelledby="tallyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:430px;">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1B4965,#2a6f96);color:white;border-radius:1rem 1rem 0 0;padding:14px 20px;">
        <h5 class="modal-title fw-bold" id="tallyModalLabel" style="font-family:'Inter',sans-serif;font-size:0.95rem;">
          <i class="bi bi-clipboard-data me-2"></i>Daily Tally
        </h5>
        <div class="d-flex align-items-center gap-2">
          <span id="tallyDate" style="font-size:0.72rem;opacity:0.85;font-family:'Inter',sans-serif;"></span>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body" style="padding:20px 24px;">

        <!-- Screenshot section -->
        <div id="tallyScreenshots" style="display:none;margin-bottom:16px;">
          <div style="display:flex;gap:8px;flex-direction:column;">
            <div style="border:1px solid #e8eaf2;border-radius:10px;overflow:hidden;">
              <div style="background:linear-gradient(135deg,#1B4965,#2a6f96);color:white;font-size:0.73rem;font-weight:600;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;font-family:'Inter',sans-serif;">
                <span>Before (Morning)</span>
                <button id="copyBeforeBtn" onclick="copyScreenshot('before')"
                  style="background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.3);color:white;border-radius:6px;padding:2px 10px;font-size:0.68rem;cursor:pointer;font-family:'Inter',sans-serif;">
                  <i class="bi bi-clipboard-image"></i> Copy Image
                </button>
              </div>
              <div style="background:#f8f9fa;padding:6px;text-align:center;min-height:60px;display:flex;align-items:center;justify-content:center;">
                <img id="beforeScreenshot" style="max-width:100%;border-radius:6px;display:none;" />
                <span id="beforeScreenshotNone" style="font-size:0.73rem;color:#aaa;font-family:'Inter',sans-serif;">No morning snapshot yet — open dashboard before importing</span>
              </div>
            </div>
            <div style="border:1px solid #e8eaf2;border-radius:10px;overflow:hidden;">
              <div style="background:linear-gradient(135deg,#198754,#2da86e);color:white;font-size:0.73rem;font-weight:600;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;font-family:'Inter',sans-serif;">
                <span>After (Current)</span>
                <button id="copyAfterBtn" onclick="copyScreenshot('after')"
                  style="background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.3);color:white;border-radius:6px;padding:2px 10px;font-size:0.68rem;cursor:pointer;font-family:'Inter',sans-serif;">
                  <i class="bi bi-clipboard-image"></i> Copy Image
                </button>
              </div>
              <div style="background:#f8f9fa;padding:6px;text-align:center;min-height:60px;display:flex;align-items:center;justify-content:center;">
                <img id="afterScreenshot" style="max-width:100%;border-radius:6px;display:none;" />
                <span id="afterScreenshotLoading" style="font-size:0.73rem;color:#aaa;font-family:'Inter',sans-serif;">
                  <span class="spinner-border spinner-border-sm text-secondary me-1"></span> Capturing...
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div id="tallyLoading" class="text-center py-4">
          <div class="spinner-border text-secondary" role="status" style="width:1.4rem;height:1.4rem;"></div>
          <p class="text-muted mt-2 mb-0" style="font-size:0.82rem;font-family:'Inter',sans-serif;">Loading tally...</p>
        </div>

        <!-- Tally table -->
        <div id="tallyContent" style="display:none;">
          <table class="table table-sm mb-0" style="font-size:0.845rem;font-family:'Inter',sans-serif;">
            <tbody>
              <tr style="background:#e8f4fd;">
                <td class="fw-semibold" style="color:#1B4965;border-radius:8px 0 0 8px;">Total Morning</td>
                <td class="text-end fw-bold" id="t-morning" style="color:#1B4965;border-radius:0 8px 8px 0;"></td>
              </tr>
              <tr><td class="text-muted ps-3">Total of 1 month</td><td class="text-end fw-semibold" id="t-1month"></td></tr>
              <tr><td class="text-muted ps-3">Total of 2 months</td><td class="text-end fw-semibold" id="t-2months"></td></tr>
              <tr><td class="text-muted ps-3">Total of 3 months</td><td class="text-end fw-semibold" id="t-3months"></td></tr>
              <tr><td class="text-muted ps-3">Total of 6 months</td><td class="text-end fw-semibold" id="t-6months"></td></tr>
              <tr><td class="text-muted ps-3">Total of 12 months</td><td class="text-end fw-semibold" id="t-12months"></td></tr>
              <tr style="background:#f0fdf4;">
                <td class="ps-3" style="color:#166534;">Total of 1 month FREE</td>
                <td class="text-end fw-semibold" id="t-1free" style="color:#166534;"></td>
              </tr>
              <tr style="background:#f0fdf4;">
                <td class="ps-3" style="color:#166534;">Total of 2 month FREE</td>
                <td class="text-end fw-semibold" id="t-2free" style="color:#166534;"></td>
              </tr>
              <tr style="border-top:2px solid #e8eaf2;">
                <td class="fw-semibold">Total Access</td>
                <td class="text-end fw-bold text-success" id="t-access"></td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Deleted (duplicate)</td>
                <td class="text-end fw-semibold text-danger" id="t-deleted"></td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Expired students</td>
                <td class="text-end fw-semibold text-warning" id="t-expired"></td>
              </tr>
              <tr style="background:#e8f4fd;border-top:2px solid #1B4965;">
                <td class="fw-semibold" style="color:#1B4965;border-radius:8px 0 0 8px;">Total Afternoon</td>
                <td class="text-end fw-bold" id="t-afternoon" style="color:#1B4965;border-radius:0 8px 8px 0;"></td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer" style="border-top:1px solid #e9ecef;padding:12px 20px;border-radius:0 0 1rem 1rem;flex-direction:column;gap:8px;">
        <button type="button" id="tallyCopyBtn" class="btn btn-sm w-100 fw-semibold"
          style="background:linear-gradient(135deg,#1B4965,#2a6f96);color:white;border-radius:50px;padding:8px 0;display:none;font-family:'Inter',sans-serif;border:none;">
          <i class="bi bi-clipboard me-1" id="tallyCopyIcon"></i>
          <span id="tallyCopyText">Copy Text</span>
        </button>
        <button type="button" id="tallyResetBtn" class="btn btn-sm w-100 fw-semibold"
          style="background:#dc3545;color:white;border-radius:50px;padding:8px 0;display:none;font-family:'Inter',sans-serif;border:none;">
          <i class="bi bi-arrow-counterclockwise me-1" id="tallyResetIcon"></i>
          <span id="tallyResetText">Reset Tally</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== SCRIPTS ===================== -->
<style>
@keyframes pulse {
  0%   { box-shadow: 0 0 0 0 rgba(44,122,123,0.4); }
  70%  { box-shadow: 0 0 0 6px rgba(44,122,123,0); }
  100% { box-shadow: 0 0 0 0 rgba(44,122,123,0); }
}
</style>

<script>
  // Set nav date
  document.getElementById('navDate').textContent = new Date().toLocaleDateString('en-US', {
    weekday:'long', year:'numeric', month:'long', day:'numeric', timeZone:'Asia/Manila'
  });

  function confirmAction(action, name, id) {
    Swal.fire({
      title: action.charAt(0).toUpperCase()+action.slice(1)+' Confirmation',
      text: 'Are you sure you want to '+action+' '+name+'?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'Cancel',
      customClass: { confirmButton: 'swal-confirm-btn' },
      dangerMode: true,
    }).then((result) => {
      if (result.isConfirmed) {
        if (action==='delete')   window.location.href='admin_delete.php?id='+id;
        else if (action==='update') window.location.href='admin_update.php?id='+id;
        else if (action==='enable' || action==='disable')
          window.location.href='type.php?id='+id+'&type='+(action==='enable'?0:1);
      }
    });
  }

  const _s = document.createElement('style');
  _s.innerHTML='.swal-confirm-btn{background:linear-gradient(135deg,#1B4965,#2a6f96)!important;color:white!important;font-family:Inter,sans-serif!important;}';
  document.head.appendChild(_s);

  let sidebar    = document.querySelector('.sidebar');
  let sidebarBtn = document.querySelector('.sidebarBtn');
  sidebarBtn.onclick = function() {
    sidebar.classList.toggle('active');
    sidebarBtn.classList.toggle('bx-menu');
    sidebarBtn.classList.toggle('bx-menu-alt-right');
  };
</script>

<script>
// ==================== TALLY + SCREENSHOT ====================
const TALLY_LS_KEY   = 'studium_tally_cache';
const MORNING_SS_KEY = 'studium_morning_ss';
var _afterCanvas = null;

function todayPH() {
  return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
}

function captureCards() {
  return html2canvas(document.getElementById('statsCards'), {
    scale: 2, useCORS: true, backgroundColor: null, logging: false
  });
}

window.addEventListener('DOMContentLoaded', function() {
  var today = todayPH(), stored = null;
  try { stored = JSON.parse(localStorage.getItem(MORNING_SS_KEY)); } catch(e) {}
  if (!stored || stored.date !== today) {
    setTimeout(function() {
      captureCards().then(function(canvas) {
        localStorage.setItem(MORNING_SS_KEY, JSON.stringify({ date: today, image: canvas.toDataURL('image/png') }));
      }).catch(function(){});
    }, 900);
  }
});

function copyScreenshot(which) {
  var btnId = which==='before' ? 'copyBeforeBtn' : 'copyAfterBtn';
  var btn   = document.getElementById(btnId);
  var orig  = btn.innerHTML;
  function doWrite(canvas) {
    canvas.toBlob(function(blob) {
      try {
        navigator.clipboard.write([new ClipboardItem({'image/png':blob})]).then(function() {
          btn.innerHTML = '<i class="bi bi-clipboard-check"></i> Copied!';
          setTimeout(function(){ btn.innerHTML = orig; }, 2000);
        }).catch(function(){ alert('Copy failed. Right-click the image to copy instead.'); });
      } catch(e) { alert('Clipboard API not supported in this browser.'); }
    }, 'image/png');
  }
  if (which==='before') {
    var stored = null;
    try { stored = JSON.parse(localStorage.getItem(MORNING_SS_KEY)); } catch(e) {}
    if (!stored || !stored.image) { alert('No morning snapshot available yet.'); return; }
    var img = new Image();
    img.onload = function() {
      var c = document.createElement('canvas');
      c.width = img.width; c.height = img.height;
      c.getContext('2d').drawImage(img, 0, 0);
      doWrite(c);
    };
    img.src = stored.image;
  } else {
    if (_afterCanvas) doWrite(_afterCanvas);
    else alert('After screenshot not ready yet. Please wait a moment.');
  }
}

function buildCopyText(d) {
  var dateHeader = '# ' + new Date().toLocaleDateString('en-US', {
    timeZone:'Asia/Manila', month:'long', day:'numeric', year:'numeric'
  });
  return [
    dateHeader,
    'Total Morning: '                             + d.morning_count,
    'Total of 1 month: '                          + d.added_1month,
    'Total of 2 months: '                         + (d.added_2months ?? 0),
    'Total of 3 months: '                         + d.added_3months,
    'Total of 6 months: '                         + d.added_6months,
    'Total of 12 months: '                        + d.added_12months,
    'Total of 1 month FREE: '                     + d.added_1month_free,
    'Total of 2 month FREE: '                     + d.added_2months_free,
    'Total Access: '                              + d.total_access,
    'Total of Deleted due to duplicate account: ' + d.deleted_today,
    'Total of Expired students: '                 + d.expired_count,
    'Total Afternoon: '                           + d.total_afternoon,
  ].join('\n');
}

function renderTally(d) {
  document.getElementById('t-morning').textContent   = d.morning_count;
  document.getElementById('t-1month').textContent    = d.added_1month;
  document.getElementById('t-2months').textContent   = d.added_2months ?? 0;
  document.getElementById('t-3months').textContent   = d.added_3months;
  document.getElementById('t-6months').textContent   = d.added_6months;
  document.getElementById('t-12months').textContent  = d.added_12months;
  document.getElementById('t-1free').textContent     = d.added_1month_free;
  document.getElementById('t-2free').textContent     = d.added_2months_free;
  document.getElementById('t-access').textContent    = d.total_access;
  document.getElementById('t-deleted').textContent   = d.deleted_today;
  document.getElementById('t-expired').textContent   = d.expired_count;
  document.getElementById('t-afternoon').textContent = d.total_afternoon;
  document.getElementById('tallyDate').textContent   = todayPH();
  document.getElementById('tallyLoading').style.display  = 'none';
  document.getElementById('tallyContent').style.display  = 'block';
  document.getElementById('tallyCopyBtn').style.display  = 'block';
  document.getElementById('tallyResetBtn').style.display = 'block';
  localStorage.setItem(TALLY_LS_KEY, JSON.stringify({ date: todayPH(), data: d }));
}

function loadTally() {
  document.getElementById('tallyLoading').style.display     = 'block';
  document.getElementById('tallyContent').style.display     = 'none';
  document.getElementById('tallyCopyBtn').style.display     = 'none';
  document.getElementById('tallyResetBtn').style.display    = 'none';
  document.getElementById('tallyScreenshots').style.display = 'none';

  try {
    var cached = JSON.parse(localStorage.getItem(TALLY_LS_KEY));
    if (cached && cached.date === todayPH()) renderTally(cached.data);
  } catch(e) {}

  fetch('tally_get.php')
    .then(function(r){ return r.json(); })
    .then(function(d){ renderTally(d); })
    .catch(function(){
      document.getElementById('tallyLoading').innerHTML =
        '<p class="text-danger mb-0" style="font-family:Inter,sans-serif;font-size:0.82rem;">Failed to load tally data.</p>';
    });

  var beforeImg  = document.getElementById('beforeScreenshot');
  var beforeNone = document.getElementById('beforeScreenshotNone');
  var stored = null;
  try { stored = JSON.parse(localStorage.getItem(MORNING_SS_KEY)); } catch(e) {}
  if (stored && stored.date === todayPH() && stored.image) {
    beforeImg.src = stored.image; beforeImg.style.display = 'block'; beforeNone.style.display = 'none';
  } else {
    beforeImg.style.display = 'none'; beforeNone.style.display = 'inline';
  }

  var afterImg     = document.getElementById('afterScreenshot');
  var afterLoading = document.getElementById('afterScreenshotLoading');
  afterImg.style.display = 'none'; afterLoading.style.display = 'inline'; _afterCanvas = null;
  document.getElementById('tallyScreenshots').style.display = 'block';

  captureCards().then(function(canvas) {
    _afterCanvas = canvas;
    afterImg.src = canvas.toDataURL('image/png');
    afterImg.style.display = 'block'; afterLoading.style.display = 'none';
  }).catch(function(){ afterLoading.innerHTML = '<span style="color:red;font-size:0.73rem;">Capture failed</span>'; });
}

var tallyModal = document.getElementById('tallyModal');
if (tallyModal) {
  tallyModal.addEventListener('show.bs.modal', function() {
    try {
      var cached = JSON.parse(localStorage.getItem(TALLY_LS_KEY));
      if (cached && cached.date !== todayPH()) localStorage.removeItem(TALLY_LS_KEY);
    } catch(e) {}
    loadTally();
  });
}

var copyBtn = document.getElementById('tallyCopyBtn');
if (copyBtn) {
  copyBtn.addEventListener('click', function() {
    try {
      var cached = JSON.parse(localStorage.getItem(TALLY_LS_KEY));
      if (!cached || !cached.data) return;
      navigator.clipboard.writeText(buildCopyText(cached.data)).then(function() {
        document.getElementById('tallyCopyIcon').className   = 'bi bi-clipboard-check me-1';
        document.getElementById('tallyCopyText').textContent = 'Copied!';
        setTimeout(function() {
          document.getElementById('tallyCopyIcon').className   = 'bi bi-clipboard me-1';
          document.getElementById('tallyCopyText').textContent = 'Copy Text';
        }, 2000);
      });
    } catch(e) {}
  });
}

var resetBtn = document.getElementById('tallyResetBtn');
if (resetBtn) {
  resetBtn.addEventListener('click', function() {
    if (!confirm('Reset all tally counts to 0? Morning count will be kept.')) return;
    document.getElementById('tallyResetIcon').className   = 'bi bi-hourglass-split me-1';
    document.getElementById('tallyResetText').textContent = 'Resetting...';
    resetBtn.disabled = true;
    fetch('tally_reset.php', { method: 'POST' })
      .then(function(r){ return r.json(); })
      .then(function(res) {
        if (res.ok) { localStorage.removeItem(TALLY_LS_KEY); loadTally(); }
        else alert('Reset failed: '+(res.error||'unknown error'));
      })
      .catch(function(){ alert('Reset request failed.'); })
      .finally(function() {
        document.getElementById('tallyResetIcon').className   = 'bi bi-arrow-counterclockwise me-1';
        document.getElementById('tallyResetText').textContent = 'Reset Tally';
        resetBtn.disabled = false;
      });
  });
}
</script>

<script src=".././table js/jquery-3.5.1.js"></script>
<script src=".././table js/jquery.dataTables.min.js"></script>
<script src=".././table js/dataTables.bootstrap5.min.js"></script>
<script src=".././table js/script.js"></script>
</body>
</html>
