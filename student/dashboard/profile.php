<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'");
$fetch  = mysqli_fetch_assoc($select);

// Expiry check
date_default_timezone_set('Asia/Manila');
$daysLeft = floor((strtotime($fetch['dateexpired']) - time()) / 86400);
if ($daysLeft < 0) { header('Location: ../../logout.php'); exit; }

$initial   = strtoupper(substr($fetch['fullname'], 0, 1));
$firstname = explode(' ', trim($fetch['fullname']))[0];
$pageTitle = 'My Profile — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">

  <?php
  $bundle = str_replace('Packege', 'Package', $fetch['bundle_name']);
  $isActive = $daysLeft >= 0;
  $statusColor = $daysLeft <= 7 ? '#b45309' : '#0D9488';
  $statusBg    = $daysLeft <= 7 ? 'rgba(234,179,8,.1)' : 'rgba(13,148,136,.08)';
  ?>

  <!-- Profile Hero Banner -->
  <div class="s-profile-hero mb-4">
    <div class="s-ph-avatar"><?php echo $initial; ?></div>
    <div class="s-ph-info">
      <h2 class="s-ph-name"><?php echo htmlspecialchars($fetch['fullname']); ?></h2>
      <div class="s-ph-email"><i class="bi bi-envelope-fill me-1"></i><?php echo htmlspecialchars($fetch['email']); ?></div>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <span class="s-badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:0.75rem;padding:4px 12px;">
          <i class="bi bi-box-seam me-1"></i><?php echo htmlspecialchars($bundle); ?>
        </span>
        <span class="s-badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:0.75rem;padding:4px 12px;">
          <i class="bi bi-journal-check me-1"></i><?php echo $fetch['examTaken']; ?> Exams Taken
        </span>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- Subscription Status Card -->
    <div class="col-lg-4 col-12">
      <div class="s-card h-100">
        <div class="s-card-title"><i class="bi bi-shield-check"></i> Subscription Status</div>
        <div class="text-center py-3">
          <div style="font-size:3.5rem; color:<?php echo $statusColor; ?>; margin-bottom:8px;">
            <i class="bi bi-<?php echo $daysLeft > 7 ? 'check-circle-fill' : ($daysLeft >= 0 ? 'exclamation-circle-fill' : 'x-circle-fill'); ?>"></i>
          </div>
          <div class="fw-bold mb-1" style="font-size:1.1rem; color:<?php echo $statusColor; ?>;">
            <?php echo $daysLeft > 7 ? 'Active' : ($daysLeft >= 0 ? 'Expiring Soon' : 'Expired'); ?>
          </div>
          <div style="font-size:0.78rem; color:var(--s-muted); margin-bottom:16px;">
            <?php echo $daysLeft > 0 ? $daysLeft . ' day' . ($daysLeft > 1 ? 's' : '') . ' remaining' : 'Expires today'; ?>
          </div>
          <div class="s-progress mb-3">
            <?php $pct = min(100, max(0, round(($daysLeft / 365) * 100))); ?>
            <div class="s-progress-bar" style="width:<?php echo $pct; ?>%; background:<?php echo $statusColor; ?>;"></div>
          </div>
          <a href="subscription.php" class="s-btn s-btn-teal w-100" style="justify-content:center;">
            <i class="bi bi-arrow-up-circle me-1"></i> Upgrade Plan
          </a>
        </div>
      </div>
    </div>

    <!-- Account Details Card -->
    <div class="col-lg-8 col-12">
      <div class="s-card h-100">
        <div class="s-card-title"><i class="bi bi-person-lines-fill"></i> Account Information</div>
        <div class="row g-3">
          <div class="col-md-6 col-12">
            <label class="s-label">Full Name</label>
            <div class="s-info-field"><i class="bi bi-person-fill"></i><?php echo htmlspecialchars($fetch['fullname']); ?></div>
          </div>
          <div class="col-md-6 col-12">
            <label class="s-label">Email Address</label>
            <div class="s-info-field"><i class="bi bi-envelope-fill"></i><?php echo htmlspecialchars($fetch['email']); ?></div>
          </div>
          <div class="col-md-6 col-12">
            <label class="s-label">Student Number</label>
            <div class="s-info-field"><i class="bi bi-hash"></i><?php echo htmlspecialchars($fetch['studentnumber']); ?></div>
          </div>
          <div class="col-md-6 col-12">
            <label class="s-label">Package</label>
            <div class="s-info-field"><i class="bi bi-box-seam"></i><?php echo htmlspecialchars($bundle); ?></div>
          </div>
          <div class="col-md-6 col-12">
            <label class="s-label">Date Enrolled</label>
            <div class="s-info-field"><i class="bi bi-calendar-plus"></i><?php echo date('F d, Y', strtotime($fetch['dateenrolled'])); ?></div>
          </div>
          <div class="col-md-6 col-12">
            <label class="s-label">Date Expires</label>
            <div class="s-info-field" style="color:<?php echo $statusColor; ?>;"><i class="bi bi-calendar-x"></i><?php echo date('F d, Y', strtotime($fetch['dateexpired'])); ?></div>
          </div>
        </div>
        <p class="mb-0 mt-3" style="font-size:0.7rem; color:var(--s-muted);"><i class="bi bi-info-circle me-1"></i>All times are Philippine Standard Time (UTC+8)</p>
      </div>
    </div>

  </div>
</main>

<div class="s-footer"><span>© Studium 2025, All Right Reserved.</span></div>

<script src="../ty/js/bootstrap.bundle.min.js"></script>
</body>
</html>
