<?php
/**
 * Shared sidebar + mobile top-bar for all student dashboard pages.
 * Requires: $con (main DB), $user_id (session)
 */
$_sb_q = mysqli_query($con, "SELECT fullname, dateexpired, bundle_name FROM `login` WHERE id = '" . intval($user_id) . "'");
$_sb = mysqli_fetch_assoc($_sb_q);
$_sb_firstname = explode(' ', trim($_sb['fullname']))[0];
$_sb_initial   = strtoupper(substr($_sb['fullname'], 0, 1));
$_sb_exp       = date('F d, Y', strtotime($_sb['dateexpired']));
$_sb_exptime   = date('h:i A', strtotime($_sb['dateexpired']));
$_sb_bundle    = $_sb['bundle_name'];

// Expiry notification
date_default_timezone_set('Asia/Manila');
$_sb_daysLeft = floor((strtotime($_sb['dateexpired']) - time()) / 86400);
$_sb_notif = '';
if ($_sb_daysLeft <= 0 && (strtotime($_sb['dateexpired']) - time()) > 0) {
    $_sb_notif = 'Your account expires today.';
} elseif ($_sb_daysLeft > 0 && $_sb_daysLeft <= 7) {
    $_sb_notif = $_sb_daysLeft . ' day' . ($_sb_daysLeft > 1 ? 's' : '') . ' until expiration.';
}

// Active page detection
$_sb_page = basename($_SERVER['PHP_SELF']);
function _sb_active($pages) {
    global $_sb_page;
    $pages = is_array($pages) ? $pages : [$pages];
    return in_array($_sb_page, $pages) ? ' active' : '';
}
?>

<!-- ── Mobile Top Bar ── -->
<div class="s-topbar">
  <button class="s-hamburger" id="sHamburger" aria-label="Open menu">
    <i class="bi bi-list"></i>
  </button>
  <div class="s-topbar-logo">
    <img src="../../assets/LOGO.svg" alt="" style="height:32px; width:32px; object-fit:contain;">
  </div>
  <div class="s-topbar-spacer"></div>
  <?php if (!empty($_sb_notif)): ?>
    <span style="font-size:0.65rem; color:rgba(255,255,255,.75); max-width:140px; text-align:right; line-height:1.2;">
      <i class="bi bi-bell-fill me-1" style="color:#fbbf24;"></i><?php echo htmlspecialchars($_sb_notif); ?>
    </span>
  <?php endif; ?>
  <a href="../../logout.php" class="s-logout-btn ms-3" title="Logout">
    <i class="bi bi-box-arrow-right"></i>
  </a>
</div>

<!-- ── Sidebar Overlay (mobile backdrop) ── -->
<div class="s-overlay" id="sOverlay"></div>

<!-- ── Sidebar ── -->
<aside class="s-sidebar" id="sSidebar">

  <!-- Brand -->
  <div class="s-sidebar-brand">
    <img src="../../assets/STUDIUM.svg" alt="Studium" style="display:block; margin:0 auto; width:120px; height:auto;">
  </div>

  <!-- Welcome card -->
  <div class="s-sidebar-welcome">
    <?php
    $motives = [
      'Every question gets you closer.',
      'One more question. Let\'s go.',
      'You\'ve got this, future RN.',
      'Keep pushing — NCLEX is near.',
      'Study now, celebrate later.',
      'Progress over perfection.',
      'Consistency beats intensity.',
      'Small steps, big results.',
    ];
    $motive = $motives[array_rand($motives)];
    ?>
    <div class="s-welcome-row">
      <div class="s-welcome-avatar"><?= $_sb_initial ?></div>
      <div class="s-welcome-info">
        <div class="s-welcome-label">Hello,</div>
        <div class="s-welcome-name"><?= htmlspecialchars($_sb_firstname) ?></div>
      </div>
    </div>
    <div class="s-welcome-motive"><?= $motive ?></div>
    <?php if ($_sb_daysLeft > 0): ?>
    <div class="s-welcome-exp"><i class="bi bi-calendar-check"></i> <?= $_sb_daysLeft ?> days left</div>
    <?php elseif (!empty($_sb_notif)): ?>
    <div class="s-welcome-exp s-welcome-exp--warn"><i class="bi bi-bell-fill"></i> <?= htmlspecialchars($_sb_notif) ?></div>
    <?php endif; ?>
  </div>

  <!-- Navigation -->
  <ul class="s-nav">
    <li>
      <a href="index.php" class="<?php echo _sb_active('index.php'); ?>">
        <i class="bi bi-house-fill"></i> Home
      </a>
    </li>
    <li>
      <a href="profile.php" class="<?php echo _sb_active('profile.php'); ?>">
        <i class="bi bi-person-circle"></i> View Profile
      </a>
    </li>
    <li>
      <a href="history.php" class="<?php echo _sb_active(['history.php','history_details.php']); ?>">
        <i class="bi bi-clock-history"></i> Exam History
      </a>
    </li>
    <li>
      <a href="mynotes.php" class="<?php echo _sb_active('mynotes.php'); ?>">
        <i class="bi bi-journal-text"></i> My Notes
      </a>
    </li>
    <li>
      <a href="subscription.php" class="<?php echo _sb_active('subscription.php'); ?>">
        <i class="bi bi-calendar-check"></i> Subscription
      </a>
    </li>
    <li>
      <a href="../../img/userguide.mp4" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-play-circle"></i> User Guide
      </a>
    </li>
    <li>
      <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-facebook"></i> Contact Us
      </a>
    </li>
  </ul>

  <!-- Footer: Expiration + Logout -->
  <div class="s-sidebar-footer">
    <div class="s-exp-label">Expiration Date</div>
    <div class="s-exp-date"><?php echo $_sb_exp; ?><br><span style="font-weight:400; color:rgba(255,255,255,.5);"><?php echo $_sb_exptime; ?></span></div>
    <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank" class="s-upgrade-btn mb-2">
      <i class="bi bi-arrow-up-circle me-1"></i> Upgrade Now
    </a>
    <a href="../../logout.php" class="s-logout-link">
      <i class="bi bi-box-arrow-right me-1"></i> Logout
    </a>
  </div>
</aside>

<!-- ── Sidebar JS ── -->
<script>
(function() {
  const sidebar  = document.getElementById('sSidebar');
  const overlay  = document.getElementById('sOverlay');
  const hamburger = document.getElementById('sHamburger');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (hamburger) hamburger.addEventListener('click', openSidebar);
  if (overlay)   overlay.addEventListener('click', closeSidebar);
})();
</script>
