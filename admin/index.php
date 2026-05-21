<?php
include("count.php");
$count = new count;

$ap_base    = '';
$active_nav = 'dashboard';
$ap_title   = 'Dashboard';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>';
require_once 'partials/sidebar.php';
?>

<style>
/* stat cards */
.stat-card {
  background: var(--card-accent, #007CBF);
  border-radius:16px; padding:26px 24px;
  border:none;
  box-shadow:0 4px 18px rgba(0,0,0,.18);
  transition:box-shadow .2s, transform .2s;
  position:relative; overflow:hidden;
  display:flex; align-items:center; gap:18px; height:100%; min-height:110px;
  text-decoration:none!important; color:#fff!important;
  cursor:pointer;
}
.stat-card::before {
  content:''; position:absolute; top:-30px; right:-20px;
  width:120px; height:120px; border-radius:50%;
  background:rgba(255,255,255,.1);
  pointer-events:none;
}
.stat-card::after {
  content:''; position:absolute; bottom:-40px; left:-20px;
  width:100px; height:100px; border-radius:50%;
  background:rgba(0,0,0,.1);
  pointer-events:none;
}
.stat-card:hover { box-shadow:0 10px 36px rgba(0,0,0,.22); transform:translateY(-3px); color:#fff!important; }
.stat-icon-box {
  width:56px; height:56px; border-radius:16px;
  background:rgba(255,255,255,.18);
  display:flex; align-items:center; justify-content:center;
  font-size:1.5rem; flex-shrink:0; color:#fff;
}
.stat-body { flex:1; min-width:0; }
.stat-number {
  font-size:2.1rem; font-weight:800; line-height:1;
  color:#fff; letter-spacing:-1.5px; margin-bottom:6px;
}
.stat-label {
  font-size:.69rem; font-weight:600; color:rgba(255,255,255,.82);
  text-transform:uppercase; letter-spacing:.8px;
}
.stat-pulse {
  width:9px; height:9px; border-radius:50%; background:rgba(255,255,255,.9);
  position:absolute; top:16px; right:16px;
  animation:pulse 1.8s infinite;
}
@keyframes pulse {
  0%  { box-shadow:0 0 0 0 rgba(255,255,255,.5); }
  70% { box-shadow:0 0 0 7px rgba(255,255,255,0); }
  100%{ box-shadow:0 0 0 0 rgba(255,255,255,0); }
}

/* panel */
.panel {
  background:#fff; border-radius:16px;
  border:1px solid #e2e8f0;
  box-shadow:0 1px 4px rgba(15,23,42,.05);
  overflow:hidden;
}
.panel-header {
  padding:15px 20px; border-bottom:1px solid #f0f4f8;
  display:flex; justify-content:space-between;
  align-items:center; flex-wrap:wrap; gap:10px;
}
.panel-title {
  font-size:.9rem; font-weight:700; color:#0f172a;
  display:flex; align-items:center; gap:8px;
}
.panel-title-icon {
  width:30px; height:30px; border-radius:8px;
  background:linear-gradient(135deg,#0d9488,#007CBF);
  display:flex; align-items:center; justify-content:center;
  color:#fff; font-size:.82rem;
}

/* table action links */
.tbl-badge {
  display:inline-flex; align-items:center; gap:3px;
  font-size:.68rem; font-weight:600; border-radius:6px;
  padding:3px 9px; text-decoration:none!important;
  transition:opacity .15s, transform .13s; white-space:nowrap;
}
.tbl-badge:hover { opacity:.8; transform:translateY(-1px); }
.tbl-badge-update  { color:#007CBF; background:#e0f2fe; }
.tbl-badge-delete  { color:#c53030; background:#fff5f5; }
.tbl-badge-enable  { color:#276749; background:#f0fff4; }
.tbl-badge-disable { color:#c53030; background:#fff5f5; }

/* dataTables */
.dataTables_wrapper { font-size:.82rem; }

/* add-student legacy modal */
#id01 { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(15,23,42,.5); padding-top:60px; backdrop-filter:blur(4px); }
#id01 .modal-content { background:#fff; margin:4% auto 12%; border:none; border-radius:16px; box-shadow:0 25px 60px rgba(0,0,0,.2); width:34%; }
.close { position:absolute; right:20px; top:2px; color:#6b7280; font-size:30px; font-weight:bold; }
.close:hover { color:#ef4444; cursor:pointer; }
.animate { animation:animatezoom .25s; }
@keyframes animatezoom { from{transform:scale(.85)} to{transform:scale(1)} }
.imgcontainer { text-align:center; margin:20px 0 10px; position:relative; }
#id01 .container { padding:16px 20px; }
#id01 label { font-size:.8rem; font-weight:600; color:#334155; display:block; margin-top:10px; margin-bottom:3px; }
#id01 input[type=text],#id01 input[type=number],#id01 input[type=email],#id01 input[type=datetime-local],#id01 select {
  width:100%; padding:9px 13px; border:1.5px solid #e2e8f0;
  border-radius:9px; font-family:Inter,sans-serif; font-size:.82rem; outline:none;
  transition:border-color .18s;
}
#id01 input:focus,#id01 select:focus { border-color:#0d9488; box-shadow:0 0 0 3px rgba(13,148,136,.1); }
#id01 button[type=submit] {
  background:linear-gradient(135deg,#0d9488,#007CBF); color:#fff;
  padding:11px; margin:12px 0 0; border:none; cursor:pointer;
  width:100%; border-radius:10px; font-weight:600; font-size:.875rem; font-family:Inter,sans-serif;
}
#id01 button[type=submit]:hover { background:linear-gradient(135deg,#0f766e,#0d9488); }
.cancelbtn { width:auto!important; padding:9px 18px; background:#ef4444!important; border-radius:8px!important; font-size:.85rem!important; color:#fff!important; border:none; cursor:pointer; font-family:Inter,sans-serif; }

/* tally modal */
#tallyModal .modal-content { border-radius:1rem!important; border:none!important; box-shadow:0 20px 60px rgba(0,0,0,.18)!important; font-family:Inter,sans-serif!important; }
</style>

<!-- STAT CARDS -->
<div class="row g-3 mb-4" id="statsCards">

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="manage question/" class="stat-card" style="--card-accent:#007CBF;">
      <div class="stat-icon-box"><i class="bi bi-patch-question-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->questions() ?></div>
        <div class="stat-label">Total Questions</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="it_admin.php" class="stat-card" style="--card-accent:#38a169;">
      <div class="stat-icon-box"><i class="bi bi-people-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->concept() ?></div>
        <div class="stat-label">Total Students</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="it_admin.php" class="stat-card" style="--card-accent:#dd6b20;">
      <div class="stat-icon-box"><i class="bi bi-person-check-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->user() ?></div>
        <div class="stat-label">Activated</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="admin_expired.php" class="stat-card" style="--card-accent:#e53e3e;">
      <div class="stat-icon-box"><i class="bi bi-person-x-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->expired() ?></div>
        <div class="stat-label">Expired</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="admin_not_activated.php" class="stat-card" style="--card-accent:#805ad5;">
      <div class="stat-icon-box"><i class="bi bi-person-dash-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->bundles() ?></div>
        <div class="stat-label">Not Activated</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="it_admin.php" class="stat-card" style="--card-accent:#0D9488;">
      <div class="stat-icon-box"><i class="bi bi-activity"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->countActiveStudents() ?></div>
        <div class="stat-label">Active Now</div>
      </div>
      <div class="stat-pulse"></div>
    </a>
  </div>

</div>

<!-- STUDENT LIST PANEL -->
<div class="panel">
  <div class="panel-header">
    <div class="panel-title">
      <div class="panel-title-icon"><i class="bi bi-person-lines-fill"></i></div>
      Student List
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <div style="position:relative;">
        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem;pointer-events:none;"></i>
        <input type="text" id="dashSearchInput" placeholder="Search student…"
          style="padding:6px 12px 6px 30px;border:1.5px solid #e2e8f0;border-radius:50px;font-size:.78rem;width:200px;outline:none;font-family:Inter,sans-serif;">
      </div>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm"
        data-bs-toggle="modal" data-bs-target="#tallyModal">
        <i class="bi bi-clipboard-data"></i> Tally
      </button>
    </div>
  </div>

  <!-- add-student modal -->
  <div id="id01" class="modal" style="display:none;">
    <form class="modal-content animate" action="action.php" method="post" enctype="multipart/form-data">
      <div class="imgcontainer">
        <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close">&times;</span>
      </div>
      <div class="container">
        <label>Student Number:</label>
        <input type="number" placeholder="Student number" name="studentnumber">
        <label>Full Name:</label>
        <input type="text" placeholder="Full name" name="fullname">
        <label>Bundle:</label>
        <select name="bundle_name">
          <option value="">Select one…</option>
          <?php
            include("../config.php");
            $q = mysqli_query($con, "SELECT * FROM bundle");
            while ($r = mysqli_fetch_assoc($q)) echo '<option value="'.htmlspecialchars($r['bundle_name']).'">'.htmlspecialchars($r['bundle_name']).'</option>';
          ?>
        </select>
        <label>Group:</label>
        <select name="groupname">
          <option value="">Select one…</option>
          <?php
            $q = mysqli_query($con, "SELECT * FROM grouplist");
            while ($r = mysqli_fetch_assoc($q)) echo '<option value="'.htmlspecialchars($r['groupname']).'">'.htmlspecialchars($r['groupname']).'</option>';
          ?>
        </select>
        <label>Date Enrolled:</label>
        <input type="datetime-local" name="dateenrolled">
        <label>Date Expired:</label>
        <input type="datetime-local" name="dateexpired">
        <label>Email:</label>
        <input type="email" placeholder="student@email.com" name="email">
        <label>Password:</label>
        <input type="text" placeholder="Password" name="password">
        <input type="hidden" name="status" value="user">
        <button type="submit" name="submit">Submit</button>
      </div>
      <div class="container" style="background:#f8fafc;">
        <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancel</button>
      </div>
    </form>
  </div>

  <!-- table -->
  <div style="overflow-x:auto;">
    <table class="table data-table" style="width:100%;margin-bottom:0;">
      <thead>
        <tr>
          <th style="width:20px;"></th>
          <th>Student No.</th>
          <th>Full Name</th>
          <th>Bundle</th>
          <th>Group</th>
          <th>Date Expired</th>
          <th>Type</th>
          <th>Email</th>
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
            $lastLogin = strtotime($rows['lastlogin']);
            $diff      = time() - $lastLogin;
            if (empty($rows['lastlogin']))  $dot = '<span style="display:inline-block;width:9px;height:9px;background:#3b82f6;border-radius:50%;box-shadow:0 0 0 2px rgba(59,130,246,.2);"></span>';
            elseif ($diff < 600)            $dot = '<span style="display:inline-block;width:9px;height:9px;background:#22c55e;border-radius:50%;box-shadow:0 0 0 2px rgba(34,197,94,.2);"></span>';
            else                            $dot = '<span style="display:inline-block;width:9px;height:9px;background:#ef4444;border-radius:50%;box-shadow:0 0 0 2px rgba(239,68,68,.15);"></span>';
            $fn = addslashes($rows['fullname']);
            $id = $rows['id'];
        ?>
        <tr>
          <td><?= $dot ?></td>
          <td><?= htmlspecialchars($rows['studentnumber']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= htmlspecialchars(str_replace('Packege','Package',$rows['bundle_name'])) ?></td>
          <td><?= htmlspecialchars($rows['groupname']) ?></td>
          <td style="white-space:nowrap;"><?= $rows['dateexpired'] ?></td>
          <td>
            <?php if ($rows['type'] == 0): ?>
              <a href="#" onclick="confirmAction('disable','<?= $fn ?>','<?= $id ?>')" class="tbl-badge tbl-badge-enable"><i class="bi bi-check-circle"></i>Enable</a>
            <?php else: ?>
              <a href="#" onclick="confirmAction('enable','<?= $fn ?>','<?= $id ?>')" class="tbl-badge tbl-badge-disable"><i class="bi bi-x-circle"></i>Disable</a>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($rows['email']) ?></td>
          <td><?= htmlspecialchars($rows['password']) ?></td>
          <td>
            <a href="#" onclick="confirmAction('update','<?= $fn ?>','<?= $id ?>')" class="tbl-badge tbl-badge-update"><i class="bi bi-pencil"></i>Edit</a>
          </td>
          <td>
            <a href="#" onclick="confirmAction('delete','<?= $fn ?>','<?= $id ?>')" class="tbl-badge tbl-badge-delete"><i class="bi bi-trash"></i>Del</a>
          </td>
          <td>
            <?php
              if (!function_exists('get_time_ago')) {
                function get_time_ago($ts) {
                  if (empty($ts)) return '<span style="color:#3b82f6;">Never</span>';
                  $d = time() - $ts;
                  if ($d < 600) return '<span style="color:#22c55e;font-weight:500;">Active Now</span>';
                  $c=[12*30*24*3600=>'year',30*24*3600=>'month',24*3600=>'day',3600=>'hour',60=>'min',1=>'sec'];
                  foreach($c as $s=>$str){if($d/$s>=1){$t=round($d/$s);return $t.' '.$str.($t>1?'s ':' ').'ago';}}
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
</div>

<!-- TALLY MODAL -->
<div class="modal fade" id="tallyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:430px;">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;border-radius:1rem 1rem 0 0;padding:14px 20px;">
        <h5 class="modal-title fw-bold" style="font-family:Inter,sans-serif;font-size:.95rem;">
          <i class="bi bi-clipboard-data me-2"></i>Daily Tally
        </h5>
        <div class="d-flex align-items-center gap-2">
          <span id="tallyDate" style="font-size:.72rem;opacity:.85;font-family:Inter,sans-serif;"></span>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body" style="padding:20px 24px;">
        <div id="tallyScreenshots" style="display:none;margin-bottom:16px;">
          <div style="display:flex;gap:8px;flex-direction:column;">
            <div style="border:1px solid #e8eaf2;border-radius:10px;overflow:hidden;">
              <div style="background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;font-size:.73rem;font-weight:600;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;font-family:Inter,sans-serif;">
                <span>Before (Morning)</span>
                <button id="copyBeforeBtn" onclick="copyScreenshot('before')" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:6px;padding:2px 10px;font-size:.68rem;cursor:pointer;font-family:Inter,sans-serif;"><i class="bi bi-clipboard-image"></i> Copy Image</button>
              </div>
              <div style="background:#f8f9fa;padding:6px;text-align:center;min-height:60px;display:flex;align-items:center;justify-content:center;">
                <img id="beforeScreenshot" style="max-width:100%;border-radius:6px;display:none;"/>
                <span id="beforeScreenshotNone" style="font-size:.73rem;color:#aaa;font-family:Inter,sans-serif;">No morning snapshot yet — open dashboard before importing</span>
              </div>
            </div>
            <div style="border:1px solid #e8eaf2;border-radius:10px;overflow:hidden;">
              <div style="background:linear-gradient(135deg,#198754,#2da86e);color:#fff;font-size:.73rem;font-weight:600;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;font-family:Inter,sans-serif;">
                <span>After (Current)</span>
                <button id="copyAfterBtn" onclick="copyScreenshot('after')" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:6px;padding:2px 10px;font-size:.68rem;cursor:pointer;font-family:Inter,sans-serif;"><i class="bi bi-clipboard-image"></i> Copy Image</button>
              </div>
              <div style="background:#f8f9fa;padding:6px;text-align:center;min-height:60px;display:flex;align-items:center;justify-content:center;">
                <img id="afterScreenshot" style="max-width:100%;border-radius:6px;display:none;"/>
                <span id="afterScreenshotLoading" style="font-size:.73rem;color:#aaa;font-family:Inter,sans-serif;"><span class="spinner-border spinner-border-sm text-secondary me-1"></span> Capturing...</span>
              </div>
            </div>
          </div>
        </div>
        <div id="tallyLoading" class="text-center py-4">
          <div class="spinner-border text-secondary" role="status" style="width:1.4rem;height:1.4rem;"></div>
          <p class="text-muted mt-2 mb-0" style="font-size:.82rem;font-family:Inter,sans-serif;">Loading tally...</p>
        </div>
        <div id="tallyContent" style="display:none;">
          <table class="table table-sm mb-0" style="font-size:.845rem;font-family:Inter,sans-serif;">
            <tbody>
              <tr style="background:#e8f4fd;"><td class="fw-semibold" style="color:#007CBF;border-radius:8px 0 0 8px;">Total Morning</td><td class="text-end fw-bold" id="t-morning" style="color:#007CBF;border-radius:0 8px 8px 0;"></td></tr>
              <tr><td class="text-muted ps-3">Total of 1 month</td><td class="text-end fw-semibold" id="t-1month"></td></tr>
              <tr><td class="text-muted ps-3">Total of 2 months</td><td class="text-end fw-semibold" id="t-2months"></td></tr>
              <tr><td class="text-muted ps-3">Total of 3 months</td><td class="text-end fw-semibold" id="t-3months"></td></tr>
              <tr><td class="text-muted ps-3">Total of 6 months</td><td class="text-end fw-semibold" id="t-6months"></td></tr>
              <tr><td class="text-muted ps-3">Total of 12 months</td><td class="text-end fw-semibold" id="t-12months"></td></tr>
              <tr style="background:#f0fdf4;"><td class="ps-3" style="color:#166534;">Total of 1 month FREE</td><td class="text-end fw-semibold" id="t-1free" style="color:#166534;"></td></tr>
              <tr style="background:#f0fdf4;"><td class="ps-3" style="color:#166534;">Total of 2 month FREE</td><td class="text-end fw-semibold" id="t-2free" style="color:#166534;"></td></tr>
              <tr style="border-top:2px solid #e8eaf2;"><td class="fw-semibold">Total Access</td><td class="text-end fw-bold text-success" id="t-access"></td></tr>
              <tr><td class="text-muted ps-3">Deleted (duplicate)</td><td class="text-end fw-semibold text-danger" id="t-deleted"></td></tr>
              <tr><td class="text-muted ps-3">Expired students</td><td class="text-end fw-semibold text-warning" id="t-expired"></td></tr>
              <tr style="background:#e8f4fd;border-top:2px solid #007CBF;"><td class="fw-semibold" style="color:#007CBF;border-radius:8px 0 0 8px;">Total Afternoon</td><td class="text-end fw-bold" id="t-afternoon" style="color:#007CBF;border-radius:0 8px 8px 0;"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid #e9ecef;padding:12px 20px;border-radius:0 0 1rem 1rem;flex-direction:column;gap:8px;">
        <button type="button" id="tallyCopyBtn" class="btn btn-sm w-100 fw-semibold" style="background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;border-radius:50px;padding:8px 0;display:none;font-family:Inter,sans-serif;border:none;">
          <i class="bi bi-clipboard me-1" id="tallyCopyIcon"></i><span id="tallyCopyText">Copy Text</span>
        </button>
        <button type="button" id="tallyResetBtn" class="btn btn-sm w-100 fw-semibold" style="background:#dc3545;color:#fff;border-radius:50px;padding:8px 0;display:none;font-family:Inter,sans-serif;border:none;">
          <i class="bi bi-arrow-counterclockwise me-1" id="tallyResetIcon"></i><span id="tallyResetText">Reset Tally</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function confirmAction(action, name, id) {
  Swal.fire({
    title: action.charAt(0).toUpperCase()+action.slice(1)+' Confirmation',
    text: 'Are you sure you want to '+action+' '+name+'?',
    icon:'warning', showCancelButton:true,
    confirmButtonColor:'#0d9488', confirmButtonText:'Yes', cancelButtonText:'Cancel',
  }).then(r => {
    if (!r.isConfirmed) return;
    if (action==='delete')  location.href='admin_delete.php?id='+id;
    if (action==='update')  location.href='admin_update.php?id='+id;
    if (action==='enable'||action==='disable')
      location.href='type.php?id='+id+'&type='+(action==='enable'?0:1);
  });
}

const TALLY_KEY='studium_tally_cache', MORNING_KEY='studium_morning_ss';
let _afterCanvas=null;
const todayPH=()=>new Date().toLocaleDateString('en-CA',{timeZone:'Asia/Manila'});
const captureCards=()=>html2canvas(document.getElementById('statsCards'),{scale:2,useCORS:true,backgroundColor:null,logging:false});

window.addEventListener('DOMContentLoaded',()=>{
  const today=todayPH();let s=null;
  try{s=JSON.parse(localStorage.getItem(MORNING_KEY));}catch(e){}
  if(!s||s.date!==today){
    setTimeout(()=>captureCards().then(c=>localStorage.setItem(MORNING_KEY,JSON.stringify({date:today,image:c.toDataURL('image/png')}))).catch(()=>{}),900);
  }
});

function copyScreenshot(which){
  const btn=document.getElementById(which==='before'?'copyBeforeBtn':'copyAfterBtn');
  const orig=btn.innerHTML;
  function doWrite(canvas){
    canvas.toBlob(blob=>{
      try{navigator.clipboard.write([new ClipboardItem({'image/png':blob})]).then(()=>{btn.innerHTML='<i class="bi bi-clipboard-check"></i> Copied!';setTimeout(()=>btn.innerHTML=orig,2000);}).catch(()=>alert('Copy failed.'));}catch(e){alert('Clipboard API not supported.');}
    },'image/png');
  }
  if(which==='before'){
    let s=null;try{s=JSON.parse(localStorage.getItem(MORNING_KEY));}catch(e){}
    if(!s?.image){alert('No morning snapshot yet.');return;}
    const img=new Image();img.onload=function(){const c=document.createElement('canvas');c.width=img.width;c.height=img.height;c.getContext('2d').drawImage(img,0,0);doWrite(c);};img.src=s.image;
  }else{_afterCanvas?doWrite(_afterCanvas):alert('Not ready yet.');}
}

function buildCopyText(d){
  return['# '+new Date().toLocaleDateString('en-US',{timeZone:'Asia/Manila',month:'long',day:'numeric',year:'numeric'}),
    'Total Morning: '+d.morning_count,'Total of 1 month: '+d.added_1month,'Total of 2 months: '+(d.added_2months??0),
    'Total of 3 months: '+d.added_3months,'Total of 6 months: '+d.added_6months,'Total of 12 months: '+d.added_12months,
    'Total of 1 month FREE: '+d.added_1month_free,'Total of 2 month FREE: '+d.added_2months_free,
    'Total Access: '+d.total_access,'Total of Deleted due to duplicate account: '+d.deleted_today,
    'Total of Expired students: '+d.expired_count,'Total Afternoon: '+d.total_afternoon].join('\n');
}

function renderTally(d){
  const m={morning:'morning_count','1month':'added_1month','2months':'added_2months','3months':'added_3months','6months':'added_6months','12months':'added_12months','1free':'added_1month_free','2free':'added_2months_free',access:'total_access',deleted:'deleted_today',expired:'expired_count',afternoon:'total_afternoon'};
  for(const[k,v]of Object.entries(m)){const el=document.getElementById('t-'+k);if(el)el.textContent=d[v]??0;}
  const td=document.getElementById('tallyDate');if(td)td.textContent=todayPH();
  document.getElementById('tallyLoading').style.display='none';
  document.getElementById('tallyContent').style.display='block';
  document.getElementById('tallyCopyBtn').style.display='block';
  document.getElementById('tallyResetBtn').style.display='block';
  localStorage.setItem(TALLY_KEY,JSON.stringify({date:todayPH(),data:d}));
}

function loadTally(){
  ['tallyContent','tallyCopyBtn','tallyResetBtn','tallyScreenshots'].forEach(id=>document.getElementById(id).style.display='none');
  document.getElementById('tallyLoading').style.display='block';
  try{const c=JSON.parse(localStorage.getItem(TALLY_KEY));if(c?.date===todayPH())renderTally(c.data);}catch(e){}
  fetch('tally_get.php').then(r=>r.json()).then(renderTally).catch(()=>{
    document.getElementById('tallyLoading').innerHTML='<p class="text-danger mb-0" style="font-size:.82rem;">Failed to load tally data.</p>';
  });
  const bImg=document.getElementById('beforeScreenshot'),bNone=document.getElementById('beforeScreenshotNone');
  let s=null;try{s=JSON.parse(localStorage.getItem(MORNING_KEY));}catch(e){}
  if(s?.date===todayPH()&&s.image){bImg.src=s.image;bImg.style.display='block';bNone.style.display='none';}
  else{bImg.style.display='none';bNone.style.display='inline';}
  const aImg=document.getElementById('afterScreenshot'),aLoad=document.getElementById('afterScreenshotLoading');
  aImg.style.display='none';aLoad.style.display='inline';_afterCanvas=null;
  document.getElementById('tallyScreenshots').style.display='block';
  captureCards().then(c=>{_afterCanvas=c;aImg.src=c.toDataURL('image/png');aImg.style.display='block';aLoad.style.display='none';}).catch(()=>{aLoad.innerHTML='<span style="color:red;font-size:.73rem;">Capture failed</span>';});
}

// ── Dashboard student table search ───────────────────────────
$(document).ready(function () {
  var dashTable = $('.data-table').DataTable({
    order: [],
    pageLength: 25,
    dom: 'tip',
    language: {
      info:      "Showing _START_–_END_ of _TOTAL_ students",
      infoEmpty: "No students found",
      paginate:  { previous: '← Prev', next: 'Next →' }
    }
  });
  document.getElementById('dashSearchInput').addEventListener('input', function () {
    dashTable.search(this.value).draw();
  });
});

document.getElementById('tallyModal')?.addEventListener('show.bs.modal',()=>{
  try{const c=JSON.parse(localStorage.getItem(TALLY_KEY));if(c?.date!==todayPH())localStorage.removeItem(TALLY_KEY);}catch(e){}
  loadTally();
});
document.getElementById('tallyCopyBtn')?.addEventListener('click',()=>{
  try{const c=JSON.parse(localStorage.getItem(TALLY_KEY));if(!c?.data)return;
    navigator.clipboard.writeText(buildCopyText(c.data)).then(()=>{
      document.getElementById('tallyCopyIcon').className='bi bi-clipboard-check me-1';
      document.getElementById('tallyCopyText').textContent='Copied!';
      setTimeout(()=>{document.getElementById('tallyCopyIcon').className='bi bi-clipboard me-1';document.getElementById('tallyCopyText').textContent='Copy Text';},2000);
    });}catch(e){}
});
document.getElementById('tallyResetBtn')?.addEventListener('click',()=>{
  if(!confirm('Reset all tally counts to 0?'))return;
  const ri=document.getElementById('tallyResetIcon'),rt=document.getElementById('tallyResetText'),rb=document.getElementById('tallyResetBtn');
  ri.className='bi bi-hourglass-split me-1';rt.textContent='Resetting...';rb.disabled=true;
  fetch('tally_reset.php',{method:'POST'}).then(r=>r.json()).then(res=>{
    if(res.ok){localStorage.removeItem(TALLY_KEY);loadTally();}else alert('Reset failed.');
  }).catch(()=>alert('Request failed.')).finally(()=>{ri.className='bi bi-arrow-counterclockwise me-1';rt.textContent='Reset Tally';rb.disabled=false;});
});
</script>

<?php require_once 'partials/footer.php'; ?>
