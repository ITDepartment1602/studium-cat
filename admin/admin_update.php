<?php
include("count.php");
$count = new count;
include('../config.php');

if (count($_POST) > 0) {
    $bundle_name = implode(",", $_POST['bundle_name']);
    $subMonth    = !empty($_POST['subMonth']) ? $_POST['subMonth'] : null;
    $dateexpired = $_POST['dateexpired'];
    if (empty($dateexpired) || $dateexpired === '0000-00-00 00:00:00') $dateexpired = null;

    $query = "UPDATE login SET
        studentnumber='" . $_POST['studentnumber'] . "',
        fullname='" . $_POST['fullname'] . "',
        bundle_name='" . $bundle_name . "',
        groupname='" . $_POST['groupname'] . "',
        dateexpired=" . (is_null($dateexpired) ? "NULL" : "'" . $dateexpired . "'") . ",
        dateenrolled='" . $_POST['dateenrolled'] . "',
        subMonth=" . (is_null($subMonth) ? "NULL" : "'" . $subMonth . "'") . ",
        password='" . $_POST['password'] . "',
        email='" . $_POST['email'] . "'
        WHERE id='" . $_POST['id'] . "'";

    mysqli_query($con, $query);
    $message = 'Record updated successfully.';
    $message_type = 'success';
}

$result = mysqli_query($con, "SELECT * FROM login WHERE id='" . $_GET['id'] . "'");
$row    = mysqli_fetch_array($result);
$selected_bundles = explode(",", $row['bundle_name']);

$ap_base    = '';
$active_nav = 'dashboard';
$ap_title   = 'Update Student';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">';
require_once 'partials/sidebar.php';
?>

<div class="ap-card" style="max-width:680px;margin:0 auto;">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-pencil-square"></i></div>
      Update Student Details
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div style="padding:24px;">
    <?php if (isset($message)): ?>
    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> mb-3" style="border-radius:8px;padding:12px 16px;">
      <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="id" value="<?= $row['id'] ?>">

      <div style="display:grid;gap:16px;">
        <div class="ap-form-group">
          <label class="ap-form-label">Student ID</label>
          <input type="text" name="studentnumber" class="ap-form-input" value="<?= htmlspecialchars($row['studentnumber']) ?>">
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Full Name</label>
          <input type="text" name="fullname" class="ap-form-input" value="<?= htmlspecialchars($row['fullname']) ?>">
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Bundle</label>
          <select name="bundle_name[]" class="ap-form-select multiple-select" multiple style="width:100%;height:auto;">
            <?php
            $q = mysqli_query($con, "SELECT * FROM bundle WHERE type='1'");
            while ($b = mysqli_fetch_assoc($q)):
            ?>
            <option value="<?= htmlspecialchars($b['bundle_name']) ?>"
              <?= in_array($b['bundle_name'], $selected_bundles) ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['bundle_name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Group</label>
          <input type="text" name="groupname" class="ap-form-input" value="<?= htmlspecialchars($row['groupname']) ?>">
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Date Expired</label>
          <input type="datetime-local" name="dateexpired" class="ap-form-input"
            value="<?= date('Y-m-d\TH:i', strtotime($row['dateexpired'])) ?>">
          <small style="color:var(--ap-text-muted);font-size:0.72rem;">1970-01-01 08:00 AM is treated as NULL</small>
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Date Enrolled</label>
          <input type="datetime-local" name="dateenrolled" class="ap-form-input"
            value="<?= date('Y-m-d\TH:i', strtotime($row['dateenrolled'])) ?>">
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Activation Button (months)</label>
          <input type="number" name="subMonth" class="ap-form-input" value="<?= htmlspecialchars($row['subMonth']) ?>" placeholder="NULL">
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Email</label>
          <input type="email" name="email" class="ap-form-input" value="<?= htmlspecialchars($row['email']) ?>">
        </div>

        <div class="ap-form-group">
          <label class="ap-form-label">Password</label>
          <input type="text" name="password" class="ap-form-input" value="<?= htmlspecialchars($row['password']) ?>">
        </div>
      </div>

      <div style="display:flex;gap:12px;margin-top:24px;">
        <button type="submit" name="submit" class="ap-btn ap-btn-primary"
          onclick="return confirm('Are you sure you want to update this record?')">
          <i class="bi bi-check-lg"></i> Save Changes
        </button>
        <a href="index.php" class="ap-btn ap-btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>$(".multiple-select").select2({ width: "100%" });</script>';
require_once 'partials/footer.php';
?>
