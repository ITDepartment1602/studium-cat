<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila');
include_once '../config.php';
include_once 'tally_helper.php';
mysqli_query($con, "SET time_zone = '+08:00'");
include("count.php");
$count = new count;

// ── CSV processing (POST to self) ────────────────────────────
$flash = '';

// Handle redirect back from import_action.php (if ever used)
if (!$flash && isset($_GET['import'])) {
    if ($_GET['import'] === 'success') {
        $rows = (int)($_GET['rows'] ?? 0);
        $flash = "success|Successfully imported {$rows} student(s).";
    } elseif ($_GET['import'] === 'error') {
        $flash = "error|" . htmlspecialchars($_GET['msg'] ?? 'Import failed.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {

    // Guard: file upload error
    if ($_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        $flash = "error|File upload failed (code: {$_FILES['csvFile']['error']}). Check php.ini upload_max_filesize / post_max_size.";
    } else {
        $file   = $_FILES['csvFile']['tmp_name'];
        $handle = fopen($file, 'r');
        fgetcsv($handle); // skip header

        if (!tally_read()) {
            $morningRes = mysqli_query($con, "SELECT COUNT(*) as cnt FROM login");
            $morningRow = mysqli_fetch_assoc($morningRes);
            tally_create((int)$morningRow['cnt']);
        }

        $sql = "INSERT INTO login (
            studentnumber, fullname, bundle_name, groupname,
            dateenrolled, dateexpired, subMonth, type,
            email, password, status, loginstatus, lastlogin, examTaken
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            $flash = "error|DB prepare failed: " . htmlspecialchars($con->error);
        } else {
            $rowCount  = 0;
            $skipCount = 0;

            $batch = [
                'added_1month'       => 0,
                'added_2months'      => 0,
                'added_3months'      => 0,
                'added_6months'      => 0,
                'added_12months'     => 0,
                'added_1month_free'  => 0,
                'added_2months_free' => 0,
            ];

            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 8) continue;

                $studentnumber = $data[1];
                $fullname      = $data[2];
                $bundle_name   = $data[3];
                $rawGroup      = trim($data[4]);
                $groupname     = formatGroupStudent($rawGroup);
                $dateenrolled  = date('Y-m-d H:i:s');
                $dateexpired   = null;
                $subMonth      = $data[5];
                $type          = null;
                $email         = $data[6];
                $password      = $data[7];
                $status        = 'user';
                $loginstatus   = null;
                $lastlogin     = null;
                $examTaken     = 0;

                $stmt->bind_param(
                    "ssssssssssssss",
                    $studentnumber, $fullname, $bundle_name, $groupname,
                    $dateenrolled, $dateexpired, $subMonth, $type,
                    $email, $password, $status, $loginstatus, $lastlogin, $examTaken
                );

                if ($stmt->execute()) {
                    $rowCount++;
                    $isFree = stripos($groupname, 'FREE') !== false;
                    $months = (int)$subMonth;
                    if ($isFree) {
                        if ($months === 1)     $batch['added_1month_free']++;
                        elseif ($months === 2) $batch['added_2months_free']++;
                    } else {
                        if ($months === 1)      $batch['added_1month']++;
                        elseif ($months === 2)  $batch['added_2months']++;
                        elseif ($months === 3)  $batch['added_3months']++;
                        elseif ($months === 6)  $batch['added_6months']++;
                        elseif ($months === 12) $batch['added_12months']++;
                    }
                } else {
                    $skipCount++;
                    if ($skipCount === 1) {
                        // Capture first failure reason to surface in flash
                        $firstErr = $stmt->error;
                    }
                }
            }

            fclose($handle);
            $stmt->close();

            if ($rowCount > 0) tally_increment($batch);

            if ($rowCount > 0 && $skipCount === 0) {
                $flash = "success|Successfully imported {$rowCount} student(s).";
            } elseif ($rowCount > 0) {
                $flash = "success|Imported {$rowCount} student(s). {$skipCount} row(s) skipped (duplicate email/ID or DB error).";
            } else {
                $errDetail = !empty($firstErr) ? " DB error: " . htmlspecialchars($firstErr) : '';
                $flash = "error|No rows were imported. {$skipCount} row(s) failed.{$errDetail}";
            }
        }
    }
}

function formatGroupStudent($value) {
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return date('F Y', strtotime($value));
    if (preg_match('/^([A-Za-z]{3,})-(\d{2})$/', $value, $m)) {
        $ts = strtotime("01-{$m[1]}-20{$m[2]}");
        return $ts ? date('F Y', $ts) : $value;
    }
    if (preg_match('/^(\d{2})-([A-Za-z]{3,})$/', $value, $m)) {
        $ts = strtotime("01-{$m[2]}-20{$m[1]}");
        return $ts ? date('F Y', $ts) : $value;
    }
    return $value ?: "Unassigned";
}

// ── Handle flash from email send redirect ────────────────────
if (!$flash && isset($_GET['sent'])) {
    $s = (int)$_GET['sent'];
    $f = (int)($_GET['failed'] ?? 0);
    $flash = "success|Sent {$s} email(s)" . ($f ? ", {$f} failed." : ".");
}

// ── Ensure email_sent_status table exists ────────────────────
$con->query("CREATE TABLE IF NOT EXISTS `email_sent_status` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `sent_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// ── Counts for table ─────────────────────────────────────────
$all_res   = $con->query("SELECT COUNT(*) as cnt FROM login WHERE dateenrolled >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
$all_count = (int)$all_res->fetch_assoc()['cnt'];

$not_sent_res   = $con->query("SELECT COUNT(*) as cnt FROM login l WHERE l.dateenrolled >= DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOT EXISTS (SELECT 1 FROM email_sent_status e WHERE e.student_id = l.id)");
$not_sent_count = (int)$not_sent_res->fetch_assoc()['cnt'];

// ── Students data ────────────────────────────────────────────
$rows_data = [];
$res = $con->query("
    SELECT l.id, l.studentnumber, l.fullname, l.dateenrolled, l.subMonth, l.dateexpired,
           CASE WHEN e.student_id IS NOT NULL THEN 1 ELSE 0 END as email_sent
    FROM login l
    LEFT JOIN email_sent_status e ON e.student_id = l.id
    WHERE l.dateenrolled >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ORDER BY l.id DESC
");
while ($r = $res->fetch_assoc()) $rows_data[] = $r;

$ap_base    = '';
$active_nav = 'add_student';
$ap_title   = 'Add Student';
$ap_extra_head = '
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>';
require_once 'partials/sidebar.php';
?>

<style>
.as-filter-bar {
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 18px;
}
.as-tab {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 16px; border-radius: 50px; font-size: .78rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
  cursor: pointer; transition: all .16s; text-decoration: none;
}
.as-tab:hover { border-color: var(--ap-accent); color: var(--ap-accent); }
.as-tab.active {
  background: linear-gradient(135deg, var(--ap-accent), var(--ap-primary));
  color: #fff; border-color: transparent;
  box-shadow: 0 2px 8px rgba(13,148,136,.25);
}
.as-tab-count {
  background: rgba(255,255,255,.28); border-radius: 50px;
  padding: 1px 8px; font-size: .7rem; font-weight: 700;
}
.as-tab:not(.active) .as-tab-count { background: rgba(13,148,136,.1); color: var(--ap-accent); }

.as-search-wrap { position: relative; margin-left: auto; }
.as-search-wrap i {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  color: #94a3b8; font-size: .9rem; pointer-events: none;
}
.as-search-input {
  padding: 7px 14px 7px 34px; border: 1.5px solid #e2e8f0;
  border-radius: 50px; font-family: Inter, sans-serif; font-size: .8rem;
  color: #0f172a; outline: none; width: 240px; background: #fff;
  transition: border-color .18s, box-shadow .18s;
}
.as-search-input:focus { border-color: var(--ap-accent); box-shadow: 0 0 0 3px rgba(13,148,136,.1); }

.as-panel {
  background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;
  box-shadow: 0 1px 4px rgba(15,23,42,.05); overflow: hidden;
}
.as-panel-header {
  padding: 12px 20px; border-bottom: 1px solid #f1f5f9;
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.as-panel-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.as-panel-icon {
  width: 32px; height: 32px; border-radius: 9px;
  background: linear-gradient(135deg, var(--ap-accent), var(--ap-primary));
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .88rem; flex-shrink: 0;
}
.as-panel-title { font-size: .92rem; font-weight: 700; color: #0f172a; }
.as-total-badge {
  font-size: .72rem; font-weight: 600; color: #64748b;
  background: #f1f5f9; padding: 3px 12px; border-radius: 50px;
}
.as-select-row {
  padding: 10px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
  display: flex; align-items: center; gap: 10px;
}
.as-select-row label {
  display: flex; align-items: center; gap: 8px;
  font-size: .8rem; font-weight: 500; color: #475569; cursor: pointer; margin: 0;
}
.as-select-row label input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--ap-accent); cursor: pointer; }
.as-selected-count { font-size: .75rem; font-weight: 600; color: #94a3b8; }

.as-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.as-table thead th {
  background: var(--ap-primary) !important; color: #fff !important;
  font-size: .67rem !important; font-weight: 700 !important;
  text-transform: uppercase !important; letter-spacing: .8px !important;
  padding: 11px 14px !important; border: none !important; white-space: nowrap;
}
.as-table thead th:first-child { width: 40px; text-align: center; }
.as-table tbody td {
  padding: 11px 14px; border-bottom: 1px solid #f1f5f9;
  vertical-align: middle; color: #334155;
}
.as-table tbody tr:last-child td { border-bottom: none; }
.as-table tbody tr:hover td { background: #f8fafc; }
.as-table td:first-child { text-align: center; }
.as-table td input[type=checkbox] { width: 15px; height: 15px; accent-color: var(--ap-accent); cursor: pointer; }
.as-serial { color: #94a3b8; font-size: .74rem; font-weight: 600; }
.as-student-id { color: var(--ap-primary); font-weight: 600; font-size: .82rem; }
.as-name { font-weight: 600; color: #0f172a; }
.as-date { font-size: .76rem; color: #64748b; white-space: nowrap; }
.as-badge-sent {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 12px; border-radius: 50px; font-size: .72rem; font-weight: 600;
  background: #dcfce7; color: #16a34a; white-space: nowrap;
}
.as-btn-send {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 13px; border-radius: 50px; font-size: .72rem; font-weight: 600;
  background: linear-gradient(135deg, var(--ap-accent), var(--ap-primary));
  color: #fff; border: none; cursor: pointer;
  transition: opacity .15s, transform .15s; text-decoration: none; white-space: nowrap;
}
.as-btn-send:hover { opacity: .88; transform: translateY(-1px); color: #fff; }

.as-alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 18px; border-radius: 12px;
  font-size: .82rem; font-weight: 500; margin-bottom: 18px; border: 1.5px solid;
}
.as-alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.as-alert-error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

.as-file-zone {
  border: 2px dashed #e2e8f0; border-radius: 12px; padding: 22px;
  text-align: center; background: #f8fafc; cursor: pointer;
  transition: border-color .18s, background .18s; position: relative;
}
.as-file-zone:hover, .as-file-zone.drag-over { border-color: var(--ap-accent); background: rgba(13,148,136,.04); }
.as-file-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
.as-file-zone-icon { font-size: 1.8rem; color: #94a3b8; margin-bottom: 8px; }
.as-file-zone-text { font-size: .8rem; color: #64748b; line-height: 1.5; }
.as-file-zone-name { font-size: .8rem; font-weight: 600; color: var(--ap-accent); margin-top: 6px; }
.as-format-note {
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 10px; padding: 12px 16px; font-size: .75rem; color: #475569; line-height: 1.8;
}
</style>

<?php if ($flash): ?>
  <?php [$ftype, $fmsg] = explode('|', $flash, 2); ?>
  <div class="as-alert as-alert-<?= $ftype ?>">
    <i class="bi bi-<?= $ftype === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
    <?= htmlspecialchars($fmsg) ?>
    <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1rem;color:inherit;opacity:.6;">×</button>
  </div>
<?php endif; ?>

<!-- Top Actions -->
<div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
  <button class="ap-btn ap-btn-primary" onclick="document.getElementById('importModal').classList.add('open')">
    <i class="bi bi-file-earmark-arrow-up-fill"></i> Import CSV
  </button>
</div>

<!-- Filter Tabs + Search -->
<div class="as-filter-bar">
  <button class="as-tab active" id="tabAll" onclick="setFilter('all')">
    All <span class="as-tab-count"><?= $all_count ?></span>
  </button>
  <button class="as-tab" id="tabNotSent" onclick="setFilter('not_sent')">
    <i class="bi bi-envelope-x-fill"></i> Email Not Sent
    <span class="as-tab-count"><?= $not_sent_count ?></span>
  </button>
  <div class="as-search-wrap">
    <i class="bi bi-search"></i>
    <input type="text" id="searchInput" class="as-search-input" placeholder="Search name, ID, date…">
  </div>
</div>

<!-- Access History Panel -->
<div class="as-panel">
  <div class="as-panel-header">
    <div class="as-panel-title-row">
      <div class="as-panel-icon"><i class="bi bi-clock-history"></i></div>
      <span class="as-panel-title">Access History</span>
      <span class="as-total-badge"><?= $all_count ?> students</span>
    </div>
    <button type="button" onclick="sendSelected()"
      class="ap-btn ap-btn-primary ap-btn-sm" id="sendSelectedBtn" disabled>
      <i class="bi bi-send-fill"></i> Send Selected
    </button>
  </div>

  <div class="as-select-row">
    <label>
      <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
      Select all on this page
    </label>
    <span class="as-selected-count" id="selectedCount">0 selected</span>
  </div>

  <div style="overflow-x:auto;">
    <table class="as-table" id="accessTable">
      <thead>
        <tr>
          <th></th>
          <th>Serial</th>
          <th>Student ID</th>
          <th>Full Name</th>
          <th>Date Enrolled</th>
          <th>Subscription</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows_data as $row):
          $emailSent   = (bool)$row['email_sent'];
          $subMonth    = $row['subMonth'] ?? null;
          $dateExpired = $row['dateexpired'] ?? null;
          $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

          if (!empty($subMonth)) {
              $subBadge = '<span class="ap-badge ap-badge-info">' . htmlspecialchars($subMonth) . ' Month/s</span>';
          } elseif (!empty($dateExpired) && $dateExpired !== '0000-00-00 00:00:00') {
              $exp = new DateTime($dateExpired, new DateTimeZone('Asia/Manila'));
              if ($exp < $now) {
                  $subBadge = '<span class="ap-badge ap-badge-danger">Expired</span>';
              } else {
                  $diff = $now->diff($exp);
                  $left = ($diff->y * 12 + $diff->m) . 'm ' . $diff->d . 'd left';
                  $subBadge = '<span class="ap-badge ap-badge-success">Active — ' . $left . '</span>';
              }
          } else {
              $subBadge = '<span class="ap-badge ap-badge-gray">No Data</span>';
          }
        ?>
        <tr data-email-sent="<?= $emailSent ? '1' : '0' ?>">
          <td>
            <?php if (!$emailSent): ?>
              <input type="checkbox" class="row-check" value="<?= $row['id'] ?>" onchange="updateSelection()">
            <?php endif; ?>
          </td>
          <td><span class="as-serial">E<?= $row['id'] ?></span></td>
          <td><span class="as-student-id"><?= htmlspecialchars($row['studentnumber']) ?></span></td>
          <td><span class="as-name"><?= htmlspecialchars($row['fullname']) ?></span></td>
          <td><span class="as-date"><?= date('M d, Y | h:i A', strtotime($row['dateenrolled'])) ?></span></td>
          <td><?= $subBadge ?></td>
          <td>
            <?php if ($emailSent): ?>
              <span class="as-badge-sent"><i class="bi bi-check-circle-fill"></i> Email Sent</span>
            <?php else: ?>
              <a href="send_email.php?id=<?= $row['id'] ?>" class="as-btn-send"
                onclick="return confirm('Send email to <?= htmlspecialchars(addslashes($row['fullname'])) ?>?')">
                <i class="bi bi-envelope-fill"></i> Send Email
              </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Import CSV Modal — posts to self -->
<div class="ap-modal-overlay" id="importModal">
  <div class="ap-modal" style="max-width:500px;">
    <div class="ap-modal-header">
      <span class="ap-modal-title">
        <i class="bi bi-file-earmark-arrow-up-fill me-2" style="color:var(--ap-accent);"></i>
        Import CSV
      </span>
      <button class="ap-modal-close" onclick="closeImport()">×</button>
    </div>
    <form action="import_csv.php" method="POST" enctype="multipart/form-data">
      <div class="ap-modal-body" style="display:flex;flex-direction:column;gap:16px;">
        <div>
          <label class="ap-form-label">CSV File</label>
          <div class="as-file-zone" id="fileZone">
            <input type="file" name="csvFile" id="csvFileInput" accept=".csv" required onchange="showFileName(this)">
            <div class="as-file-zone-icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
            <div class="as-file-zone-text">
              Click to choose a CSV file<br>
              <span style="font-size:.72rem;color:#94a3b8;">Supports .csv format</span>
            </div>
            <div class="as-file-zone-name" id="fileNameDisplay" style="display:none;"></div>
          </div>
        </div>
        <div class="as-format-note">
          <div style="font-weight:700;color:var(--ap-primary);margin-bottom:5px;">
            <i class="bi bi-info-circle me-1"></i> CSV Format Notes
          </div>
          <div>• <strong>dateenrolled</strong> — leave blank, auto-set to Manila time</div>
          <div>• <strong>dateexpired</strong> — leave NULL</div>
          <div>• <strong style="color:#dc2626;">subMonth</strong> — use <code>1</code>, <code>3</code>, <code>6</code>, or <code>12</code> for paid; <code>1</code> or <code>2</code> for FREE groups</div>
        </div>
      </div>
      <div class="ap-modal-footer">
        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="closeImport()">Cancel</button>
        <button type="submit" class="ap-btn ap-btn-primary ap-btn-sm">
          <i class="bi bi-upload"></i> Upload &amp; Import
        </button>
      </div>
    </form>
  </div>
</div>

<script>
var table = $('#accessTable').DataTable({
  order: [],
  pageLength: 10,
  dom: 'tip',
  language: {
    info:      "Showing _START_–_END_ of _TOTAL_ students",
    infoEmpty: "No students found",
    paginate:  { previous: '← Prev', next: 'Next →' }
  },
  columnDefs: [{ orderable: false, targets: [0, 6] }]
});

document.getElementById('searchInput').addEventListener('input', function () {
  table.search(this.value).draw();
});

var currentFilter = 'all';
function setFilter(filter) {
  currentFilter = filter;
  document.getElementById('tabAll').classList.toggle('active', filter === 'all');
  document.getElementById('tabNotSent').classList.toggle('active', filter === 'not_sent');
  $.fn.dataTable.ext.search = [];
  if (filter === 'not_sent') {
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      return $(table.row(dataIndex).node()).attr('data-email-sent') === '0';
    });
  }
  table.draw();
  deselectAll();
}

function toggleSelectAll(cb) {
  getVisibleChecks().forEach(function(c) { c.checked = cb.checked; });
  updateSelection();
}

function getVisibleChecks() {
  var checks = [];
  table.rows({ search: 'applied', page: 'current' }).nodes().each(function(node) {
    var cb = node.querySelector('.row-check');
    if (cb) checks.push(cb);
  });
  return checks;
}

function updateSelection() {
  var all     = getVisibleChecks();
  var checked = all.filter(function(c){ return c.checked; });
  document.getElementById('selectedCount').textContent = checked.length + ' selected';
  document.getElementById('sendSelectedBtn').disabled  = checked.length === 0;
  document.getElementById('selectAll').checked         = all.length > 0 && checked.length === all.length;
  document.getElementById('selectAll').indeterminate   = checked.length > 0 && checked.length < all.length;
}

function deselectAll() {
  document.querySelectorAll('.row-check').forEach(function(c){ c.checked = false; });
  document.getElementById('selectAll').checked = false;
  updateSelection();
}

table.on('draw', updateSelection);

function sendSelected() {
  var ids = getVisibleChecks()
    .filter(function(c){ return c.checked; })
    .map(function(c){ return c.value; });
  if (!ids.length) return;

  Swal.fire({
    title: 'Send Emails?',
    html: 'Send welcome emails to <strong>' + ids.length + '</strong> selected student(s)?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0d9488',
    confirmButtonText: '<i class="bi bi-send-fill"></i> Yes, Send',
    cancelButtonText: 'Cancel',
  }).then(function(result) {
    if (!result.isConfirmed) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'send_email_bulk.php';
    ids.forEach(function(id) {
      var inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
      form.appendChild(inp);
    });
    document.body.appendChild(form);
    Swal.fire({ title: 'Sending...', text: 'Please wait while emails are being sent.', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
    form.submit();
  });
}

function closeImport() {
  document.getElementById('importModal').classList.remove('open');
}
document.getElementById('importModal').addEventListener('click', function(e) {
  if (e.target === this) closeImport();
});

function showFileName(input) {
  var display = document.getElementById('fileNameDisplay');
  var zone    = document.getElementById('fileZone');
  if (input.files && input.files[0]) {
    display.textContent = '📄 ' + input.files[0].name;
    display.style.display = 'block';
    zone.querySelector('.as-file-zone-text').style.display = 'none';
    zone.style.borderColor = '#0d9488';
    zone.style.background  = 'rgba(13,148,136,.04)';
  }
}

var zone = document.getElementById('fileZone');
zone.addEventListener('dragover',  function(e){ e.preventDefault(); this.classList.add('drag-over'); });
zone.addEventListener('dragleave', function(){ this.classList.remove('drag-over'); });
zone.addEventListener('drop', function(e) {
  e.preventDefault(); this.classList.remove('drag-over');
  var fi = document.getElementById('csvFileInput');
  fi.files = e.dataTransfer.files;
  showFileName(fi);
});

<?php if ($flash && strpos($flash, 'success') === 0): ?>
// Auto-open modal is not needed after successful import
<?php endif; ?>
</script>

<?php require_once 'partials/footer.php'; ?>
