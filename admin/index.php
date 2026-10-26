<?php
include("count.php");
$count = new count;

$ap_base    = '';
$active_nav = 'dashboard';
$ap_title   = 'Dashboard';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>';
require_once 'partials/sidebar.php';
?>

<!-- dashboard component styles moved to tw.css (Tailwind build) -->

<!-- STAT CARDS -->
<div class="row g-3 mb-4" id="statsCards">

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="manage question/" class="stat-card tw-bg-[#007CBF]">
      <div class="stat-icon-box"><i class="bi bi-patch-question-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->questions() ?></div>
        <div class="stat-label">Total Questions</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="students.php?filter=all" class="stat-card tw-bg-[#38a169]">
      <div class="stat-icon-box"><i class="bi bi-people-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->concept() ?></div>
        <div class="stat-label">Total Students</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="students.php?filter=activated" class="stat-card tw-bg-[#dd6b20]">
      <div class="stat-icon-box"><i class="bi bi-person-check-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->user() ?></div>
        <div class="stat-label">Activated</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="students.php?filter=expired" class="stat-card tw-bg-[#e53e3e]">
      <div class="stat-icon-box"><i class="bi bi-person-x-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->expired() ?></div>
        <div class="stat-label">Expired</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="students.php?filter=notactivated" class="stat-card tw-bg-[#805ad5]">
      <div class="stat-icon-box"><i class="bi bi-person-dash-fill"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $count->bundles() ?></div>
        <div class="stat-label">Not Activated</div>
      </div>
    </a>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <a href="students.php?filter=active" class="stat-card tw-bg-[#0D9488]">
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
    <div class="tw-flex tw-gap-2 tw-flex-wrap tw-items-center">
      <div class="tw-relative">
        <i class="bi bi-search tw-absolute tw-left-[10px] tw-top-1/2 -tw-translate-y-1/2 tw-text-[#94a3b8] tw-text-[.85rem] tw-pointer-events-none"></i>
        <input type="text" id="dashSearchInput" placeholder="Search student…"
          class="tw-py-[6px] tw-pr-3 tw-pl-[30px] tw-border-[1.5px] tw-border-solid tw-border-[#e2e8f0] tw-rounded-full tw-text-[.78rem] tw-w-[200px] tw-outline-none tw-font-sans">
      </div>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm"
        data-bs-toggle="modal" data-bs-target="#tallyModal">
        <i class="bi bi-clipboard-data"></i> Tally
      </button>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm position-relative"
        data-bs-toggle="modal" data-bs-target="#renewalModal" id="renewalBtn">
        <i class="bi bi-arrow-repeat"></i> Renewals
        <span id="renewalBadge" class="badge bg-danger" style="display:none;">0</span>
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
          $query = "SELECT * FROM `login` WHERE status='user' AND groupname!='Admin' ORDER BY id DESC";
          $data  = mysqli_query($con, $query);

          while ($rows = mysqli_fetch_array($data)):
            $lastLogin = !empty($rows['lastlogin']) ? strtotime($rows['lastlogin']) : 0;
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
          <td style="white-space:nowrap;"><?= !empty($rows['dateexpired']) ? htmlspecialchars($rows['dateexpired']) : '<span style="color:#94a3b8;">—</span>' ?></td>
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
              echo get_time_ago(!empty($rows['lastlogin']) ? strtotime($rows['lastlogin']) : 0);
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
  <div class="modal-dialog modal-dialog-centered" style="max-width:700px;">
    <div class="modal-content" style="border-radius:1rem!important;border:none!important;box-shadow:0 20px 60px rgba(0,0,0,.18)!important;font-family:Inter,sans-serif!important;">

      <!-- Header -->
      <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;border-radius:1rem 1rem 0 0;padding:10px 18px;border:none;align-items:center;">
        <div>
          <h5 class="modal-title fw-bold" style="font-family:Inter,sans-serif;font-size:.92rem;margin:0;display:flex;align-items:center;">
            <i class="bi bi-clipboard-data me-2"></i>Daily Tally
          </h5>
          <span id="tallyDate" style="font-size:.7rem;opacity:.75;font-family:Inter,sans-serif;padding-left:26px;display:block;margin-top:2px;"></span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body" style="padding:14px 18px;overflow-y:auto;max-height:calc(100vh - 200px);">

        <!-- Loading state -->
        <div id="tallyLoading" class="text-center py-4">
          <div class="spinner-border text-secondary" role="status" style="width:1.4rem;height:1.4rem;"></div>
          <p class="text-muted mt-2 mb-0" style="font-size:.82rem;font-family:Inter,sans-serif;">Loading tally...</p>
        </div>

        <!-- 2-column grid: left = screenshots, right = stats -->
        <div id="tallyContentGrid" style="display:none;grid-template-columns:1fr 1fr;gap:14px;align-items:start;">

          <!-- LEFT: Screenshots -->
          <div id="tallyScreenshots" style="display:flex;flex-direction:column;gap:8px;">
            <!-- Before (Morning) -->
            <div style="border:1px solid #e8eaf2;border-radius:8px;overflow:hidden;">
              <div style="background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;font-size:.7rem;font-weight:600;padding:5px 10px;display:flex;justify-content:space-between;align-items:center;font-family:Inter,sans-serif;">
                <span><i class="bi bi-sunrise me-1"></i>Before (Morning)</span>
                <button id="copyBeforeBtn" onclick="copyScreenshot('before')" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:5px;padding:2px 8px;font-size:.63rem;cursor:pointer;font-family:Inter,sans-serif;">
                  <i class="bi bi-clipboard-image"></i> Copy
                </button>
              </div>
              <div style="background:#f8f9fa;padding:5px;text-align:center;min-height:70px;display:flex;align-items:center;justify-content:center;">
                <img id="beforeScreenshot" style="max-width:100%;max-height:110px;border-radius:5px;display:none;"/>
                <span id="beforeScreenshotNone" style="font-size:.7rem;color:#aaa;font-family:Inter,sans-serif;">No morning snapshot yet</span>
              </div>
            </div>
            <!-- After (Current) -->
            <div style="border:1px solid #e8eaf2;border-radius:8px;overflow:hidden;">
              <div style="background:linear-gradient(135deg,#198754,#2da86e);color:#fff;font-size:.7rem;font-weight:600;padding:5px 10px;display:flex;justify-content:space-between;align-items:center;font-family:Inter,sans-serif;">
                <span><i class="bi bi-sun me-1"></i>After (Current)</span>
                <button id="copyAfterBtn" onclick="copyScreenshot('after')" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:5px;padding:2px 8px;font-size:.63rem;cursor:pointer;font-family:Inter,sans-serif;">
                  <i class="bi bi-clipboard-image"></i> Copy
                </button>
              </div>
              <div style="background:#f8f9fa;padding:5px;text-align:center;min-height:70px;display:flex;align-items:center;justify-content:center;">
                <img id="afterScreenshot" style="max-width:100%;max-height:110px;border-radius:5px;display:none;"/>
                <span id="afterScreenshotLoading" style="font-size:.7rem;color:#aaa;font-family:Inter,sans-serif;">
                  <span class="spinner-border spinner-border-sm text-secondary me-1"></span> Capturing...
                </span>
              </div>
            </div>
          </div>

          <!-- RIGHT: Tally stats table -->
          <div id="tallyContent">
            <table class="table table-sm mb-0" style="font-size:.8rem;font-family:Inter,sans-serif;">
              <tbody>
                <tr style="background:#e8f4fd;">
                  <td class="fw-semibold" style="color:#007CBF;padding:7px 10px;">Total Morning</td>
                  <td class="text-end fw-bold" id="t-morning" style="color:#007CBF;padding:7px 10px;"></td>
                </tr>
                <tr><td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">1 month</td><td class="text-end fw-semibold" id="t-1month" style="padding:5px 10px;"></td></tr>
                <tr><td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">2 months</td><td class="text-end fw-semibold" id="t-2months" style="padding:5px 10px;"></td></tr>
                <tr><td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">3 months</td><td class="text-end fw-semibold" id="t-3months" style="padding:5px 10px;"></td></tr>
                <tr><td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">6 months</td><td class="text-end fw-semibold" id="t-6months" style="padding:5px 10px;"></td></tr>
                <tr><td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">12 months</td><td class="text-end fw-semibold" id="t-12months" style="padding:5px 10px;"></td></tr>
                <tr style="background:#f0fdf4;">
                  <td style="color:#166534;padding:5px 10px 5px 18px;font-size:.75rem;">1 month FREE</td>
                  <td class="text-end fw-semibold" id="t-1free" style="color:#166534;padding:5px 10px;"></td>
                </tr>
                <tr style="background:#f0fdf4;">
                  <td style="color:#166534;padding:5px 10px 5px 18px;font-size:.75rem;">2 month FREE</td>
                  <td class="text-end fw-semibold" id="t-2free" style="color:#166534;padding:5px 10px;"></td>
                </tr>
                <tr style="border-top:2px solid #e8eaf2;">
                  <td class="fw-semibold" style="padding:7px 10px;">Total Access</td>
                  <td class="text-end fw-bold text-success" id="t-access" style="padding:7px 10px;"></td>
                </tr>
                <tr>
                  <td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">Deleted (duplicate)</td>
                  <td class="text-end fw-semibold text-danger" id="t-deleted" style="padding:5px 10px;"></td>
                </tr>
                <tr>
                  <td class="text-muted" style="padding:5px 10px 5px 18px;font-size:.75rem;">Expired students</td>
                  <td class="text-end fw-semibold text-warning" id="t-expired" style="padding:5px 10px;"></td>
                </tr>
                <tr style="background:#e8f4fd;border-top:2px solid #007CBF;">
                  <td class="fw-semibold" style="color:#007CBF;padding:7px 10px;">Total Afternoon</td>
                  <td class="text-end fw-bold" id="t-afternoon" style="color:#007CBF;padding:7px 10px;"></td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Footer: two buttons side by side -->
      <div class="modal-footer" style="border-top:1px solid #e9ecef;padding:10px 18px;border-radius:0 0 1rem 1rem;display:flex;gap:8px;justify-content:stretch;">
        <button type="button" id="tallyCopyBtn" class="btn btn-sm fw-semibold" style="flex:1;background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;border-radius:50px;padding:8px 0;display:none;font-family:Inter,sans-serif;border:none;">
          <i class="bi bi-clipboard me-1" id="tallyCopyIcon"></i><span id="tallyCopyText">Copy Text</span>
        </button>
        <button type="button" id="tallyResetBtn" class="btn btn-sm fw-semibold" style="flex:1;background:#dc3545;color:#fff;border-radius:50px;padding:8px 0;display:none;font-family:Inter,sans-serif;border:none;">
          <i class="bi bi-arrow-counterclockwise me-1" id="tallyResetIcon"></i><span id="tallyResetText">Reset Tally</span>
        </button>
      </div>

    </div>
  </div>
</div>

<!-- RENEWAL MODAL -->
<div class="modal fade" id="renewalModal" tabindex="-1" aria-labelledby="renewalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:1rem!important;border:none!important;box-shadow:0 20px 60px rgba(0,0,0,.18)!important;font-family:Inter,sans-serif!important;">
      <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#007CBF);color:#fff;border-radius:1rem 1rem 0 0;padding:12px 18px;border:none;">
        <h5 class="modal-title fw-bold" id="renewalModalLabel" style="font-family:Inter,sans-serif;font-size:.92rem;margin:0;display:flex;align-items:center;">
          <i class="bi bi-arrow-repeat me-2"></i>Pending Renewals
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding:18px 22px;font-family:Inter,sans-serif;">

        <!-- Rows present in the sheet that could not be actioned. Shown so a
             renewal is never dropped without the admin being told why. -->
        <div id="renewalSkipped" class="mb-3" style="display:none;background:#fff7ed;border:1px solid #fed7aa;border-radius:.6rem;padding:10px 14px;font-size:.8rem;color:#9a3412;">
          <div style="font-weight:600;margin-bottom:4px;"><i class="bi bi-exclamation-triangle-fill me-1"></i><span id="renewalSkippedTitle"></span></div>
          <ul id="renewalSkippedList" style="margin:0;padding-left:18px;"></ul>
        </div>

        <div id="renewalLoading" class="text-center py-4">
          <div class="spinner-border text-secondary" role="status" style="width:1.5rem;height:1.5rem;"></div>
          <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">Loading pending renewals...</p>
        </div>

        <div id="renewalEmpty" class="text-center py-4" style="display:none;">
          <p class="text-muted mb-0">No pending renewals 🎉</p>
        </div>

        <div id="renewalError" class="text-center py-4" style="display:none;">
          <p class="text-danger mb-0" id="renewalErrorText">Failed to load pending renewals.</p>
        </div>

        <div id="renewalContent" style="display:none;overflow-x:auto;">
          <table class="table mb-0" style="font-size:.82rem;">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Student No.</th>
                <th>Gmail</th>
                <th>Subscription</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="renewalTableBody"></tbody>
          </table>
        </div>

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
  document.getElementById('tallyContentGrid').style.display='grid';
  document.getElementById('tallyCopyBtn').style.display='block';
  document.getElementById('tallyResetBtn').style.display='block';
  localStorage.setItem(TALLY_KEY,JSON.stringify({date:todayPH(),data:d}));
}

function loadTally(){
  document.getElementById('tallyContentGrid').style.display='none';
  document.getElementById('tallyCopyBtn').style.display='none';
  document.getElementById('tallyResetBtn').style.display='none';
  document.getElementById('tallyLoading').style.display='block';
  // Load morning screenshot
  const bImg=document.getElementById('beforeScreenshot'),bNone=document.getElementById('beforeScreenshotNone');
  let s=null;try{s=JSON.parse(localStorage.getItem(MORNING_KEY));}catch(e){}
  if(s?.date===todayPH()&&s.image){bImg.src=s.image;bImg.style.display='block';bNone.style.display='none';}
  else{bImg.style.display='none';bNone.style.display='inline';}
  // Start capturing current screenshot
  const aImg=document.getElementById('afterScreenshot'),aLoad=document.getElementById('afterScreenshotLoading');
  aImg.style.display='none';aLoad.style.display='inline';_afterCanvas=null;
  captureCards().then(c=>{_afterCanvas=c;aImg.src=c.toDataURL('image/png');aImg.style.display='block';aLoad.style.display='none';}).catch(()=>{aLoad.innerHTML='<span style="color:red;font-size:.7rem;">Capture failed</span>';});
  // Try cache first, then fetch fresh
  try{const c=JSON.parse(localStorage.getItem(TALLY_KEY));if(c?.date===todayPH())renderTally(c.data);}catch(e){}
  fetch('tally_get.php').then(r=>r.json()).then(renderTally).catch(()=>{
    document.getElementById('tallyLoading').innerHTML='<p class="text-danger mb-0" style="font-size:.82rem;">Failed to load tally data.</p>';
  });
}

// ── Dashboard student table search ───────────────────────────
$(document).ready(function () {
  var dashTable = $('.data-table').DataTable({
    order: [],
    pageLength: 20,
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

// ── Pending Renewals ─────────────────────────────────────────
var renewalData = [];
var renewalSkippedData = [];

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, function (c) {
    return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
  });
}

function updateRenewalBadge(count) {
  var badge = document.getElementById('renewalBadge');
  if (!badge) return;
  if (count > 0) { badge.textContent = count; badge.style.display = 'inline-block'; }
  else           { badge.style.display = 'none'; }
}

// Rows the sheet contains but the panel cannot action (blank student number,
// unreadable subscription text). Listing them stops a renewal disappearing.
function renderRenewalSkipped() {
  var box = document.getElementById('renewalSkipped');
  if (!box) return;

  if (!renewalSkippedData.length) { box.style.display = 'none'; return; }

  document.getElementById('renewalSkippedTitle').textContent =
    renewalSkippedData.length + ' sheet row' + (renewalSkippedData.length === 1 ? '' : 's') +
    ' could not be read — please fix them in the STUDIUM-RENEW sheet';

  document.getElementById('renewalSkippedList').innerHTML =
    renewalSkippedData.map(function (r) {
      var who = r.full_name ? ' (' + escapeHtml(r.full_name) + ')' : '';
      return '<li>Row ' + escapeHtml(r.sheet_row) + who + ' — ' + escapeHtml(r.reason) + '</li>';
    }).join('');

  box.style.display = 'block';
}

function renderRenewalList() {
  var empty   = document.getElementById('renewalEmpty');
  var error   = document.getElementById('renewalError');
  var content = document.getElementById('renewalContent');
  var tbody   = document.getElementById('renewalTableBody');

  document.getElementById('renewalLoading').style.display = 'none';
  error.style.display = 'none';

  renderRenewalSkipped();

  if (renewalData.length === 0) {
    content.style.display = 'none';
    empty.style.display   = 'block';
    return;
  }

  empty.style.display   = 'none';
  content.style.display = 'block';

  tbody.innerHTML = renewalData.map(function (s, i) {
    return '<tr>' +
      '<td style="font-weight:500;">' + escapeHtml(s.full_name) + '</td>' +
      '<td>' + escapeHtml(s.student_number) + '</td>' +
      '<td>' + escapeHtml(s.gmail) + '</td>' +
      '<td>' + escapeHtml(s.subscription_label) + '</td>' +
      '<td><a href="#" class="tbl-badge tbl-badge-update" onclick="confirmGiveAccess(' + i + '); return false;">' +
      '<i class="bi bi-unlock"></i>Give Access</a></td>' +
      '</tr>';
  }).join('');
}

function loadRenewalPending() {
  document.getElementById('renewalLoading').style.display = 'block';
  document.getElementById('renewalEmpty').style.display   = 'none';
  document.getElementById('renewalError').style.display   = 'none';
  document.getElementById('renewalSkipped').style.display = 'none';
  document.getElementById('renewalContent').style.display = 'none';

  fetch('renewal_pending.php')
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d.ok) {
        document.getElementById('renewalLoading').style.display = 'none';
        document.getElementById('renewalError').style.display   = 'block';
        document.getElementById('renewalErrorText').textContent = d.error || 'Failed to load pending renewals.';
        return;
      }
      renewalData = d.students || [];
      renewalSkippedData = d.skipped || [];
      updateRenewalBadge(d.count || 0);
      renderRenewalList();
    })
    .catch(function () {
      document.getElementById('renewalLoading').style.display = 'none';
      document.getElementById('renewalError').style.display   = 'block';
    });
}

function confirmGiveAccess(i) {
  var s = renewalData[i];
  if (!s) return;

  Swal.fire({
    title: 'Give Access',
    html: 'Give <b>' + escapeHtml(s.full_name) + '</b> access for <b>' + escapeHtml(s.subscription_label) + '</b>?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0d9488',
    confirmButtonText: 'Yes, give access',
    cancelButtonText: 'Cancel',
  }).then(function (result) {
    if (!result.isConfirmed) return;

    fetch('renewal_give_access.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        row_index: s.row_index,
        student_number: s.student_number,
        months: s.months,
        free: s.free
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) {
          // Access is already granted at this point. If the sheet row could not be
          // highlighted the entry will show up as pending again, so warn the admin
          // to mark it by hand rather than giving access a second time.
          if (res.sheet_marked === false) {
            Swal.fire({
              title: 'Access granted — sheet not updated',
              html: '<b>' + escapeHtml(s.full_name) + '</b> now has access until <b>' +
                    escapeHtml(res.new_expiration) + '</b>.<br><br>' +
                    'The STUDIUM-RENEW sheet could not be marked, so this row will appear ' +
                    'as pending again. Highlight row ' + escapeHtml(s.sheet_row) + ' manually — ' +
                    'do <b>not</b> give access twice.',
              icon: 'warning', confirmButtonColor: '#0d9488'
            });
          } else {
            Swal.fire({ title: 'Access granted', text: s.full_name + ' now has access until ' + res.new_expiration + '.', icon: 'success', confirmButtonColor: '#0d9488' });
          }
          loadRenewalPending();
        } else {
          Swal.fire({ title: 'Failed', text: res.error || 'Could not give access.', icon: 'error', confirmButtonColor: '#0d9488' });
        }
      })
      .catch(function () {
        Swal.fire({ title: 'Error', text: 'Request failed.', icon: 'error', confirmButtonColor: '#0d9488' });
      });
  });
}

// Load badge on page load, refresh list when modal opens
document.addEventListener('DOMContentLoaded', loadRenewalPending);
document.getElementById('renewalModal')?.addEventListener('show.bs.modal', loadRenewalPending);
</script>

<?php require_once 'partials/footer.php'; ?>
